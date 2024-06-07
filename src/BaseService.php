<?php

namespace Gourish7\Aliceblue;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class BaseService
{
    protected $client;
    protected $baseApiUrl;
    protected $getEncKeyUrl;
    protected $getUserSIDUrl;

    public function __construct()
    {
        $this->client = new Client();
        $this->baseApiUrl = ApiEndpoints::BASE_API_URL;
        $this->getEncKeyUrl = ApiEndpoints::GET_API_ENC_KEY;
        $this->getUserSIDUrl = ApiEndpoints::GET_USER_SID;
    }

    public function login($credential)
    {
        try {
            $encKey = $this->fetchEncKey($credential['user_id']);
            return $this->getSessionId($credential, $encKey);
        } catch (\Exception $e) {
            // Handle exceptions (e.g., log errors)
            return null;
        }
    }

    private function fetchEncKey($userId)
    {
        $headers = ['Content-Type' => 'application/json'];
        $encKeyRequest = new Request(
            'POST',
            $this->baseApiUrl . $this->getEncKeyUrl,
            $headers,
            json_encode(['userId' => $userId])
        );

        $encKeyResponse = $this->client->sendAsync($encKeyRequest)->wait();
        $encKeyData = json_decode($encKeyResponse->getBody(), true);

        if (isset($encKeyData['encKey'])) {
            return $encKeyData['encKey'];
        } else {
            throw new \Exception("Missing encKey");
        }
    }

    private function getSessionId($credential, $encKey)
    {
        $checksum = $credential['user_id'] . $credential['api_key'] . $encKey;
        $hashedKey = hash('sha256', $checksum);

        $headers = ['Content-Type' => 'application/json'];
        $sessionRequest = new Request(
            'POST',
            $this->baseApiUrl . $this->getUserSIDUrl,
            $headers,
            json_encode([
                'userId' => $credential['user_id'],
                'userData' => $hashedKey,
            ])
        );

        $sessionResponse = $this->client->sendAsync($sessionRequest)->wait();
        $accessTokenResponse = json_decode($sessionResponse->getBody(), true);

        return strcasecmp($accessTokenResponse['stat'], 'Ok') === 0 ? $accessTokenResponse['sessionID'] : null;
    }

    protected function sendRequestToApi($credential, $method, $endPoint, $body = [])
    {
        $headers = $this->getLoggedInUserHeader($credential);
        $request = new Request(
            $method,
            $this->baseApiUrl . $endPoint,
            $headers,
            json_encode($body)
        );

        $res = $this->client->sendAsync($request)->wait();
        return json_decode($res->getBody()->getContents(), true);
    }

    private function getLoggedInUserHeader($credential)
    {
        return [
            'Authorization' => 'Bearer ' . $credential['user_id'] . ' ' . $credential['access_token'],
            'Content-Type' => 'application/json',
        ];
    }
}
