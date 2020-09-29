<?php

namespace MxcDropshipInnocigs;

use MxcDropshipInnocigs\EventListeners\DropshipEventListener;
use MxcDropshipInnocigs\Jobs\UpdatePrices;
use MxcDropshipInnocigs\Jobs\UpdateStock;
use MxcDropshipInnocigs\PluginListeners\RegisterDropshipModule;
use MxcDropshipInnocigs\Api\ApiClient;
use MxcDropshipInnocigs\Article\ArticleRegistry;
use MxcDropshipInnocigs\Api\Credentials;
use MxcDropshipInnocigs\Order\DropshipOrder;
use MxcDropshipInnocigs\Companion\DropshippersCompanion;
use MxcDropshipInnocigs\Order\OrderProcessor;
use MxcDropshipInnocigs\Api\Xml\HttpReader;
use MxcDropshipInnocigs\Api\Xml\ResponseToArray;
use MxcDropshipInnocigs\Api\Xml\XmlReader;
use Shopware\Bundle\AttributeBundle\Service\TypeMapping;

return [
    'plugin_listeners'   => [
        RegisterDropshipModule::class
    ],
    'doctrine' => [
        'attributes' => [
            's_articles_attributes'      => [
                // ist das Produkt für InnoCigs dropship registriert?
                'mxcbc_dsi_ic_registered'     => ['type' => TypeMapping::TYPE_BOOLEAN],
                'mxcbc_dsi_ic_status'         => ['type' => TypeMapping::TYPE_INTEGER],
                'mxcbc_dsi_ic_productnumber'  => ['type' => TypeMapping::TYPE_STRING],
                'mxcbc_dsi_ic_productname'    => ['type' => TypeMapping::TYPE_STRING],
                'mxcbc_dsi_ic_purchaseprice'  => ['type' => TypeMapping::TYPE_FLOAT],
                'mxcbc_dsi_ic_retailprice'    => ['type' => TypeMapping::TYPE_FLOAT],
                'mxcbc_dsi_ic_instock'        => ['type' => TypeMapping::TYPE_INTEGER],
            ],
        ],
    ],

    'services'     => [
        'magicals'  => [
            DropshipOrder::class,
            ArticleRegistry::class,
            ApiClient::class,
            Credentials::class,
            DropshippersCompanion::class,
            OrderProcessor::class,
            HttpReader::class,
            XmlReader::class,
            ResponseToArray::class,
            UpdateStock::class,
            UpdatePrices::class,
            DropshipEventListener::class,
        ],
        'aliases' => [
            'DropshipEventListener'     => DropshipEventListener::class,
            'ArticleRegistry'           => ArticleRegistry::class,
            'ApiClient'                 => ApiClient::class,
            'OrderProcessor'            => OrderProcessor::class,
            'DropshippersCompanion'     => DropshippersCompanion::class,
        ]
    ],
];
