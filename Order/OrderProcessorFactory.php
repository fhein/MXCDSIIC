<?php

namespace MxcDropshipInnocigs\Order;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use MxcDropship\Dropship\DropshipLogger;
use MxcDropship\MxcDropship;

class OrderProcessorFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $dropshipOrder = $container->get(DropshipOrder::class);
        $errorHandler = $container->get(OrderErrorHandler::class);
        $dropshipLogger = MxcDropship::getServices()->get(DropshipLogger::class);
        return new OrderProcessor($dropshipOrder, $dropshipLogger, $errorHandler);
    }
}