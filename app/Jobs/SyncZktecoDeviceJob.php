<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\OrganizationDevice;
use App\Models\ZktecoUser;
use App\Services\ZktecoService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class SyncZktecoDeviceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected OrganizationDevice $device) {}

    public function handle(): void
    {
        $zkteco = new ZktecoService($this->device->ip, $this->device->port);

        $this->syncUsers($zkteco);
        $this->syncAttendance($zkteco);

        $this->device->update(['last_synced_at' => now()]);
    }

    protected function syncUsers(ZktecoService $zkteco): void
    {
        $users = $zkteco->fetchUsers();
        if (empty($users)) {
            return;
        }

        $orgId = $this->device->organization_id;

        $rows = collect($users)->map(fn ($user) => [
            'organization_id' => $orgId,
            'uid' => (string) $user->uid,
            'user_id' => (string) $user->userId,
            'name' => trim($user->name),
            'role' => $user->privilege->value ?? 0,
            'cardno' => trim((string) ($user->cardNumber ?? '')) ?: null,
            'password' => trim((string) ($user->password ?? '')) ?: null,
            'group_id' => $user->groupId ?? 0,
            'created_at' => now(),
            'updated_at' => now(),
        ])->values()->all();

        ZktecoUser::upsert(
            $rows,
            ['organization_id', 'uid'],
            ['user_id', 'name', 'role', 'cardno', 'password', 'group_id', 'updated_at']
        );
    }

    protected function syncAttendance(ZktecoService $zkteco): void
    {
        $entries = $zkteco->fetchAttendance();
        if (empty($entries)) {
            return;
        }

        $orgId = $this->device->organization_id;
        $oneWeekAgo = Carbon::now()->subWeek()->startOfDay();

        // scoped to THIS org only — the key multi-tenant fix
        $zkUsers = ZktecoUser::where('organization_id', $orgId)->get()->keyBy('uid');

        $validEntries = [];

        foreach ($entries as $entry) {
            $record = $entry['record'] ?? null;
            if (!$record) {
                continue;
            }

            $timestamp = Carbon::instance($record->recordedAt);
            if ($timestamp->lessThan($oneWeekAgo)) {
                continue;
            }

            $uid = (string) $record->uid;
            $zkUser = $zkUsers->get($uid);
            if (!$zkUser) {
                continue;
            }

            $validEntries[] = [
                'zkUser' => $zkUser,
                'timestamp' => $timestamp,
                'date' => $timestamp->toDateString(),
            ];
        }

        usort($validEntries, fn ($a, $b) => $a['timestamp']->greaterThan($b['timestamp']) ? 1 : -1);

        foreach ($validEntries as $item) {
            $zkUser = $item['zkUser'];
            $date = $item['date'];
            $timestamp = $item['timestamp'];

            // DB-based open-session check (not in-memory counting — the fix we discussed)
            $attendance = Attendance::firstOrCreate(
                ['employee_id' => $zkUser->id, 'attendance_date' => $date],
                ['organization_id' => $orgId, 'status' => 'Present', 'user_name' => $zkUser->name]
            );

            $openLog = AttendanceLog::where('attendance_id', $attendance->id)
                ->whereNull('check_out_time')
                ->first();

            try {
                DB::transaction(function () use ($attendance, $zkUser, $timestamp, $openLog) {
                    if (!$openLog) {
                        $exists = AttendanceLog::where('attendance_id', $attendance->id)
                            ->where('check_in_time', $timestamp)
                            ->exists();
                        if ($exists) return;

                        AttendanceLog::create([
                            'attendance_id' => $attendance->id,
                            'zkteco_user_id' => $zkUser->id,
                            'check_in_time' => $timestamp,
                            'check_in_punch_state' => 'Check In at ' . $timestamp->format('h:i A'),
                        ]);
                    } else {
                        $exists = AttendanceLog::where('id', $openLog->id)
                            ->where('check_out_time', $timestamp)
                            ->exists();
                        if ($exists) return;

                        $openLog->update([
                            'check_out_time' => $timestamp,
                            'check_out_punch_state' => 'Check Out at ' . $timestamp->format('h:i A'),
                        ]);
                    }
                });
            } catch (Throwable $e) {
                report($e);
            }
        }
    }
}