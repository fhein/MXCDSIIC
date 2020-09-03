<?php

namespace MxcDropshipInnocigs\Companion;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use MxcDropshipInnocigs\Api\ApiClient;

class DropshippersCompanionFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $apiClient = $container->get(ApiClient::class);
        return new DropshippersCompanion($apiClient);
    }
}