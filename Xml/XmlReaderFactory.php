<?php

namespace MxcDropshipInnocigs\Xml;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class XmlReaderFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $rta = $container->get(ResponseToArray::class);
        return new XmlReader($rta);
    }
}