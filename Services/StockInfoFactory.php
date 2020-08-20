<?php

namespace MxcDropshipInnocigs\Services;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class StockInfoFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $db = $container->get('db');
        $client = $container->get(ApiClient::class);
        return new StockInfo($client);
    }
}