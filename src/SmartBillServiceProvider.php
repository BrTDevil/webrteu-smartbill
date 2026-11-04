<?php

declare(strict_types=1);

namespace WeBRTeu\SmartBill;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use WeBRTeu\Invoicing\InvoiceManager;
use WeBRTeu\SmartBill\Commands\SeriesCommand;
use WeBRTeu\SmartBill\Commands\TaxesCommand;

class SmartBillServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/smartbill.php', 'smartbill');

        $this->app->singleton(SmartBillClient::class);
        $this->app->singleton(SmartBillProvider::class);

        $this->callAfterResolving(InvoiceManager::class, function (InvoiceManager $invoicing): void {
            $invoicing->extend('smartbill', fn (Container $app): SmartBillProvider => $app->make(SmartBillProvider::class));
        });
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/smartbill.php' => $this->app->configPath('smartbill.php')], 'smartbill-config');

        $this->commands([SeriesCommand::class, TaxesCommand::class]);
    }
}
