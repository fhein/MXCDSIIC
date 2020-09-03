<?php

namespace MxcDropshipInnocigs\Api\Xml;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class HttpReaderFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $rta = $container->get(ResponseToArray::class);
        return new HttpReader($rta);
    }
}