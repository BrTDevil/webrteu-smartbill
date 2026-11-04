<?php

declare(strict_types=1);

namespace WeBRTeu\SmartBill\Requests;

use WeBRTeu\Invoicing\Data\InvoiceData;
use WeBRTeu\Invoicing\Data\InvoiceLineData;
use WeBRTeu\Invoicing\Data\PartyData;
use WeBRTeu\Invoicing\Enums\InvoiceType;
use WeBRTeu\Invoicing\Support\Money;

/**
 * InvoiceData → corpul POST /invoice (fiscală) sau /estimate (proformă). Prețurile se trimit în lei, cu 2 zecimale.
 */
final class DocumentPayload
{
    public const NON_VAT_PAYER = 'Neplatitor TVA';

    /**
     * @param  array<int|string, mixed>  $taxes  cota → denumirea din SmartBill
     * @param  array{collect?: bool, type?: string}  $payment
     * @return array<string, mixed>
     */
    public static function make(InvoiceData $invoice, string $cif, string $series, array $taxes = [], array $payment = [], bool $sendEmail = false, ?string $warehouse = null): array
    {
        $vatPayer = $invoice->seller instanceof PartyData && $invoice->seller->vatPayer;

        return array_filter([
            'companyVatCode' => $cif,
            'client' => self::client($invoice->buyer),
            'issueDate' => $invoice->issueDate?->format('Y-m-d'),
            'dueDate' => $invoice->dueDate?->format('Y-m-d'),
            'seriesName' => $series,
            'isDraft' => false,
            'currency' => $invoice->currency,
            'language' => strtoupper($invoice->language),
            'precision' => 2,
            'mentions' => $invoice->notes,
            'observations' => $invoice->reference !== null ? 'Comanda '.$invoice->reference : null,
            'sendEmail' => $sendEmail,
            'useStock' => $warehouse !== null,
            'products' => array_map(fn (InvoiceLineData $line): array => self::product($line, $invoice->currency, $vatPayer, $taxes, $warehouse), $invoice->lines),
            'payment' => $invoice->type === InvoiceType::Fiscal && ($payment['collect'] ?? false)
                ? ['value' => Money::toFloat($invoice->total()), 'type' => $payment['type'] ?? 'Card', 'isCash' => ($payment['type'] ?? '') === 'Numerar']
                : null,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    public static function client(PartyData $buyer): array
    {
        return array_filter([
            'name' => $buyer->name,
            'vatCode' => $buyer->cui,
            'regCom' => $buyer->regCom,
            'address' => $buyer->address,
            'city' => $buyer->city,
            'county' => $buyer->county,
            'country' => $buyer->country === 'RO' ? 'Romania' : $buyer->country,
            'email' => $buyer->email,
            'phone' => $buyer->phone,
            'isTaxPayer' => $buyer->isCompany() && str_starts_with(strtoupper((string) $buyer->cui), 'RO'),
            'saveToDb' => false,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /**
     * @param  array<int|string, mixed>  $taxes
     * @return array<string, mixed>
     */
    public static function product(InvoiceLineData $line, string $currency, bool $vatPayer, array $taxes, ?string $warehouse = null): array
    {
        $taxName = $taxes[$line->vatRate] ?? null;

        return array_filter([
            'name' => $line->name,
            'code' => $line->code,
            'isService' => $line->isService,
            'measuringUnitName' => $line->unit,
            'currency' => $currency,
            'quantity' => $line->quantity,
            'price' => Money::toFloat($line->unitPrice),
            'isTaxIncluded' => $vatPayer && $line->priceIncludesVat,
            'taxName' => $vatPayer ? (is_string($taxName) ? $taxName : null) : self::NON_VAT_PAYER,
            'taxPercentage' => $vatPayer ? $line->vatRate : 0,
            'saveToDb' => false,
            'warehouseName' => $warehouse,
        ], fn (mixed $value): bool => $value !== null);
    }
}
