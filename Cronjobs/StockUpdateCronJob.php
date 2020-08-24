<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Cronjobs;

use Enlight\Event\SubscriberInterface;
use MxcCommons\Plugin\Service\LoggerInterface;
use MxcCommons\Toolbox\Strings\StringTool;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use MxcDropshipInnocigs\Services\ApiClient;
use MxcDropshipInnocigs\Services\ArticleRegistry;
use MxcCommons\Toolbox\Shopware\ArticleTool;
use Shopware\Models\Article\Detail;
use Shopware\Models\Plugin\Plugin;
use Throwable;

class StockUpdateCronJob implements SubscriberInterface
{
    protected $log = null;

    protected $companionPresent = null;

    protected $modelManager = null;

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_MxcInnocigsStockUpdate' => 'onStockUpdate',
        ];
    }

    public function onStockUpdate(/** @noinspection PhpUnusedParameterInspection */ $job)
    {
        $services = MxcDropshipInnocigs::getServices();
        /** @var LoggerInterface $log */
        $this->log = $services->get('logger');
        $this->modelManager = $services->get('models');
        $result = true;

        if (! $this->isCompanionInstalled()) {
            $this->log->info('Update stock cronjob: Companion is not installed.');
        }

        $start = date('d-m-Y H:i:s');

        try {
            $this->updateStockInfo();
        } catch (Throwable $e) {
            $this->log->except($e, false, false);
            $result = false;
        }
        $end = date('d-m-Y H:i:s');

        $resultMsg = $result === true ? '. Success.' : '. Failure.';
        $msg = 'Update stock cronjob ran from ' . $start . ' to ' . $end . $resultMsg;

        $result === true ? $this->log->info($msg) : $this->log->err($msg);

        return $result;
    }

    protected function updateStockInfo()
    {
        /** @var ApiClient $apiClient */
        $services = MxcDropshipInnocigs::getServices();
        $apiClient = $services->get(ApiClient::class);
        /** @var ArticleRegistry $registry */
        $registry = $services->get(ArticleRegistry::class);

        $info = $apiClient->getItemList(true, false);
        $stockInfo = $apiClient->getAllStockInfo();
        $details = $this->modelManager->getRepository(Detail::class)->findAll();

        /** @var Detail $detail */
        foreach ($details as $detail) {
            $detailId = $detail->getId();
            $settings = $registry->getSettings($detailId);
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
            $registry->updateSettings($detailId, $settings);

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
        }
        return $this->companionPresent;
    }
}