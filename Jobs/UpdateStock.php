<?php

namespace MxcDropshipInnocigs\Jobs;

use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcCommons\Toolbox\Shopware\ArticleTool;
use MxcCommons\Toolbox\Strings\StringTool;
use MxcDropshipInnocigs\Api\ApiClient;
use MxcDropshipInnocigs\Article\ArticleRegistry;
use Shopware\Models\Article\Detail;
use Shopware\Models\Plugin\Plugin;

class UpdateStock implements AugmentedObject
{
    use ModelManagerAwareTrait;
    use LoggerAwareTrait;

    /** @var ApiClient */
    protected $client;

    /** @var ArticleRegistry */
    protected $registry;

    protected $companionPresent;

    public function __construct(ApiClient $client, ArticleRegistry $registry)
    {
        $this->client = $client;
        $this->registry = $registry;
    }

    public function run()
    {
        $info = $this->client->getProducts(true, false);
        $stockInfo = $this->client->getStockInfo();
        $details = $this->modelManager->getRepository(Detail::class)->findAll();

        /** @var Detail $detail */
        foreach ($details as $detail) {
            $detailId = $detail->getId();
            $settings = $this->registry->getSettings($detailId);
            $productNumber = $settings['mxcbc_dsi_ic_productnumber'];
            if (empty($productNumber)) continue;

            if ($info[$productNumber] === null) {
                $purchasePrice = null;
                $retailPrice = null;
                $instock = 0;
            } else {
                // record from InnoCigs available
                $purchasePrice = StringTool::toFloat($info[$productNumber]['purchasePrice']);
                $retailPrice = StringTool::tofloat($info[$productNumber]['recommendedRetailPrice']);
                $instock = intval($stockInfo[$productNumber]) ?? 0;
            }

            // vapee dropship attributes
            $settings['mxcbc_dsi_ic_purchaseprice'] = $purchasePrice;
            $settings['mxcbc_dsi_ic_retailprice'] = $retailPrice;
            $settings['mxcbc_dsi_ic_instock'] = $instock;
            $this->registry->updateSettings($detailId, $settings);

            // For now we override shopware's purchase price @todo: Configurable?
            $detail->setPurchasePrice($purchasePrice);

            // dropshippers companion attributes (legacy dropship support)
            if (! $this->isCompanionInstalled()) continue;

            ArticleTool::setDetailAttribute($detail, 'dc_ic_purchasing_price', $info[$productNumber]['purchasePrice']);
            ArticleTool::setDetailAttribute($detail, 'dc_ic_retail_price', $info[$productNumber]['recommendedRetailPrice']);
            ArticleTool::setDetailAttribute($detail, 'dc_ic_instock', $instock);

        }
    }

    protected function isCompanionInstalled() {
        if ($this->companionPresent === null) {
            $this->companionPresent = (null != $this->modelManager->getRepository(Plugin::class)->findOneBy(['name' => 'wundeDcInnoCigs']));
            if (! $this->companionPresent) {
                $this->log->info('Update stock cronjob: Companion is not installed.');
            }
        }
        return $this->companionPresent;
    }
}