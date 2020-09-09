<?php

namespace MxcDropshipInnocigs\Jobs;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use MxcDropshipInnocigs\Api\ApiClient;
use MxcDropshipInnocigs\Article\ArticleRegistry;
use MxcDropshipInnocigs\Companion\DropshippersCompanion;

class UpdateStockFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $companion = $container->get(DropshippersCompanion::class);
        $client = $container->get(ApiClient::class);
        $registry = $container->get(ArticleRegistry::class);
        return new UpdateStock($client, $companion);
    }
}