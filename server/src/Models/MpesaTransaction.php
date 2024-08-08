<?php

namespace Fleetbase\Storefront\Models;

use Fleetbase\Models\Model;
use Fleetbase\Traits\HasUuid;

class MpesaTransaction extends StorefrontModel
{
    use HasUuid;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'mpesa_transactions';

    /**
     * These attributes that can be queried.
     *
     * @var array
     */
    protected $searchableColumns = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'merchant_request_id', 'checkout_request_id', 'amount', 'mpesa_receipt_number',
        'transaction_date', 'phone_number', 'status',
    ];

}
