<?php


namespace App\Http\Responses;

// Update this path to match where your trait file actually lives
use \App\Http\Traits\MessagesService;
use \App\Http\Traits\CommonService;

class BaseResponse {
    use CommonService;
    use MessagesService;
}
