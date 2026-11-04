<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use WeBRTeu\Invoicing\Enums\InvoiceStatus;
use WeBRTeu\Invoicing\Enums\InvoiceType;
use WeBRTeu\Invoicing\Facades\Invoicing;
use WeBRTeu\Invoicing\Models\Invoice;
use WeBRTeu\SmartBill\Exceptions\SmartBillException;
use WeBRTeu\SmartBill\SmartBillServiceProvider;

it('issues a fiscal invoice and stores the SmartBill series and number', function (): void {
    Http::fake([SMARTBILL.'/invoice' => Http::response(fixtureFile('invoice-created.json'))]);

    $invoice = Invoicing::issue(invoiceData());

    expect($invoice)->provider->toBe('smartbill')->status->toBe(InvoiceStatus::Issued)
        ->and($invoice->fullNumber())->toBe('FCT-0042');
    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('facturi@firma.ro:tok_secret_123'))
        && $request['seriesName'] === 'FCT'
        && $request['companyVatCode'] === 'RO12345678'
        && $request['products'][0]['taxName'] === 'Normala');
});

it('issues a proforma through /estimate, falling back to the requested series', function (): void {
    Http::fake([SMARTBILL.'/estimate' => Http::response(['number' => '7'])]);

    expect(Invoicing::issue(invoiceData(InvoiceType::Proforma))->fullNumber())->toBe('PRF-7');
});

it('uses the configured warehouse', function (): void {
    Config::set('smartbill.warehouse', 'Depozit');
    Http::fake([SMARTBILL.'/invoice' => Http::response(fixtureFile('invoice-created.json'))]);

    Invoicing::issue(invoiceData());

    Http::assertSent(fn (Request $request): bool => $request['useStock'] === true && $request['products'][0]['warehouseName'] === 'Depozit');
});

it('fails clearly and keeps a Failed invoice', function (array|string $body, int $status, string $message): void {
    Log::spy();
    Http::fake([SMARTBILL.'/invoice' => Http::response($body, $status)]);

    expect(fn (): Invoice => Invoicing::issue(invoiceData()))->toThrow(SmartBillException::class, $message)
        ->and(Invoice::query()->sole()->status)->toBe(InvoiceStatus::Failed);
    Log::shouldNotHaveReceived('warning', [fn (string $m, array $context): bool => str_contains((string) json_encode($context), 'tok_secret_123')]);
})->with([
    'errorText la HTTP 200' => [fn (): string => fixtureFile('error.json'), 200, 'Seria FCT nu exista'],
    'HTTP 401 fără corp' => ['', 401, 'eroare necunoscută'],
    'fără număr' => [['errorText' => ''], 200, 'nu a întors numărul'],
]);

it('requires a SmartBill series', function (): void {
    Config::set('smartbill.series.fiscal');
    Http::fake();

    expect(fn (): Invoice => Invoicing::issue(invoiceData()))->toThrow(SmartBillException::class, 'SMARTBILL_SERIES_FISCAL');
    Http::assertNothingSent();
});

it('registers the provider and the config tag', function (): void {
    expect(Invoicing::available())->toContain('smartbill')
        ->and(ServiceProvider::pathsToPublish(SmartBillServiceProvider::class, 'smartbill-config'))->toHaveCount(1);
});
