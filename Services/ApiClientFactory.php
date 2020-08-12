<?php

namespace MxcDropshipInnocigs\Services;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcDropshipIntegrator\Dropship\SupplierRegistry;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class ApiClientFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $credentials = $container->get(Credentials::class);
        $logger = $container->get('logger');
        return new ApiClient($credentials, $logger);
    }
}