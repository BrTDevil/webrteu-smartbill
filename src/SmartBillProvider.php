<?php

declare(strict_types=1);

namespace WeBRTeu\SmartBill;

use Illuminate\Support\Facades\Config;
use WeBRTeu\Invoicing\Contracts\InvoiceProvider;
use WeBRTeu\Invoicing\Contracts\ProvidesPdf;
use WeBRTeu\Invoicing\Data\InvoiceData;
use WeBRTeu\Invoicing\Data\IssuedInvoiceData;
use WeBRTeu\Invoicing\Enums\InvoiceType;
use WeBRTeu\Invoicing\Models\Invoice;
use WeBRTeu\SmartBill\Exceptions\SmartBillException;
use WeBRTeu\SmartBill\Requests\DocumentPayload;

/**
 * Facturile fiscale → /invoice, proformele → /estimate. Seria și numărul vin de la SmartBill.
 * PDF-ul poate fi cel din SmartBill (pdf()) sau cel din șablonul webrteu/invoicing (pdf.source = local).
 */
class SmartBillProvider implements InvoiceProvider, ProvidesPdf
{
    public function __construct(private readonly SmartBillClient $client) {}

    public function name(): string
    {
        return 'smartbill';
    }

    public function issue(InvoiceData $invoice): IssuedInvoiceData
    {
        $series = $invoice->series ?? Config::get('smartbill.series.'.$invoice->type->value);

        if (! is_string($series) || $series === '') {
            throw new SmartBillException(sprintf('Lipsește seria SmartBill pentru „%s” (SMARTBILL_SERIES_%s).', $invoice->type->value, strtoupper($invoice->type->value)));
        }

        $warehouse = Config::get('smartbill.warehouse');

        /** @var array{collect?: bool, type?: string} $payment */
        $payment = Config::array('smartbill.payment', []);

        $json = $this->client->send('POST', $this->resource($invoice->type), body: DocumentPayload::make(
            $invoice,
            $this->client->cif(),
            $series,
            Config::array('smartbill.taxes', []),
            $payment,
            Config::boolean('smartbill.send_email', false),
            is_string($warehouse) && $warehouse !== '' ? $warehouse : null,
        ));

        $number = is_scalar($json['number'] ?? null) ? (string) $json['number'] : '';

        if ($number === '') {
            throw new SmartBillException('SmartBill nu a întors numărul documentului.');
        }

        return new IssuedInvoiceData(
            series: is_scalar($json['series'] ?? null) && (string) $json['series'] !== '' ? (string) $json['series'] : $series,
            number: $number,
            raw: $json,
        );
    }

    /**
     * Anulează documentul în SmartBill (rămâne în evidență, marcat anulat).
     */
    public function cancel(Invoice $invoice): void
    {
        $this->client->send('PUT', $this->resource($invoice->type).'/cancel', $this->reference($invoice));
    }

    /**
     * Șterge documentul din SmartBill. Doar ultimul document din serie poate fi șters.
     */
    public function delete(Invoice $invoice): void
    {
        $this->client->send('DELETE', $this->resource($invoice->type), $this->reference($invoice));
    }

    /**
     * Readuce o factură anulată.
     */
    public function restore(Invoice $invoice): void
    {
        $this->client->send('PUT', $this->resource($invoice->type).'/restore', $this->reference($invoice));
    }

    public function pdf(Invoice $invoice): string
    {
        return $this->client->download($this->resource($invoice->type).'/pdf', $this->reference($invoice));
    }

    /**
     * Cât s-a încasat dintr-o factură fiscală, în bani.
     *
     * @return array{total: int, paid: int, unpaid: int}
     */
    public function paymentStatus(Invoice $invoice): array
    {
        $json = $this->client->send('GET', '/invoice/paymentstatus', $this->reference($invoice));
        $bani = fn (string $key): int => is_numeric($json[$key] ?? null) ? (int) round((float) $json[$key] * 100) : 0;

        return ['total' => $bani('invoiceTotalAmount'), 'paid' => $bani('paidAmount'), 'unpaid' => $bani('unpaidAmount')];
    }

    private function resource(InvoiceType $type): string
    {
        return $type === InvoiceType::Proforma ? '/estimate' : '/invoice';
    }

    /**
     * @return array<string, string>
     */
    private function reference(Invoice $invoice): array
    {
        return ['cif' => $this->client->cif(), 'seriesname' => (string) $invoice->series, 'number' => (string) $invoice->number];
    }
}
