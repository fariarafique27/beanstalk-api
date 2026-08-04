<?php

namespace App\Services;

use ZkTeco\TCP\Device;

class ZktecoService
{
    protected string $ip;
    protected int $port;

    public function __construct(?string $ip = null, ?int $port = null)
    {
        $this->ip = $ip ?? env('ZKTECO_IP', '192.168.1.15');
        $this->port = $port ?? env('ZKTECO_PORT', 4370);
    }

    /**
     * Fetch the user list from the device.
     *
     * @return \ZkTeco\Values\User[]
     */
    public function fetchUsers(): array
    {
        return $this->newDevice()->session(fn (Device $d) => $d->users()->all());
    }

    /**
     * Fetch the attendance log from the device, each record merged with its
     * matching user.
     *
     * @return array<int, array{record: \ZkTeco\Values\AttendanceRecord, user: ?\ZkTeco\Values\User}>
     */
    public function fetchAttendance(): array
    {
        return $this->newDevice()->session(function (Device $d) {
            $usersByUid = collect($d->users()->all())->keyBy('uid');

            return collect($d->attendance()->all())
                ->map(fn ($record) => [
                    'record' => $record,
                    'user' => $usersByUid->get($record->uid),
                ])
                ->all();
        });
    }

    protected function newDevice(): Device
    {
        return new Device(host: $this->ip, port: $this->port);
    }
}

// Older implementation using Rats\Zkteco\Lib\ZKTeco — kept for reference,
// not currently in use.
//
// use Rats\Zkteco\Lib\ZKTeco;
//
// class ZktecoService
// {
//     protected string $ip;
//     protected int $port;

//     public function __construct(?string $ip = null, ?int $port = null)
//     {
//         $this->ip = $ip ?? config('zkteco.ip', '192.168.1.15');
//         $this->port = $port ?? config('zkteco.port', 4370);
//     }

//     /**
//      * Fetch the user list from the device.
//      */
//     public function fetchUsers(): array
//     {
//         $zk = new ZKTeco($this->ip, $this->port);

//         if ($zk->connect()) {
//             $users = $zk->getUser();
//             $zk->disconnect();
//             return is_array($users) ? $users : [];
//         }

//         return [];
//     }

//     /**
//      * Fetch the attendance log from the device, each record matched with users if available.
//      */
//     public function fetchAttendance(): array
//     {
//         $zk = new ZKTeco($this->ip, $this->port);

//         if ($zk->connect()) {
//             $users = $zk->getUser() ?: [];
//             $usersByUid = collect($users)->keyBy('uid');

//             $attendance = $zk->getAttendance() ?: [];
//             $zk->disconnect();

//             return collect($attendance)
//                 ->map(fn ($record) => [
//                     'record' => $record,
//                     'user' => $usersByUid->get($record['uid'] ?? null),
//                 ])
//                 ->all();
//         }

//         return [];
//     }
// }