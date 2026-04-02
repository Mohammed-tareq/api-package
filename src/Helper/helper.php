<?php

namespace App\Helper;


if(!function_exists('apiResponseHelper')){

    function apiResponseHelper($status, $message, $data = null)
    {
        $response = [
            'status' => $status,
            'message' => $message,
        ];
        if($data){
            $response['data'] = $data;
        }
        return response()->json($response, $status);
    }
}


