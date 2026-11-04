<?php

declare(strict_types=1);

namespace WeBRTeu\SmartBill\Commands;

use Illuminate\Console\Command;
use WeBRTeu\SmartBill\SmartBillClient;

class TaxesCommand extends Command
{
    protected $signature = 'smartbill:taxes';

    protected $description = 'Afișează cotele de TVA din contul SmartBill (pentru smartbill.taxes)';

    public function handle(SmartBillClient $client): int
    {
        $json = $client->send('GET', '/tax', ['cif' => $client->cif()]);
        $rows = [];

        foreach ((array) ($json['taxes'] ?? []) as $tax) {
            if (is_array($tax)) {
                $rows[] = array_map(fn (string $key): string => is_scalar($tax[$key] ?? null) ? (string) $tax[$key] : '', ['name', 'percentage']);
            }
        }

        $this->table(['Denumire', 'Cotă'], $rows);

        return self::SUCCESS;
    }
}
