<?php

declare(strict_types=1);

use WeBRTeu\Invoicing\Data\InvoiceData;
use WeBRTeu\Invoicing\Data\InvoiceLineData;
use WeBRTeu\Invoicing\Data\PartyData;
use WeBRTeu\Invoicing\Enums\InvoiceType;

const SMARTBILL = 'https://ws.smartbill.ro/SBORO/api';

function fixtureFile(string $name): string
{
    return (string) file_get_contents(__DIR__.'/Fixtures/'.$name);
}

function invoiceData(InvoiceType $type = InvoiceType::Fiscal, ?PartyData $buyer = null): InvoiceData
{
    return new InvoiceData(
        type: $type,
        buyer: $buyer ?? new PartyData('Client SRL', 'RO987654', 'J12/3/2021', 'Str. Mare 2', 'Cluj-Napoca', 'Cluj', email: 'client@srl.ro'),
        lines: [new InvoiceLineData('Tricou', 5000, 2, code: 'T-1'), new InvoiceLineData('Livrare', 1500, isService: true)],
        reference: 'CMD-123',
        issueDate: new DateTimeImmutable('2026-11-04'),
        notes: 'Mulțumim!',
    );
}
