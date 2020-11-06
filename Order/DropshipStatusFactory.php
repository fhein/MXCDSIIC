<?php

namespace MxcDropshipInnocigs\Order;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\MxcDropship;

class DropshipStatusFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $dropshipManager = MxcDropship::getServices()->get(DropshipManager::class);
        return new DropshipStatus($dropshipManager);
    }
}