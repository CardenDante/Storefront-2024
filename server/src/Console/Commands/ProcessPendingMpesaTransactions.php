<?php

namespace Fleetbase\Storefront\Console\Commands;

use Fleetbase\Storefront\Models\Gateway;
use Fleetbase\Storefront\Models\MpesaTransaction;
use Fleetbase\Storefront\Support\MpesaStkpush;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessPendingMpesaTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mpesa:process-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processes pending mpesa transactions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $transactions   = $this->getPendingTransactions();

        $gateway        = Gateway::where('code', 'mpesa_stk')->first();
        $mpesaService   = new MpesaStkpush($gateway->config);

        foreach ($transactions as $transaction) {

            // query transaction
            $queryResponse = $mpesaService->queryTransaction($transaction->checkout_request_id);

            if (!$queryResponse ) {
                $this->error('Failed to query stk push transaction');
            }

            // update mpesa_transaction record
            if ($queryResponse['ResultCode'] == 0) {
               
                MpesaTransaction::updateOrCreate([
                    'merchant_request_id'   => $transaction->merchant_request_id, 
                    'checkout_request_id'   => $transaction->checkout_request_id, 
                ], [
                    'status'                => 'SUCCESS',
                ]);

            }
            else {

                MpesaTransaction::updateOrCreate([
                    'merchant_request_id'   => $transaction->merchant_request_id, 
                    'checkout_request_id'   => $transaction->checkout_request_id, 
                ], [
                    'status'                => 'FAILED',
                ]);

            }

            Log::info('Resp', [ $queryResponse ]);
        }
    }

    /**
     * Fetches pending mpesa transactions
     */
    public function getPendingTransactions()
    {
        return MpesaTransaction::where('status', 'PENDING')->get();
    }
}