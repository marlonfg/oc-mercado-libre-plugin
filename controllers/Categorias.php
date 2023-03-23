<?php namespace MarlonFreire\MercadoLibre\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Categorias extends Controller
{
    public $implement = [        'Backend\Behaviors\ListController',        'Backend\Behaviors\FormController',        'Backend\Behaviors\ReorderController'    ];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public $requiredPermissions = [
        'manage_sync' 
    ];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('marlonfreire.mercadolibre', 'main-menu-sync', 'side-menu-categoria');
    }
}
