<?php

namespace App\Repositories;

use Response;

class BaseRepo {

    const CODE_STATUS_OK_OTHER = 201;
    const CODE_STATUS_OK = 200;
    const CODE_STATUS_ERROR = 401;

    public function __construct() {
        
    }

    public function getRequestError($errors = [], $addParams = []) {
        $arrayReturn = ['success' => false, 'errors' => $errors];
        $resultado = array_merge($arrayReturn, $addParams);
        return response()->json($resultado, self::CODE_STATUS_ERROR);
    }

    public function postRequestOk($message, $addParams = []) {
        $arrayReturn = ['success' => true, 'message' => $message];
        $resultado = array_merge($arrayReturn, $addParams);
        return response()->json($resultado, self::CODE_STATUS_OK);
    }

    public function postRequestNoRegister($message, $addParams = []) {
        $arrayReturn = ['success' => false, 'message' => $message];
        $resultado = array_merge($arrayReturn, $addParams);
        return response()->json($resultado, self::CODE_STATUS_OK);
    }

    public function postRequestOkRegister($message, $addParams = []) {
        $arrayReturn = ['success' => true, 'message' => $message];
        $resultado = array_merge($arrayReturn, $addParams);
        return response()->json($resultado, self::CODE_STATUS_OK_OTHER);
    }

    protected function getUrlTipoCambio() {
        return config('general.tipocambio.url');
    }

}
