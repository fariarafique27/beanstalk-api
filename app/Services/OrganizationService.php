<?php

namespace App\Services;

use App\Repositories\OrganizationRepository;
use App\Repositories\AuthRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OrganizationService extends BaseService
{
    protected $organizationRepository;
    protected $authRepository;

    public function __construct(
        OrganizationRepository $organizationRepository,
        AuthRepository $authRepository
    ) {
        $this->organizationRepository = $organizationRepository;
        $this->authRepository = $authRepository;
    }

    public function getAll()
    {
        $organizations = $this->organizationRepository->getAllOrganizations();
        return $this->successResponse($organizations, 'Data retrieved successfully');
    }

    public function createOrganization($request)
    {
        return DB::transaction(function () use ($request) {
            // 1. Create Organization & User
            $organization = $this->organizationRepository->createOrganization($request->validated());
            $this->organizationRepository->createOrgAdminUser($organization, $request->validated());

            // 2. Attach Permissions
            if ($request->has('permissions')) {
                $this->organizationRepository->syncPermissions($organization->id, $request->permissions);
            }

            // 3. Generate invitation token & link
            $token = Str::random(60);
            $this->organizationRepository->createInvitationToken($request->email, $token);

            $frontendUrl = config('app.frontend_url', url('/'));
            $setupUrl = "{$frontendUrl}/set-password?token={$token}&email=" . urlencode($request->email);

            // 4. Send Email
            Mail::raw("Hello! You have been invited as an Organization Admin for {$organization->name}. Click here to set up your password: {$setupUrl}", function ($message) use ($request) {
                $message->to($request->email)->subject('Set Up Your Organization Admin Account');
            });

            return $this->successResponse(
                $organization->load('permissions'),
                'Organization created successfully and setup email sent!',
                201
            );
        });
    }

    public function updateOrganization($id, $request)
    {
        $organization = $this->organizationRepository->findById($id);

        if (!$organization) {
            return $this->errorResponse($this->getMessageData('error', 'en')['not_found'] ?? 'Organization not found', 404);
        }

        $organization->update(['name' => $request->name]);

        if ($request->has('permissions')) {
            $this->organizationRepository->syncPermissions($organization->id, $request->permissions);
        }

        return $this->successResponse($organization->load('permissions'), 'Organization updated successfully');
    }

    public function deleteOrganization($id)
    {
        $organization = $this->organizationRepository->findById($id);

        if (!$organization) {
            return $this->errorResponse($this->getMessageData('error', 'en')['not_found'] ?? 'Organization not found', 404);
        }

        $organization->delete();

        return $this->successResponse(null, 'Organization deleted successfully');
    }

    public function setPassword($request)
    {
        $invitation = $this->organizationRepository->findInvitationToken($request->email, $request->token);

        if (!$invitation) {
            return $this->errorResponse('Invalid or expired setup token.', 400);
        }

        $user = $this->authRepository->findUserByEmail($request->email);

        if (!$user) {
            return $this->errorResponse('User not found.', 404);
        }

        $this->organizationRepository->updatePasswordAndActivate($user, $request->password);
        $this->organizationRepository->deleteInvitationToken($request->email);

        return $this->successResponse(null, 'Password created successfully! You can now log in.');
    }
}