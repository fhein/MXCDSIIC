<?php

namespace MxcDropshipInnocigs\Jobs;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use MxcDropshipInnocigs\Api\ApiClient;
use MxcDropshipInnocigs\Article\ArticleRegistry;

class UpdateStockFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $client = $container->get(ApiClient::class);
        $registry = $container->get(ArticleRegistry::class);
        return new UpdateStock($client, $registry);
    }
}