<?php
// tests/test_mpesa.php

namespace Fleetbase\Storefront\Tests;

use Fleetbase\Storefront\Support\MpesaStkpush;
use PHPUnit\Framework\TestCase;

class MpesaStkpushTest extends TestCase
{
    public function test_lipa_na_mpesa()
    {
        $config = new stdClass();
        $config->short_code = '174379';
        $config->consumer_key = 'pTICJpUNJpftgdSkeNbunPnqLItgMbyVqALYilUj0myqoVY2';
        $config->consumer_secret = 'JVYdCApArQANIQ6yj32weUhBxEd78aFHnoGBLWg90SWUzKRme8SZVbJazD7gS5Eo';
        $config->passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';
        $config->callback_url = 'https://2679-102-0-6-10.ngrok-free.app/Public/mobile_callback_url.php';
        $config->env = 'sandbox'; // or 'live'

        $mpesa = new MpesaStkpush($config);

        $amount = 1;
        $phone = '254796280700';
        $accountReference = 'your_account_reference';

        $response = $mpesa->lipaNaMpesa($amount, $phone, $accountReference);

        $this->assertNotNull($response);
    }
}