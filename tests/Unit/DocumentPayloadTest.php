<?php

declare(strict_types=1);

use WeBRTeu\Invoicing\Data\InvoiceData;
use WeBRTeu\Invoicing\Data\InvoiceLineData;
use WeBRTeu\Invoicing\Data\PartyData;
use WeBRTeu\Invoicing\Enums\InvoiceType;
use WeBRTeu\SmartBill\Requests\DocumentPayload;

$taxes = [21 => 'Normala', 11 => 'Redusa'];

it('builds an invoice for a VAT payer, prices in lei with VAT included', function () use ($taxes): void {
    $data = invoiceData()->withSeller(new PartyData('Firma', 'RO1', vatPayer: true))->withDates(new DateTimeImmutable('2026-11-04'), new DateTimeImmutable('2026-11-19'));

    expect(DocumentPayload::make($data, 'RO12345678', 'FCT', $taxes))->toBe([
        'companyVatCode' => 'RO12345678',
        'client' => [
            'name' => 'Client SRL', 'vatCode' => 'RO987654', 'regCom' => 'J12/3/2021', 'address' => 'Str. Mare 2',
            'city' => 'Cluj-Napoca', 'county' => 'Cluj', 'country' => 'Romania', 'email' => 'client@srl.ro', 'isTaxPayer' => true, 'saveToDb' => false,
        ],
        'issueDate' => '2026-11-04',
        'dueDate' => '2026-11-19',
        'seriesName' => 'FCT',
        'isDraft' => false,
        'currency' => 'RON',
        'language' => 'RO',
        'precision' => 2,
        'mentions' => 'Mulțumim!',
        'observations' => 'Comanda CMD-123',
        'sendEmail' => false,
        'useStock' => false,
        'products' => [
            ['name' => 'Tricou', 'code' => 'T-1', 'isService' => false, 'measuringUnitName' => 'buc', 'currency' => 'RON', 'quantity' => 2.0, 'price' => 50.0, 'isTaxIncluded' => true, 'taxName' => 'Normala', 'taxPercentage' => 21, 'saveToDb' => false],
            ['name' => 'Livrare', 'isService' => true, 'measuringUnitName' => 'buc', 'currency' => 'RON', 'quantity' => 1.0, 'price' => 15.0, 'isTaxIncluded' => true, 'taxName' => 'Normala', 'taxPercentage' => 21, 'saveToDb' => false],
        ],
    ]);
});

it('marks a non-VAT payer seller and an individual buyer', function () use ($taxes): void {
    $data = invoiceData(buyer: new PartyData('Ion Popescu', country: 'MD'))->withSeller(new PartyData('PFA', 'RO2'));
    $payload = DocumentPayload::make($data, 'RO2', 'FCT', $taxes);

    expect($payload['client'])->toBe(['name' => 'Ion Popescu', 'country' => 'MD', 'isTaxPayer' => false, 'saveToDb' => false])
        ->and($payload['products'][0])->toMatchArray(['isTaxIncluded' => false, 'taxName' => 'Neplatitor TVA', 'taxPercentage' => 0])
        ->and($payload)->not->toHaveKey('dueDate');
});

it('collects the payment on fiscal invoices only, with stock and email options', function () use ($taxes): void {
    $line = new InvoiceLineData('Carte', 3000, vatRate: 5, priceIncludesVat: false);
    $data = new InvoiceData(InvoiceType::Fiscal, new PartyData('A'), [$line], seller: new PartyData('S', vatPayer: true));
    $payload = DocumentPayload::make($data, 'RO1', 'FCT', $taxes, ['collect' => true, 'type' => 'Numerar'], true, 'Depozit');
    $proforma = DocumentPayload::make(new InvoiceData(InvoiceType::Proforma, new PartyData('A'), [$line]), 'RO1', 'PRF', [], ['collect' => true]);

    expect($payload['payment'])->toBe(['value' => 31.5, 'type' => 'Numerar', 'isCash' => true])
        ->and($payload)->toMatchArray(['sendEmail' => true, 'useStock' => true])
        ->and($payload['products'][0])->toMatchArray(['price' => 30.0, 'isTaxIncluded' => false, 'taxPercentage' => 5, 'warehouseName' => 'Depozit'])
        ->and($payload['products'][0])->not->toHaveKey('taxName')
        ->and($proforma)->not->toHaveKey('payment')
        ->and($proforma)->not->toHaveKey('observations');
});

it('defaults the payment type to Card', function (): void {
    $payload = DocumentPayload::make(invoiceData(), 'RO1', 'FCT', [], ['collect' => true]);

    expect($payload['payment'])->toBe(['value' => 115.0, 'type' => 'Card', 'isCash' => false]);
});
