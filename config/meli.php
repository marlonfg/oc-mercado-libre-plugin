<?php

return [
    'config_model' => MarlonFreire\MercadoLibre\Models\Configuracion::class,
    'use_env' => false,
    'cache' => [
        'key'=>'meli',
        'lifetime'=>60
    ],
    'site_id' => 'MLU',
    'models' => [
        'categories' => [
            'class' => MarlonFreire\MercadoLibre\Models\Categoria::class,
            'table' => 'marlonfreire_mercadolibre_categorias',
            'sincronizable' => false,
            'fields' => [
                'fill' => ['id', 'name'],
                'sync' => ['id', 'name']
            ]
        ],
        'items' => [
            'class' => Lovata\Shopaholic\Models\Product::class,
            'table' => 'lovata_shopaholic_products',
            'sincronizable' => true,
            'fields' => [
                //El orden de los atributos es importante pues es el orden exacto del item de la API para el merge con el model
                'fill' => ['id', 'title', 'condition', 'category_id', 'price', 'base_price', 'currency_id', 'descriptions', 'date_created', 'last_updated'],
                'sync' => ['title', ['description' => ['plain_text' => '']], 'category_id', 'available_quantity', 'condition', 'listing_type_id', 'price', 'currency_id'],
                'guarded' => ['listing_type_id', 'description'],
                'status' => 'status',
                'description' => 'description'
            ]
        ],
        'medias' => [
            'class' => System\Models\File::class
        ]
    ],
    'routes' => [
        'user_data' => 'users/me',
        'test_user' => 'users/test_user',
        'listing_types' => [
            'general'=>'/sites/{param}/listing_types',
            'by_user'=>'/users/{param}/available_listing_types'
        ],
        'categories' => 'sites/{param}/categories',
        'attributes' => 'categories/{param}/attributes',
        'items' => [
            // 'search' => 'users/{param}/items/search',
            'search' => 'users/{param}/items/search',
            'get' => [
                'general' => 'items/{param}',
                'one' => 'items/{param}',
                'description' => 'items/{param}/description',
                'categories' => 'categories/{param}',
                'predict' => 'sites/{param}/category_predictor/predict?title={title}'
            ],
            'create' => 'items',
            'update' => [
                'all' => 'items/{param}',
                'description' => 'items/{param}/description'
            ],
            'delete' => 'items/{param}'
        ]
    ],
    'response_codes' => [
        'success' => [200, 201],
        'error' => [400, 401, 422, 403]
    ]
];
