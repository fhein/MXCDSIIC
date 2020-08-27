<?php

namespace MxcDropshipInnocigs\Services;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use MxcDropshipIntegrator\Dropship\DropshipLogger;

class OrderErrorHandlerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $dropshipOrder = $container->get(DropshipOrder::class);
        $dropshipLogger = $container->get(DropshipLogger::class);
        return new OrderErrorHandler($dropshipOrder, $dropshipLogger);
    }
}