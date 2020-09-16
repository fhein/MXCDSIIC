<?php

namespace MxcDropshipInnocigs;

use MxcCommons\Plugin\Plugin;
use MxcCommons\Plugin\Service\ServicesFactory;
use MxcDropship\Models\DropshipModule;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\UninstallContext;

class MxcDropshipInnocigs extends Plugin {

    protected $activateClearCache = ActivateContext::CACHE_LIST_ALL;
    protected $uninstallClearCache = UninstallContext::CACHE_LIST_ALL;

    protected static $dropshipModuleName = 'InnoCigs';
    protected static $dropshipModuleSupplier = 'InnoCigs GmbH & Co. KG';

    public const PLUGIN_DIR = __DIR__;

    private static $services;
    private static $module;

    public static function getServices()
    {
        if (self::$services !== null) return self::$services;
        $factory = new ServicesFactory();
        self::$services = $factory->getServices(__DIR__);

        return self::$services;
    }

    public static function getModule()
    {
        if (self::$module !== null) return self::$module;
        $modelManager = self::getServices()->get('models');
        self::$module = $modelManager->getRepository(DropshipModule::class)->findOneBy([ 'name' => self::$dropshipModuleName]);
        return self::$module;
    }

    public static function getDropshipModuleName() {
        return self::$dropshipModuleName;
    }

    public static function getDropshipModuleSupplier() {
        return self::$dropshipModuleSupplier;
    }
}

