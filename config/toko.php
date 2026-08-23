<?php
return [
    // ID gudang inti - ganti sesuai DB jika berubah, jangan hardcode di controller lagi
    'gudang_ecer'   => env('GUDANG_ECER_ID', 1),
    'gudang_grosir' => env('GUDANG_GROSIR_ID', 2),
    'gudang_terjual'=> env('GUDANG_TERJUAL_ID', 55),
    'printer_name'  => env('PRINTER_NAME', 'POS80'),
    // supplier default jika tidak dipilih
    'default_supplier_id' => env('DEFAULT_SUPPLIER_ID', 1),
];
