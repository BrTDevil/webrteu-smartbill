<?php

declare(strict_types=1);

return [

    /*
    | SmartBill → Contul meu → Integrări → API: emailul contului și token-ul.
    */
    'username' => env('SMARTBILL_USERNAME'),
    'token' => env('SMARTBILL_TOKEN'),

    /*
    | CIF-ul firmei din SmartBill (cu sau fără RO, cum apare în cont).
    */
    'cif' => env('SMARTBILL_CIF'),

    'base_url' => env('SMARTBILL_BASE_URL', 'https://ws.smartbill.ro/SBORO/api'),

    /*
    | Seriile definite în SmartBill (php artisan smartbill:series).
    */
    'series' => [
        'fiscal' => env('SMARTBILL_SERIES_FISCAL'),
        'proforma' => env('SMARTBILL_SERIES_PROFORMA'),
    ],

    /*
    | Cota TVA → denumirea cotei din contul SmartBill (php artisan smartbill:taxes).
    | La neplătitorii de TVA (invoicing.seller.vat_payer = false) se trimite „Neplatitor TVA”.
    */
    'taxes' => [
        21 => 'Normala',
        11 => 'Redusa',
        0 => 'Scutita',
    ],

    /*
    | Marchează factura fiscală încasată la emitere (ex. plată online deja confirmată).
    | Tip: Card | Ordin de plata | Numerar | Alta ...
    */
    'payment' => [
        'collect' => (bool) env('SMARTBILL_COLLECT_PAYMENT', false),
        'type' => env('SMARTBILL_PAYMENT_TYPE', 'Card'),
    ],

    /*
    | SmartBill trimite el emailul cu factura clientului (din contul SmartBill).
    */
    'send_email' => (bool) env('SMARTBILL_SEND_EMAIL', false),

    /*
    | Gestiunea, doar dacă folosești stocuri în SmartBill.
    */
    'warehouse' => env('SMARTBILL_WAREHOUSE'),

    'timeout' => (int) env('SMARTBILL_TIMEOUT', 30),

];
