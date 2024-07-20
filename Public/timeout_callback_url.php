<?php

use Illuminate\Support\Facades\Log;

require_once __DIR__ . '/../vendor/autoload.php';

Log::info('Mpesa Timeout Callback received');

$data = json_decode(file_get_contents('php://input'), true);

file_put_contents(__DIR__ . '/../error_log.txt', "Mpesa Timeout Callback Data: " . print_r($data, true) . "\n", FILE_APPEND);

Log::info('Mpesa Timeout Callback Data:', $data);

// Handle timeout specific logic here
Log::error('Transaction timed out or failed', $data);

echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
