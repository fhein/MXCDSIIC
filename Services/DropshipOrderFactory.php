<?php

namespace MxcDropshipInnocigs\Services;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class DropshipOrderFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = Shopware()->Config();
        $apiClient = $container->get(ApiClient::class);
        return new DropshipOrder($config, $apiClient);
    }
}