<?php
namespace App\Repositories;

use App\Models\OrganizationDevice;

class DeviceRepository extends BaseRepository
{
    public function __construct(OrganizationDevice $device)
    {
        $this->model = $device;
    }

    public function findByOrganization($organizationId)
    {
        return $this->model->where('organization_id', $organizationId)->first();
    }

    public function findActiveByOrganization($organizationId)
    {
        return $this->model
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->first();
    }

    public function updateOrCreate($organizationId, array $data)
    {
        return $this->model->updateOrCreate(
            ['organization_id' => $organizationId],
            $data
        );
    }
}