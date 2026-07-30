<?php

namespace App\Services;

use Rats\Zkteco\Lib\ZKTeco;

class ZktecoService
{
    protected string $ip;
    protected int $port;

    public function __construct(?string $ip = null, ?int $port = null)
    {
        $this->ip = $ip ?? config('zkteco.ip', '192.168.1.201');
        $this->port = $port ?? config('zkteco.port', 4370);
    }

    /**
     * Fetch the user list from the device.
     */
    public function fetchUsers(): array
    {
        $zk = new ZKTeco($this->ip, $this->port);

        if ($zk->connect()) {
            $users = $zk->getUser();
            $zk->disconnect();
            return is_array($users) ? $users : [];
        }

        return [];
    }

    /**
     * Fetch the attendance log from the device, each record matched with users if available.
     */
    public function fetchAttendance(): array
    {
        $zk = new ZKTeco($this->ip, $this->port);

        if ($zk->connect()) {
            $users = $zk->getUser() ?: [];
            $usersByUid = collect($users)->keyBy('uid');

            $attendance = $zk->getAttendance() ?: [];
            $zk->disconnect();

            return collect($attendance)
                ->map(fn ($record) => [
                    'record' => $record,
                    'user' => $usersByUid->get($record['uid'] ?? null),
                ])
                ->all();
        }

        return [];
    }
}