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
            's_articles_attributes'      => [
                'mxc_dsi_ic_registered'     => ['type' => TypeMapping::TYPE_BOOLEAN],
                'mxc_dsi_ic_status'         => ['type' => TypeMapping::TYPE_INTEGER],
                'mxc_dsi_ic_active'         => ['type' => TypeMapping::TYPE_BOOLEAN],
                'mxc_dsi_ic_preferownstock' => ['type' => TypeMapping::TYPE_BOOLEAN],
                'mxc_dsi_ic_productnumber'  => ['type' => TypeMapping::TYPE_STRING],
                'mxc_dsi_ic_productname'    => ['type' => TypeMapping::TYPE_STRING],
                'mxc_dsi_ic_purchaseprice'  => ['type' => TypeMapping::TYPE_FLOAT],
                'mxc_dsi_ic_retailprice'    => ['type' => TypeMapping::TYPE_FLOAT],
                'mxc_dsi_ic_instock'        => ['type' => TypeMapping::TYPE_INTEGER],
            ],
        ],
    ],

    'services'     => [
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
