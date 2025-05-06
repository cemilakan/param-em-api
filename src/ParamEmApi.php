<?php

namespace Param\EmApi;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ParamEmApi
{
    protected $clientCode;
    protected $username;
    protected $password;
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('param_em_api.base_url');
        $this->clientCode = config('param_em_api.client_code');
        $this->username = config('param_em_api.username');
        $this->password = config('param_em_api.password');
    }

    public function getToken($shouldReload = false)
    {
      if($shouldReload) Cache::forget('param_em_api_token');
      return Cache::remember('param_em_api_token', now()->addMinutes(14), function () {
        $response = Http::post("{$this->baseUrl}/Authorization/Token", [
          'clientCode' => $this->clientCode,
          'username' => $this->username,
          'password' => $this->password,
        ]);
        
        // Response içindeki resultInfo.isSuccess kontrolü yapılır
        if ($response->successful()) {
            $data = $response->json();
        
            // Eğer resultInfo.isSuccess true ise accessToken'ı döndür
            if (isset($data['resultInfo']['isSuccess']) && $data['resultInfo']['isSuccess'] === true) {
                return $data['resultObject']['accessToken'];  // Token'ı döndür
            }
        
            // Eğer isSuccess false ise hata mesajını döndür
            throw new \Exception('Token alırken bir hata oluştu: ' . $data['resultInfo']['message']);
        }
        
        // Eğer HTTP isteği başarısız olursa hata fırlatılır
        throw new \Exception('Token alırken HTTP hatası oluştu: ' . $response->body());
      });
    }
}
