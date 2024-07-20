<?php

use Illuminate\Support\Facades\Log;

require_once __DIR__ . '/../vendor/autoload.php';

Log::info('Mpesa Callback received');

$data = json_decode(file_get_contents('php://input'), true);

file_put_contents(__DIR__ . '/../error_log.txt', "Mpesa Callback Data: " . print_r($data, true) . "\n", FILE_APPEND);

if (isset($data['Body']['stkCallback'])) {
    $stkCallback = $data['Body']['stkCallback'];
    $resultCode = $stkCallback['ResultCode'];
    $resultDesc = $stkCallback['ResultDesc'];
    $metadata = $stkCallback['CallbackMetadata']['Item'];

    Log::info('Mpesa Callback Data:', $data);

    if ($resultCode == 0) {
        // Transaction successful
        $transactionId = $metadata[1]['Value'];
        $amount = $metadata[0]['Value'];
        $phoneNumber = $metadata[4]['Value'];

        // Update your database with the transaction details
        // Example:
        // Payment::where('transaction_id', $transactionId)->update(['status' => 'completed']);

        Log::info('Transaction successful', [
            'transactionId' => $transactionId,
            'amount' => $amount,
            'phoneNumber' => $phoneNumber
        ]);
    } else {
        // Transaction failed
        Log::error('Transaction failed', ['resultDesc' => $resultDesc]);
    }
} else {
    Log::error('Invalid callback data', $data);
}

echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
