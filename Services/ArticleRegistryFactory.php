<?php

namespace MxcDropshipInnocigs\Services;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class ArticleRegistryFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $db = Shopware()->Db();
        $client = $container->get(ApiClient::class);
        return new ArticleRegistry($client, $db);
    }

}