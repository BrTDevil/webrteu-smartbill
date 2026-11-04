<?php

declare(strict_types=1);

namespace WeBRTeu\SmartBill\Facades;

use Illuminate\Support\Facades\Facade;
use WeBRTeu\SmartBill\SmartBillProvider;

/**
 * @method static string pdf(\WeBRTeu\Invoicing\Models\Invoice $invoice)
 * @method static void delete(\WeBRTeu\Invoicing\Models\Invoice $invoice)
 * @method static void restore(\WeBRTeu\Invoicing\Models\Invoice $invoice)
 * @method static array{total: int, paid: int, unpaid: int} paymentStatus(\WeBRTeu\Invoicing\Models\Invoice $invoice)
 *
 * @see SmartBillProvider
 */
class SmartBill extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SmartBillProvider::class;
    }
}
