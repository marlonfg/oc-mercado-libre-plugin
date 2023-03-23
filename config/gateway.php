<?php

return [
    'default_gateway'=>'meli',
    'flag_msg'=>'api_action',
    'configured'=>[
        'meli'=>[
            'site_id'=>'MLU',
            'sync'=>[
                'class'=> MarlonFreire\MercadoLibre\App\Gateways\MercadoLibre\MercadoLibre::class,
                'job'=> MarlonFreire\MercadoLibre\App\Jobs\MercadoLibreSyncJob::class,
                'config_file'=>'meli.php',
                'bindings'=>[
                    'backend/lovata/shopaholic/products/create/*',
                    'backend/lovata/shopaholic/products/update/*',
//                    'backend/lovata/shopaholic/products/toggle/*',
                    'backend/lovata/shopaholic/products/delete/*',
//                    'backend/lovata/shopaholic/products/media/*',
//                    'admin/productos/mercadolibre/sync/*',
//                    'admin/configuracion/editar/save',
//                    'admin/melicategorias/mercadolibre/sync/*',
                ]
            ],
            'payment'=>[
                'class'=> MarlonFreire\MercadoLibre\App\Gateways\MercadoLibre\MercadoPago::class,
                'config_file'=>'wo2\meli.php',
                'bindings'=>['carrito/comprar']
            ]
        ]
    ]
];
