<?php

namespace App\Services;

use App\Repositories\OrganizationRepository;
use App\Http\Responses\ResponseOrganization;

class OrgAdminService extends BaseService
{
    protected OrganizationRepository $organizationRepository;

    public function __construct(OrganizationRepository $organizationRepository)
    {
        $this->organizationRepository = $organizationRepository;
    }

    public function index()
    {
        $organizations = $this->organizationRepository->getAllOrganizations();

        $stats = [
            'total_orgs' => count($organizations),
            'active_admins' => collect($organizations)->where('status', 1)->count(),
            'pending_invites' => collect($organizations)->where('status', '!=', 1)->count(),
        ];

        return (new ResponseOrganization(null))->prepareOrganizationResponse($organizations, $stats);
    }
}