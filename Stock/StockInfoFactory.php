<?php

namespace MxcDropshipInnocigs\Stock;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use MxcDropshipInnocigs\Api\ApiClient;

class StockInfoFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new StockInfo($container->get(ApiClient::class));
    }
}