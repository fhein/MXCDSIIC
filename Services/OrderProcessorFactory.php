<?php

namespace MxcDropshipInnocigs\Services;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Plugin\Service\AugmentedObjectFactory;

class OrderProcessorFactory extends AugmentedObjectFactory
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $dropshipOrder = $container->get(DropshipOrder::class);
        $errorHandler = $container->get(OrderErrorHandler::class);
        return $this->augment($container, new OrderProcessor($dropshipOrder, $errorHandler));
    }
}