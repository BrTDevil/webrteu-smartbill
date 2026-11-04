<?php

declare(strict_types=1);

namespace WeBRTeu\SmartBill\Commands;

use Illuminate\Console\Command;
use WeBRTeu\SmartBill\SmartBillClient;

class SeriesCommand extends Command
{
    protected $signature = 'smartbill:series {--type=f : f = facturi, p = proforme, c = chitanțe}';

    protected $description = 'Afișează seriile din contul SmartBill (pentru SMARTBILL_SERIES_*)';

    public function handle(SmartBillClient $client): int
    {
        $type = $this->option('type');
        $json = $client->send('GET', '/series', ['cif' => $client->cif(), 'type' => is_string($type) ? $type : 'f']);
        $rows = [];

        foreach ((array) ($json['list'] ?? []) as $series) {
            if (is_array($series)) {
                $rows[] = array_map(fn (string $key): string => is_scalar($series[$key] ?? null) ? (string) $series[$key] : '', ['name', 'nextNumber', 'type']);
            }
        }

        $this->table(['Serie', 'Următorul număr', 'Tip'], $rows);

        return self::SUCCESS;
    }
}
