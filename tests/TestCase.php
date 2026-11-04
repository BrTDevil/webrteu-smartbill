<?php

declare(strict_types=1);

namespace WeBRTeu\SmartBill\Tests;

use Illuminate\Database\Migrations\Migration;
use Orchestra\Testbench\TestCase as Orchestra;
use WeBRTeu\Invoicing\InvoicingServiceProvider;
use WeBRTeu\SmartBill\SmartBillServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [InvoicingServiceProvider::class, SmartBillServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $app['config']->set('invoicing.default', 'smartbill');
        $app['config']->set('invoicing.seller', ['name' => 'Firma Mea SRL', 'cui' => 'RO12345678', 'vat_payer' => true]);
        $app['config']->set('smartbill.username', 'facturi@firma.ro');
        $app['config']->set('smartbill.token', 'tok_secret_123');
        $app['config']->set('smartbill.cif', 'RO12345678');
        $app['config']->set('smartbill.series', ['fiscal' => 'FCT', 'proforma' => 'PRF']);
    }

    protected function defineDatabaseMigrations(): void
    {
        /** @var Migration $migration */
        $migration = require __DIR__.'/../vendor/webrteu/invoicing/database/migrations/create_invoices_tables.php.stub';
        $migration->up();
    }
}
