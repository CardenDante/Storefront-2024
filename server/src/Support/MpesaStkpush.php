<?php

namespace Fleetbase\Storefront\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaStkpush
{
    protected $short_code;
    protected $consumer_key;
    protected $consumer_secret;
    protected $passkey;
    protected $callback_url;
    protected $env;

    public function __construct($config)
    {
        $this->short_code = $config['short_code'];
        $this->consumer_key = $config['consumer_key'];
        $this->consumer_secret = $config['consumer_secret'];
        $this->passkey = $config['passkey'];
        $this->callback_url = $config['callback_url'];
        $this->env = $config['env']; // 'sandbox' or 'live'
    }

    protected function getAccessToken()
    {
        $access_token_url = ($this->env === 'live') ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials' : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($this->consumer_key . ':' . $this->consumer_secret),
        ])->get($access_token_url);

        if ($response->failed()) {
            Log::error('Failed to get access token', ['response' => $response->body()]);
            file_put_contents(__DIR__ . '/../../error_log.txt', "Failed to get access token: " . $response->body() . "\n", FILE_APPEND);
            return null;
        }

        return $response->json()['access_token'];
    }

    public function lipaNaMpesa($amount, $phone, $accountReference)
    {
        $timestamp = date('YmdHis');
        $password = base64_encode($this->short_code . $this->passkey . $timestamp);
        $access_token = $this->getAccessToken();

        if (!$access_token) {
            return null;
        }

        $stk_push_url = ($this->env === 'live') ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest' : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json',
        ])->post($stk_push_url, [
            'BusinessShortCode' => $this->short_code,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $phone,
            'PartyB' => $this->short_code,
            'PhoneNumber' => $phone,
            'CallBackURL' => $this->callback_url,
            'AccountReference' => $accountReference,
            'TransactionDesc' => 'Payment for ' . $accountReference,
        ]);

        if ($response->failed()) {
            Log::error('STK Push request failed', ['response' => $response->body()]);
            file_put_contents(__DIR__ . '/../../error_log.txt', "STK Push request failed: " . $response->body() . "\n", FILE_APPEND);
            return null;
        }

        return $response->json();
    }
}
