<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrganizationRequest;
use App\Http\Requests\SetOrganizationPasswordRequest;
use App\Services\OrganizationService;
use Illuminate\Http\Request;

// class OrganizationController extends Controller
// {
//     protected $organizationService;

//     public function __construct(OrganizationService $organizationService)
//     {
//         $this->organizationService = $organizationService;
//     }

//     public function index()
//     {
//         try {
//             return $this->organizationService->getAll();
//         } catch (\Exception $e) {
//             $this->storeException($e);
//             return $this->errorResponse($this->getMessageData('error', 'en')['general_error'], 500);
//         }
//     }

//     public function store(CreateOrganizationRequest $request)
//     {
//         try {
//             return $this->organizationService->createOrganization($request);
//         } catch (\Exception $e) {
//             $this->storeException($e);
//             return $this->errorResponse($this->getMessageData('error', 'en')['general_error'], 500);
//         }
//     }

//     public function update(Request $request, $id)
//     {
//         try {
//             return $this->organizationService->updateOrganization($id, $request);
//         } catch (\Exception $e) {
//             $this->storeException($e);
//             return $this->errorResponse($this->getMessageData('error', 'en')['general_error'], 500);
//         }
//     }

//     public function destroy($id)
//     {
//         try {
//             return $this->organizationService->deleteOrganization($id);
//         } catch (\Exception $e) {
//             $this->storeException($e);
//             return $this->errorResponse($this->getMessageData('error', 'en')['general_error'], 500);
//         }
//     }

//     public function setPassword(SetOrganizationPasswordRequest $request)
//     {
//         try {
//             return $this->organizationService->setPassword($request);
//         } catch (\Exception $e) {
//             $this->storeException($e);
//             return $this->errorResponse($this->getMessageData('error', 'en')['general_error'], 500);
//         }
//     }

    
// }