<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Datos de empresa para voucher de Factura PrimeTrack
    |--------------------------------------------------------------------------
    */
    'company' => [
        'name' => env('ACCOUNTING_COMPANY_NAME', 'PrimeTrack Group'),
        'tax_id' => env('ACCOUNTING_COMPANY_TAX_ID', ''),
        'address' => env('ACCOUNTING_COMPANY_ADDRESS', ''),
        'phones' => env('ACCOUNTING_COMPANY_PHONES', ''),
        'footer' => env('ACCOUNTING_VOUCHER_FOOTER', 'Es un gusto atenderle!'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipo de cambio por defecto (córdobas por 1 USD) al emitir factura
    |--------------------------------------------------------------------------
    */
    'default_exchange_rate' => (float) env('ACCOUNTING_EXCHANGE_RATE', 36.6243),

    'folio_prefix' => env('ACCOUNTING_FOLIO_PREFIX', 'FP-'),
];
