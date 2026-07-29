<?php

namespace App\Http\Traits;

trait MessagesService
{
        public function getMessageData($type, $lang = 'en') 
    {
        if ($lang == 'en' && $type == 'error') {
            return $this->error_en;
        }
        
        if ($lang == 'en' && $type == 'success') {
            return $this->success_en;
        }

        return [];
    }
    
    public $success_en = [
        'general_success' => 'Request successfully processed.',
    ];
    public $error_en = [
        'general_error' => 'Request not processed at this moment. Please try again or contact with support team.',
       
    ];


}