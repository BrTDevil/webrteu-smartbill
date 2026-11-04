<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use WeBRTeu\Invoicing\Enums\InvoiceStatus;
use WeBRTeu\Invoicing\Enums\InvoiceType;
use WeBRTeu\Invoicing\Facades\Invoicing;
use WeBRTeu\Invoicing\Models\Invoice;
use WeBRTeu\SmartBill\Exceptions\SmartBillException;
use WeBRTeu\SmartBill\Facades\SmartBill;

function issued(InvoiceType $type = InvoiceType::Fiscal): Invoice
{
    Http::fake([
        SMARTBILL.'/invoice' => Http::response(fixtureFile('invoice-created.json')),
        SMARTBILL.'/estimate' => Http::response(fixtureFile('estimate-created.json')),
    ]);

    return Invoicing::issue(invoiceData($type));
}

beforeEach(function (): void {
    Storage::fake('local');
    Config::set('invoicing.pdf.store', false);
});

it('downloads the SmartBill PDF or renders the own template', function (): void {
    $invoice = issued();
    Http::fake([SMARTBILL.'/invoice/pdf*' => Http::response('%PDF-smartbill', 200, ['Content-Type' => 'application/octet-stream'])]);

    expect(Invoicing::pdf($invoice, 'provider'))->toBe('%PDF-smartbill')
        ->and(Invoicing::pdf($invoice, 'local'))->toStartWith('%PDF-1')
        ->and(Invoicing::html($invoice))->toContain('FCT-0042', 'Client SRL');
    Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), SMARTBILL.'/invoice/pdf?cif=RO12345678&seriesname=FCT&number=0042')
        && $request->hasHeader('Accept', 'application/octet-stream'));
});

it('downloads a proforma PDF from /estimate/pdf', function (): void {
    $invoice = issued(InvoiceType::Proforma);
    Http::fake([SMARTBILL.'/estimate/pdf*' => Http::response('%PDF-proforma')]);

    expect(SmartBill::pdf($invoice))->toBe('%PDF-proforma');
});

it('reports PDF download errors', function (string $body, string $message): void {
    $invoice = issued();
    Http::fake([SMARTBILL.'/invoice/pdf*' => Http::response($body, 404)]);

    expect(fn (): string => SmartBill::pdf($invoice))->toThrow(SmartBillException::class, $message);
})->with([
    ['{"errorText":"Factura nu exista"}', 'Factura nu exista'],
    ['not found', 'eroare necunoscută'],
]);

it('cancels, restores and deletes documents', function (): void {
    $invoice = issued();
    Http::fake([SMARTBILL.'/invoice*' => Http::response(['errorText' => '', 'message' => 'ok'])]);

    expect(Invoicing::cancel($invoice)->status)->toBe(InvoiceStatus::Cancelled);
    SmartBill::restore($invoice);
    SmartBill::delete($invoice);

    Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT' && str_starts_with($request->url(), SMARTBILL.'/invoice/cancel?cif=RO12345678&seriesname=FCT&number=0042'));
    Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT' && str_contains($request->url(), '/invoice/restore?'));
    Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE' && str_starts_with($request->url(), SMARTBILL.'/invoice?cif='));
});

it('reads the payment status in bani', function (): void {
    $invoice = issued();
    Http::fake([SMARTBILL.'/invoice/paymentstatus*' => Http::sequence()->push(fixtureFile('payment-status.json'))->push(['errorText' => ''])]);

    expect(SmartBill::paymentStatus($invoice))->toBe(['total' => 11500, 'paid' => 5050, 'unpaid' => 6450])
        ->and(SmartBill::paymentStatus($invoice))->toBe(['total' => 0, 'paid' => 0, 'unpaid' => 0]);
});
