<?php

namespace Param\EmApi;

use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use RuntimeException;


class ParamEmApi
{
    protected $clientCode;
    protected $username;
    protected $password;
    protected $account_id;
    protected $baseUrl;
    protected $throw;
    protected $client;
    protected $cookieJar;
    protected $accessToken;
    protected $prefix;

    public function __construct()
    {
        $this->baseUrl    = config('param_em_api.base_url');
        $this->clientCode = config('param_em_api.client_code');
        $this->username   = config('param_em_api.username');
        $this->password   = config('param_em_api.password');
        $this->account_id   = config('param_em_api.account_id');
        $this->prefix   = config('param_em_api.prefix');
        $this->throw      = config('param_em_api.throw_exceptions');

        $this->cookieJar = new CookieJar();

        $this->client = new Client([
            'base_uri'    => $this->baseUrl,
            'cookies'     => $this->cookieJar,
            'http_errors' => true,
            'headers'     => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    public function getToken(bool $shouldReload = false)
    {
        if (!$shouldReload && Cache::has('param_em_api_token')) {
            return $this->accessToken = Cache::get('param_em_api_token');
        }
        try {
            $response = $this->client->post($this->prefix . 'Authorization/Token', [
                'json' => [
                    'clientCode' => $this->clientCode,
                    'username'   => $this->username,
                    'password'   => $this->password,
                ]
            ]);
        } catch (RequestException $e) {

            return $this->formatApiError(
                'Token isteği sırasında hata oluştu',
                $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0,
                ['raw' => $e->getMessage()]
            );
        }
        $body = json_decode($response->getBody()->getContents(), true) ?? [];

        if ($response->getStatusCode() === 200 && ($body['resultInfo']['isSuccess'] ?? false)) {
            $token = $body['resultObject']['accessToken'];
            $expiresIn = $body['resultObject']['expiresIn'] ?? 1200;
            $minutes = floor($expiresIn / 60) - 1;
            
            $this->accessToken = $token;
            Cache::put('param_em_api_token', $token, now()->addMinutes($minutes));
            return $token;
        }

        $message = $body['resultInfo']['message'] ?? 'Bilinmeyen hata';

        if (!$this->accessToken) {
            return $this->formatApiError($message, $response->getStatusCode(), $body);
        }
    }

    public function request(string $endpoint, string $method = 'GET', array $params = []): array
    {
        $token = $this->getToken(true);
        if (!is_string($token)) {
            return $token;
        }

        $options = [
            'headers' => [
                'Authorization' => "Bearer {$token}",
            ]
        ];

        $method = strtoupper($method);
        if (in_array($method, ['POST', 'PUT'])) {
            $options['json'] = $params;
        } elseif ($method === 'GET') {
            $options['query'] = $params;
        }

        try {
            $response = $this->client->request($method, $this->prefix . $endpoint, $options);
            $data     = json_decode($response->getBody()->getContents(), true) ?? [];
            $status   = $response->getStatusCode();
        } catch (RequestException $e) {
            
            $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $body  = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            $data = json_decode($body, true) ?? null;
            if ($data && $status >= 400 && ($data['resultInfo']['message'] ?? false) != false ) {
                return $this->formatApiError($data['resultInfo']['message'], $status, ['raw' => $data]);
            }
            
            return $this->formatApiError('İstek sırasında bir hata oluştu', $status, ['raw' => $body]);
        }

        if ($status >= 400 || !($data['resultInfo']['isSuccess'] ?? false)) {
            $message = $data['resultInfo']['message'] ?? $response->getReasonPhrase() ?? 'Bilinmeyen API hatası';
            return $this->formatApiError($message, $status, $data, $data['resultInfo'] ?? []);
        }

        return [
            'success' => true,
            'data'    => $data['resultObject'] ?? [],
            'info'    => $data['resultInfo'] ?? [],
        ];
    }

    private function formatApiError(string $message, int $status, array $data, array $resultInfo = []): array
    {
        if ($this->throw) {
            throw new RuntimeException("API/HTTP hatası: {$message}");
        }

        return [
            'success'    => false,
            'error'      => $message,
            'status'     => $status,
            'body'       => $data,
            'resultInfo' => $resultInfo,
        ];
    }

    public function commissionCalculate(string $amount, string $transferType = '1', ?string $accountId = null): array
    {
        // return $this->request('Transfer/CommissionCalculate', 'POST', [
        //     'accountId' => "D1F6ABF4-0571-4CAB-AD1A-B739F73D2706",
        //     'amount' => "5.00",
        //     'transferType' => "1",
        // ]);
        return $this->request('Transfer/CommissionCalculate', 'POST', [
            'accountId' => is_null($accountId) ? $this->account_id : $accountId,
            'amount' => $amount,
            'transferType' => $transferType,
        ]);
    }

    public function transferableAmountCalculate(string $transferType = '1', ?string $accountId = null): array
    {
        return $this->request('Transfer/TransferableAmountCalculate', 'GET', [
            'accountId' => is_null($accountId) ? $this->account_id : $accountId,
            'transferType' => $transferType,
        ]);
    }

    public function eftStart(array $params): array
    {
        $requiredKeys = [
            'senderAccountId', 'transferType', 'amount', 'receiverAccountNo',
            'receiverName', 'currency', 'description',
            'fastTransferLocation', 'fastTransferType',
            // 'kolasQueryRefNo', 'kolasAddres',
        ];
        if(!isset($params['senderAccountId'])) $params['senderAccountId'] = $this->account_id;

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $params)) {
                throw new \InvalidArgumentException("Eksik parametre: {$key}");
            }
        }

        return $this->request('Transfer/Start', 'POST', $params);
    }

    public function getConfig() 
    {
        return config('param_em_api');
    }
}
