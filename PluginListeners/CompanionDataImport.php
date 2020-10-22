<?php

namespace MxcDropshipInnocigs\PluginListeners;

use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcCommons\Toolbox\Shopware\ArticleTool;
use MxcCommons\Toolbox\Strings\StringTool;
use MxcDropship\Dropship\DropshipManager;
use MxcDropshipInnocigs\Article\ArticleRegistry;
use MxcDropshipInnocigs\Companion\DropshippersCompanion;
use Shopware\Models\Article\Detail;
use Throwable;

class CompanionDataImport implements AugmentedObject
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    /** @var DropshippersCompanion */
    protected $companion;

    /** @var ArticleRegistry */
    protected $registry;

    public function __construct(DropshippersCompanion $companion, ArticleRegistry $registry)
    {
        $this->companion = $companion;
        $this->registry = $registry;
    }

    public function importCompanionData()
    {
        if (! $this->companion->validate()) return;
        $this->importCompanionArticleData();
        $this->importCompanionOrderData();
    }

    public function importCompanionArticleData()
    {
        $detailRepository = $this->modelManager->getRepository(Detail::class);
        $details = $detailRepository->findAll();

        /** @var Detail $detail */
        foreach ($details as $detail) {
            $attr = ArticleTool::getDetailAttributes($detail);
            if (! empty($attr['dc_ic_ordernumber'])) {
                $purchasePrice = $attr['dc_ic_purchasing_price'];
                $purchasePrice = StringTool::tofloat($purchasePrice);
                $retailPrice = $attr['dc_ic_retail_price'];
                $retailPrice = StringTool::tofloat($retailPrice);
                $settings = [
                    'mxcbc_dsi_ic_purchaseprice' => $purchasePrice,
                    'mxcbc_dsi_ic_retailprice' => $retailPrice,
                    'mxcbc_dsi_ic_productname' => $attr['dc_ic_articlename'],
                    'mxcbc_dsi_ic_productnumber' => $attr['dc_ic_ordernumber'],
                    'mxcbc_dsi_ic_instock' => $attr['dc_ic_instock'],
                    'mxcbc_dsi_mode' => DropshipManager::MODE_DROPSHIP_ONLY,
                    'mxcbc_dsi_ic_registered' => true,
                    'mxcbc_dsi_ic_status' => 0,
                ];
                $this->registry->updateSettings($detail->getId(), $settings);
            }
        }
    }

    public function importCompanionOrderData()
    {

    }
}