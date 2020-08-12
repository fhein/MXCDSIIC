<?php

namespace MxcDropshipInnocigs;

use MxcCommons\Plugin\Plugin;
use MxcCommons\Plugin\Service\ServicesFactory;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\UninstallContext;

class MxcDropshipInnocigs extends Plugin {

    protected $activateClearCache = ActivateContext::CACHE_LIST_ALL;
    protected $uninstallClearCache = UninstallContext::CACHE_LIST_ALL;

    public const MXC_DELIMITER_L1 = '#!#';
    public const MXC_DELIMITER_L2 = '##!##';
    public const MXC_PATH_DELIMITER = '>';

    public const PLUGIN_DIR = __DIR__;

    private static $services;

    public static function getServices()
    {
        if (self::$services !== null) return self::$services;
        $factory = new ServicesFactory();
        self::$services = $factory->getServices(__DIR__);
        return self::$services;

    }
}

