<?php

use Jorique\BitrixSmsAeroConnector\Connector;

class jorique_smsaero_connector extends CModule
{
    public $MODULE_ID = 'jorique.smsaero_connector';

    public $MODULE_VERSION;

    public $MODULE_VERSION_DATE;

    public function __construct()
    {
        $arModuleVersion = [];
        $path = realpath(dirname(__FILE__));
        require $path.'/version.php';

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

        $this->MODULE_NAME = "Коннектор для шлюза SMSAero";
        $this->MODULE_DESCRIPTION = "Коннектор для шлюза SMSAero для модуля сервис сообщений";
    }

    public function DoInstall() {
        RegisterModule($this->MODULE_ID);

        RegisterModuleDependences('messageservice', 'onGetSmsSenders', $this->MODULE_ID, Connector::class, 'onGetSmsSenders');
    }

    public function DoUninstall() {
        UnRegisterModuleDependences('messageservice', 'onGetSmsSenders', $this->MODULE_ID, Connector::class, 'onGetSmsSenders');

        UnRegisterModule($this->MODULE_ID);
    }
}