<?php

namespace MxcDropshipInnocigs\Order;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use MxcDropship\Dropship\DropshipLogger;
use MxcDropship\MxcDropship;

class OrderErrorHandlerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $dropshipOrder = $container->get(DropshipOrder::class);
        $dropshipLogger = MxcDropship::getServices()->get(DropshipLogger::class);
        return new OrderErrorHandler($dropshipOrder, $dropshipLogger);
    }
}