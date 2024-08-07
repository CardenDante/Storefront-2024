<?php

namespace Fleetbase\Storefront\Support;

use Exception;
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
        $this->short_code = $config->short_code;
        $this->consumer_key = $config->consumer_key;
        $this->consumer_secret = $config->consumer_secret;
        $this->passkey = $config->passkey;
        $this->callback_url = $config->callback_url;
        $this->env = $config->env; // 'sandbox' or 'live'
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

    protected function formatPhoneNumberForMpesa($phoneNumber)
    {
        // Remove any non-numeric characters
        $phoneNumber = preg_replace('/\D/', '', $phoneNumber);

        // Check if the phone number starts with '254'
        if (substr($phoneNumber, 0, 3) !== '254') {
            // If the phone number starts with '0', replace it with '254'
            if (substr($phoneNumber, 0, 1) === '0') {
                $phoneNumber = '254' . substr($phoneNumber, 1);
            }
        }

        // Ensure the phone number does not start with '+'
        $phoneNumber = ltrim($phoneNumber, '+');

        // Validate the number starts with '2547' or '2541' and has exactly 12 digits
        if (preg_match('/^254[17][0-9]{8}$/', $phoneNumber)) {
            return $phoneNumber;
        } else {
            // Return false or an error message if the format is incorrect
            return false;
        }
    }

    public function lipaNaMpesa($amount, $phone, $accountReference)
    {
        $timestamp = date('YmdHis');
        $password = base64_encode($this->short_code . $this->passkey . $timestamp);
        $access_token = $this->getAccessToken();

        if (!$access_token) {
            return null;
        }
        // Format the phone number
        $phone = $this->formatPhoneNumberForMpesa($phone);
        if (!$phone) {
            Log::error('Invalid phone number format', ['phone' => $phone]);
            return null;
        }
        $stk_push_url = ($this->env === 'live') ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest' : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';


        $reference = abs(rand(10000,99999));    
        // $reference_one = "Lipagas Limited";
        $reference_two = "Order Payment";
       
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
            'AccountReference' => $reference,
            'TransactionDesc' => $reference_two,

        ]);

        if ($response->failed()) {
            Log::error('STK Push request failed', ['response' => $response->body()]);
            file_put_contents(__DIR__ . '/../../error_log.txt', "STK Push request failed: " . $response->body() . "\n", FILE_APPEND);
            return null;
        }

        return $response->json();
    }

    public function queryTransaction($checkoutRequestId)
    {
        try {

            $query_url = $this->env === 'live' ?
                'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query' :
                'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query';

            $timestamp = date('YmdHis');
            $password  = base64_encode($this->short_code . $this->passkey . $timestamp);

            $access_token = $this->getAccessToken();
            if (!$access_token) {
                throw new Exception('Failed to get access token');
            }

            $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type'  => 'application/json'
                ])
                ->post($query_url, [
                    'BusinessShortCode' => $this->short_code,
                    'Password'          => $password,
                    'Timestamp'         => $timestamp,
                    'CheckoutRequestID' => $checkoutRequestId, 
                ]);

            if ($response->failed()) {
                $error = $response->json()['errorMessage'];
                throw new Exception("StkPushQuery failed: $error");
            }

            return $response->json();
        }
        catch (Exception $e) {
            Log::info('MpesaStkpush::queryTransaction failed');
            Log::error($e);
            return null;
        }
    }

    public static function getMetadataByName($metadata, $name)
    {
        return array_values(
                    array_filter(
                        $metadata, 
                        fn ($item) => $item['Name'] == $name
                    )
                )[0]['Value'];
    }

    public static function parseTimestamp($timestamp) 
    {
        return sprintf(
            "%d-%d-%d %d:%d:%d",
            substr($timestamp, 0, 4),
            ...str_split(substr_replace($timestamp, '', 0, 4), 2)
        );
    }

    public static function formatAmount($amount)
    {
        return round($amount/100);
    }
}
