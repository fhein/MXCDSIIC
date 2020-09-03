<?php

namespace MxcDropshipInnocigs\Jobs;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use MxcDropshipInnocigs\Import\ImportClient;
use MxcDropshipIntegrator\Mapping\ImportPriceMapper;
use MxcDropshipIntegrator\MxcDropshipIntegrator;

class UpdatePricesFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $client = $container->get(ImportClient::class);
        $mapper = MxcDropshipIntegrator::getServices()->get(ImportPriceMapper::class);
        return new UpdatePrices($client, $mapper);
    }
}