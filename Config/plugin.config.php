<?php

namespace MxcDropshipInnocigs;

use MxcCommons\Plugin\Service\AugmentedObjectFactory;
use MxcDropshipInnocigs\Models\Model;
use MxcDropshipInnocigs\Services\ApiClient;
use MxcDropshipInnocigs\Services\ApiClientSequential;
use MxcDropshipInnocigs\Services\ArticleRegistry;
use MxcDropshipInnocigs\Services\Credentials;
use MxcDropshipInnocigs\Services\DropshipOrder;
use MxcDropshipInnocigs\Services\DropshippersCompanion;
use MxcDropshipInnocigs\Services\ImportClient;
use MxcDropshipInnocigs\Services\OrderErrorHandler;
use MxcDropshipInnocigs\Services\OrderProcessor;
use MxcDropshipInnocigs\Services\StockInfo;
use Shopware\Bundle\AttributeBundle\Service\TypeMapping;

return [
    'plugin'   => [
    ],
    'doctrine' => [
        'models'     => [
            Model::class,
        ],
        'attributes' => [
            's_order_attributes'         => [
                'mxcbc_dsi_ic_active'     => ['type' => TypeMapping::TYPE_BOOLEAN],
                'mxcbc_dsi_ic_cronstatus' => ['type' => TypeMapping::TYPE_INTEGER],
                'mxcbc_dsi_ic_status'     => ['type' => TypeMapping::TYPE_INTEGER],
            ],

            's_articles_attributes'      => [
                // ist das Produkt für InnoCigs dropship registriert?
                'mxcbc_dsi_ic_registered'     => ['type' => TypeMapping::TYPE_BOOLEAN],
                'mxcbc_dsi_ic_status'         => ['type' => TypeMapping::TYPE_INTEGER],

                // Aus welcher Quelle wird bei Bestellung geliefert?
                //      - aus eigenem Lager                                     -> 1
                //      - Dropship und eigenes Lager, eigenes Lager bevorzugen  -> 2
                //      - Dropship und eigenes Lager, Dropship bevorzugen       -> 3
                //      - nur Dropship                                          -> 4
                'mxcbc_dsi_ic_delivery'       => ['type' => TypeMapping::TYPE_INTEGER],
                'mxcbc_dsi_ic_productnumber'  => ['type' => TypeMapping::TYPE_STRING],
                'mxcbc_dsi_ic_productname'    => ['type' => TypeMapping::TYPE_STRING],
                'mxcbc_dsi_ic_purchaseprice'  => ['type' => TypeMapping::TYPE_FLOAT],
                'mxcbc_dsi_ic_retailprice'    => ['type' => TypeMapping::TYPE_FLOAT],
                'mxcbc_dsi_ic_instock'        => ['type' => TypeMapping::TYPE_INTEGER],
            ],
        ],
    ],

    'services'     => [
        'factories' => [
            OrderErrorHandler::class => AugmentedObjectFactory::class,
        ],
        'magicals'  => [
            DropshipOrder::class,
            ArticleRegistry::class,
            ApiClient::class,
            ApiClientSequential::class,
            Credentials::class,
            ImportClient::class,
            DropshippersCompanion::class,
            StockInfo::class,
            OrderProcessor::class,
        ],
    ],
    'class_config' => [
        ImportClient::class  => 'ImportClient.config.php',
    ],
];
