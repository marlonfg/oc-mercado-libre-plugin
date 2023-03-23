<?php return [
    'plugin' => [
        'name' => 'MercadoLibre',
        'description' => 'Plugin de MercadoLibre para Shopaholic.',
    ],
    'app_id' => 'App ID',
    'app_secret' => 'Secret Token',
    'test_access_token' => 'Test Access Token',
    'test_mode' => 'Test Mode',
    'automatic_sync' => 'Automatic Synchronize',
    'category' => 'Categories',
    'main_menu_sync' => 'Synchronize',
    'menu' => [
        'category' => 'Categories',
        'config' => 'Setting',
    ],
    'tab' => [
        'manage_sync' => 'Manage Sync',
        'category_image' => 'Image',
    ],
    'field' => [
        'manage_sync' => 'Manage Sync',
        'partial' => 'Partial Synchronize',
        'category_name' => 'Name',
        'category_meli' => 'Meli Category',
        'category_preview_image' => 'Preview Image',
    ],
    'button' => [
        'sync' => 'Synchronize',
        'load' => 'Synchronizing'
    ],
    'msg'=>[
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
        'sync_selected_confirm' => '¿Sincronizar los registros seleccionados?'
    ],
];