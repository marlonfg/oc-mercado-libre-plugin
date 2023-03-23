<?php return [
    'plugin' => [
        'name' => 'MercadoLibre',
        'description' => 'Plugin de MercadoLibre para Shopaholic.',
    ],
    'app_id' => 'Identificador de la Aplicación',
    'app_secret' => 'Token Secreto',
    'test_access_token' => 'Access Token para Pruebas',
    'test_mode' => 'Modo de Prueba',
    'automatic_sync' => 'Sincronización Automática',
    'category' => 'Categorías',
    'main_menu_sync' => 'Sincronizar',
    'menu' => [
        'category' => 'Categorías',
        'config' => 'Configuración',
        'web_category' => 'Categorías Web',
    ],
    'tab' => [
        'manage_sync' => 'Administrar Sincronización',
        'category_image' => 'Imágen',
    ],
    'field' => [
        'manage_sync' => 'Administrar Sincronización',
        'partial' => 'Sincronización Parcial',
        'category_name' => 'Nombre',
        'category_meli' => 'Categoría Meli',
        'category_preview_image' => 'Imágen para Vista Previa',
        'id' => 'ID',
        'creado' => 'Creado',
    ],
    'button' => [
        'sync' => 'Sincronizar',
        'load' => 'Sincronizando',
    ],
    'msg' => [
        'save' => 'Los campos Id y Token Secreto son obligatorios. 
                                                                        La opción sincronización automática actualizará los productos cuando se modifiquen.
                                                                        La opción sincronización parcial se selecciona para solo sincronizar los productos nuevos.
                                                                        Desea guardar la configuración actual?',
        'sync' => 'Desea sincronizar con la configuración previamente guardada?',
        'cat' => 'Usted debe haber seleccionado las categorías necesarias para su negocio.
                                                                        Se guardará todo el árbol de categorías correspondiente a lo seleccionado.
                                                                        Desea sincronizar las categorías seleccionadas?',
        'comment_auto' => 'La sincronización automática sincronizará los productos de manera automática al detectar cambios en el catálogo.',
        'comment_partial' => 'La sincronización parcial define la manera de traer los productos hacia el catálogo, parcial activo solo trae los productos nuevos, de lo contrario se traen todos los productos.',
        'sync_selected_confirm' => '¿Sincronizar los registros seleccionados?',
    ],
];