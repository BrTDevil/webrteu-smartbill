<?php

declare(strict_types=1);

use WeBRTeu\Invoicing\Exceptions\InvoicingException;

arch('nu folosește funcții de debug')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'die'])
    ->not->toBeUsed();

arch('toate fișierele au strict types')
    ->expect('WeBRTeu\SmartBill')
    ->toUseStrictTypes();

arch('fără Guzzle direct: HTTP doar prin Http::')
    ->expect('WeBRTeu\SmartBill')
    ->not->toUse('GuzzleHttp');

arch('excepțiile extind InvoicingException')
    ->expect('WeBRTeu\SmartBill\Exceptions')
    ->toExtend(InvoicingException::class);
