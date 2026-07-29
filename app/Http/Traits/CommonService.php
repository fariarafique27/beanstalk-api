<?php


namespace App\Http\Traits;

use Illuminate\Support\Facades\Request;
use App\Models\CodeException;
use App\Models\User;
use Illuminate\Support\Facades\Response;
use GuzzleHttp\Client;
use App\Services\SalaryEncryptionService;


trait CommonService {

    /**
     * errorResponse method
     * @param type $error
     * @param type $code
     * @return Response
     */
    public function errorResponse($error, $code = 2044) {
        $response = [];
        $response['success'] = false;
        $response['message'] = $error;
        $response['status_code'] = $code;
        return Response::json($response,$code);
    }

    public function errorResponseWithData($error, $data=[], $code = 422) {
        $response = [];
        $response['success'] = false;
        $response['data'] = $data;
        $response['message'] = $error;
        $response['status_code'] = $code;
        return Response::json($response, $code);
    }

    public function errorValidationResponseWithData($error, $data=[], $code = 2044) {
        $response = [];
        $response['success'] = false;
        $response['errors'] = $data;
        $response['message'] = $error;
        $response['status_code'] = $code;
        return Response::json($response);
    }


    /**
     * SuccessResponse method
     * @param type $msg
     * @param type $data
     * @param type $code
     * @return type
     */
    public function successResponse($msg, $data = [], $code = 200) {
        $response = [];
        $response['success'] = true;
        $response['data'] = $data;
        $response['message'] = $msg;
        $response['status_code'] = $code;
        return Response::json($response);
    }

    /**
     * SuccessResponseWithoutData method
     * @param type $msg
     * @return type
     */
    public function successResponseWithoutData($msg, $code = 200) {
        $response = [];
        $response['success'] = true;
        $response['message'] = $msg;
        $response['status_code'] = $code;
        return Response::json($response);
    }

    public function cleanAndConvertTime($value)
    {
        if (!$value)
            return null;

        $value = strtoupper(trim($value));
        $timestamp = strtotime(explode(' ', $value)[0]);
        return date('H:i:s', $timestamp);
    }


}
