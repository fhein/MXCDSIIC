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
use MxcDropshipInnocigs\Services\StockInfo;
use Shopware\Bundle\AttributeBundle\Service\TypeMapping;

return [
    'plugin'   => [
    ],
    'doctrine' => [
        'models'                     => [
            Model::class,
        ],
        'attributes'                 => [
            's_order_attributes' => [
                'mxc_dsi_ic_active'     => ['type' => TypeMapping::TYPE_BOOLEAN],
                'mxc_dsi_ic_cronstatus' => ['type' => TypeMapping::TYPE_INTEGER],
                'mxc_dsi_ic_status'     => ['type' => TypeMapping::TYPE_INTEGER],
            ],
            's_order_details_attributes' => [
                'mxc_dsi_ic_id'            => ['type' => TypeMapping::TYPE_STRING],
                'mxc_dsi_ic_order_id'      => ['type' => TypeMapping::TYPE_STRING],
                'mxc_dsi_ic_infos'         => ['type' => TypeMapping::TYPE_STRING],
                'mxc_dsi_ic_supplier'      => ['type' => TypeMapping::TYPE_STRING],
                'mxc_dsi_ic_stock'         => ['type' => TypeMapping::TYPE_INTEGER],
                'mxc_dsi_ic_purchaseprice' => ['type' => TypeMapping::TYPE_FLOAT],
                'mxc_dsi_ic_date'          => ['type' => TypeMapping::TYPE_STRING],
                'mxc_dsi_ic_status'        => ['type' => TypeMapping::TYPE_INTEGER],
                'mxc_dsi_ic_message'       => ['type' => TypeMapping::TYPE_STRING],
                'mxc_dsi_ic_carrier'       => ['type' => TypeMapping::TYPE_STRING],
                'mxc_dsi_ic_tracking_id'   => ['type' => TypeMapping::TYPE_STRING],
            ],
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
        'factories' => [
            DropshipOrder::class => AugmentedObjectFactory::class,
        ],
        'magicals'  => [
            ArticleRegistry::class,
            ApiClient::class,
            ApiClientSequential::class,
            Credentials::class,
            ImportClient::class,
            DropshippersCompanion::class,
            StockInfo::class,
        ],
    ],
    'class_config' => [
        ImportClient::class  => 'ImportClient.config.php',
        DropshipOrder::class => 'DropshipOrder.config.php',
    ],
];
