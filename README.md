# webrteu/smartbill

[![PHP](https://img.shields.io/badge/PHP-%5E8.2-777bb4)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-12%E2%80%9313-ff2d20)](https://laravel.com/)
[![Licență MIT](https://img.shields.io/badge/licen%C8%9B%C4%83-MIT-green)](LICENSE.md)
[![Packagist](https://img.shields.io/packagist/v/webrteu/smartbill)](https://packagist.org/packages/webrteu/smartbill)

Furnizorul **SmartBill Cloud** pentru `webrteu/invoicing`: facturile fiscale și proformele se emit în contul SmartBill,
cu seria și numărul de acolo, iar PDF-ul poate fi al SmartBill sau cel din șablonul `webrteu/invoicing`. Mai are
anulare, restaurare, ștergere și statusul încasării.

Nu este un pachet oficial SmartBill.

> **Versiunea 2.x e o rescriere.** 1.x era legată de aplicația Auditor. Aplicațiile pe 1.x rămân pe `^1.0`.

## Cerințe

- PHP `^8.2`, Laravel 12–13
- cont SmartBill Cloud cu acces API
- `webrteu/invoicing` ^2.0

## Instalare

```bash
composer require webrteu/smartbill
php artisan vendor:publish --tag=smartbill-config
php artisan smartbill:series     # seriile din cont
php artisan smartbill:taxes      # cotele de TVA din cont
```

## Configurare

```dotenv
INVOICING_PROVIDER=smartbill

SMARTBILL_USERNAME=facturi@firma.ro   # emailul contului SmartBill
SMARTBILL_TOKEN=                      # SmartBill → Contul meu → Integrări → API
SMARTBILL_CIF=RO12345678              # CIF-ul firmei, cum apare în SmartBill
SMARTBILL_SERIES_FISCAL=FCT           # seria de facturi (smartbill:series)
SMARTBILL_SERIES_PROFORMA=PRF         # seria de proforme (smartbill:series --type=p)
SMARTBILL_COLLECT_PAYMENT=false       # true = factura fiscală apare încasată (plăți online confirmate)
SMARTBILL_PAYMENT_TYPE=Card           # Card | Ordin de plata | Numerar | Alta
SMARTBILL_SEND_EMAIL=false            # true = SmartBill trimite el factura clientului
SMARTBILL_WAREHOUSE=                  # gestiunea, doar dacă descarci stoc în SmartBill
SMARTBILL_TIMEOUT=30
```

Cotele de TVA se trimit după denumirea lor din contul tău (`smartbill.taxes`, implicit `21 => Normala`,
`11 => Redusa`, `0 => Scutita`). Verifică denumirile cu `php artisan smartbill:taxes`. Pentru neplătitorii de TVA
(`INVOICING_SELLER_VAT_PAYER=false`) se trimite `Neplatitor TVA`, ca în versiunea 1.x din producție.

## Utilizare

Totul trece prin `webrteu/invoicing`:

```php
$invoice = Invoicing::issue($invoiceData, invoiceable: $order);   // POST /invoice (fiscală) sau /estimate (proformă)
$invoice->fullNumber();                                           // "FCT-0042", de la SmartBill

Invoicing::pdf($invoice, 'local');      // PDF-ul tău (șablonul webrteu/invoicing), cu numărul SmartBill
Invoicing::pdf($invoice, 'provider');   // PDF-ul SmartBill
Invoicing::cancel($invoice);            // PUT /invoice/cancel
```

| Metodă | SmartBill |
|---|---|
| `SmartBill::pdf($invoice)` | `GET /invoice/pdf` sau `/estimate/pdf` |
| `SmartBill::restore($invoice)` | `PUT /invoice/restore` |
| `SmartBill::delete($invoice)` | `DELETE /invoice` (doar ultimul document din serie) |
| `SmartBill::paymentStatus($invoice)` | `GET /invoice/paymentstatus`, cu sumele în bani |

Prețurile se trimit în lei cu 2 zecimale. Erorile SmartBill (inclusiv `errorText` la HTTP 200) devin
`SmartBillException`, iar factura rămâne `Failed` în `invoices`. Cererile de emitere nu se reîncearcă automat.
Token-ul nu apare în loguri. Limita SmartBill e de 30 de apeluri la 10 secunde.

⚠️ Căile `/invoice/pdf`, `/invoice/cancel`, `/invoice/restore` și `/invoice/paymentstatus` sunt scrise după documentația
api.smartbill.ro și trebuie confirmate pe contul real.

## Comenzi Artisan

- `smartbill:series {--type=f}`: seriile (`f` facturi, `p` proforme, `c` chitanțe)
- `smartbill:taxes`: cotele de TVA

## Ce nu face pachetul

- e-Factura: SmartBill o poate trimite automat în SPV din setările contului; integrarea proprie va fi un pachet separat
- nomenclatorul de produse și clienți din SmartBill (trimite `saveToDb: false`)
- chitanțe și încasări ulterioare emiterii

## Testare

```bash
composer test
```

Fixture-urile sunt scrise după documentația SmartBill. **De înlocuit cu răspunsuri reale.**

## Changelog

Vezi [CHANGELOG.md](CHANGELOG.md).

## Autor

[WeBRTeu](https://webrt.eu)

## Licență

MIT. Vezi [LICENSE.md](LICENSE.md).
