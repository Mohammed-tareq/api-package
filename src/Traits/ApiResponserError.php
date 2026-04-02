<?php

namespace MohammedTareq\ApiConfig\Traits;

trait ApiResponserError
{
    public function apiResponseError($message, $status = 200, $data = null, $requestId = null, $errorCode = null)
    {
        $response = [
            'success' => $status >= 200 && $status < 300,
            'message' => $message,
            'request_id' => $requestId,
        ];

        if ($errorCode) {
            $response['error_code'] = $errorCode;
        }

        if ($data !== null) {
            $response['errors'] = $data;   // للـ validation وغيره
        }

        return response()->json($response, $status);
    }
}