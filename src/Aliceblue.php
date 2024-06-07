<?php

namespace Gourish7\Aliceblue;

class Aliceblue extends BaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getProfile($credential)
    {
        return $this->sendRequestToApi($credential, "GET", ApiEndpoints::ACCOUNT_DETAILS);
    }

    public function getPositions($credential, $request)
    {
        $body = ["ret" => $request['ret']];
        return $this->sendRequestToApi($credential, "POST", ApiEndpoints::POSITION_BOOK, $body);
    }

    public function getScripInfo($credential, $request)
    {
        $body = $request;
        if(is_array($request)){
            $body = ["exch" => $request['exchange'], "symbol" => $request['token']];
        }
        return $this->sendRequestToApi($credential, "POST", ApiEndpoints::SCRIP_QUOTE_DETAILS, $body);
    }
}
