<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesResources;
use Illuminate\Contracts\Validation\Validator;
class Controller extends BaseController
{
    use AuthorizesRequests, AuthorizesResources, DispatchesJobs, ValidatesRequests;
    
    protected function formatValidationErrors(Validator $validator)
    {
        $res = $validator->errors()->all();
        if(\Request::wantsJson())
        {
            $res = \General::validation_error_res($validator->errors()->first());
            $res['data'] = $validator->errors()->all();
        }
        return $res;
    }
}
