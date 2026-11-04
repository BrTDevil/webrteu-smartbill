<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('lists the series', function (): void {
    Http::fake([SMARTBILL.'/series*' => Http::response(fixtureFile('series.json'))]);

    $this->artisan('smartbill:series', ['--type' => 'p'])->expectsTable(['Serie', 'Următorul număr', 'Tip'], [['FCT', '43', 'f']])->assertSuccessful();

    Http::assertSent(fn (Request $request): bool => $request->url() === SMARTBILL.'/series?cif=RO12345678&type=p');
});

it('lists the VAT rates', function (): void {
    Http::fake([SMARTBILL.'/tax*' => Http::response(fixtureFile('taxes.json'))]);

    $this->artisan('smartbill:taxes')->expectsTable(['Denumire', 'Cotă'], [['Normala', '21'], ['Redusa', '11']])->assertSuccessful();
});
