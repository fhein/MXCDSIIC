<?php

namespace MxcDropshipInnocigs\Jobs;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use MxcDropshipInnocigs\Api\ApiClient;
use MxcDropshipInnocigs\Companion\DropshippersCompanion;

class UpdatePricesFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $companion = $container->get(DropshippersCompanion::class);
        $apiClient = $container->get(ApiClient::class);
        return new UpdatePrices($apiClient, $companion);
    }
}