<?php

return [
    'client_code' => env('PARAM_EM_API_CLIENT_CODE','10738'),
    'username' => env('PARAM_EM_API_USERNAME','test'),
    'password' => env('PARAM_EM_API_PASSWORD','test'),
    'account_id' => env('PARAM_EM_API_ACCOUNT_ID', '0B8B3CF1-ACD6-4509-9D47-19FDD541A85F'),
    'base_url' => env('PARAM_EM_API_BASE_URL','https://testemapi.param.com.tr'),
    'prefix' => env('PARAM_EM_API_PREFIX','/api/v1/'),
    'throw_exceptions' => env('PARAM_EM_API_THROW_EXCEPTIONS', false),
];
// return [
//     'client_code' => env('PARAM_EM_API_CLIENT_CODE', '145568'),
//     'username' => env('PARAM_EM_API_USERNAME', 'TP849B37492'),
//     'password' => env('PARAM_EM_API_PASSWORD', 'JZWD73F120A765UX'),
//     'base_url' => env('PARAM_EM_API_BASE_URL', 'https://emapi.param.com.tr/api/v1'),
//     'throw_exceptions' => env('PARAM_EM_API_THROW_EXCEPTIONS', true),
// ];