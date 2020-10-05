<?php

namespace MxcDropshipInnocigs\Jobs;

use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcCommons\Toolbox\Shopware\ArticleTool;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\Exception\DropshipException;
use MxcDropshipInnocigs\Api\ApiClient;
use MxcDropshipInnocigs\Companion\DropshippersCompanion;
use MxcDropshipInnocigs\Exception\ApiException;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use MxcDropshipInnocigs\Order\DropshipStatus;
use Shopware\Models\Article\Detail;
use Throwable;

class UpdateStock implements AugmentedObject
{
    use LoggerAwareTrait;
    use DatabaseAwareTrait;
    use ModelManagerAwareTrait;

    /** @var ApiClient */
    protected $client;

    /** @var DropshipManager */
    protected $dropshipManager;

    protected $supplier;

    protected $companion;

    protected $companionPresent;

    public function __construct(ApiClient $client, DropshippersCompanion $companion, DropshipManager $dropshipManager)
    {
        $this->companion = $companion;
        $this->client = $client;
        $this->supplier = MxcDropshipInnocigs::getModule()->getName();
        $this->dropshipManager = $dropshipManager;
    }

    public function run()
    {
        try {
            throw ApiException::fromHttpStatus(404);
            $stockInfo = $this->client->getStockInfo();
            $sql = '
                SELECT 
                    articledetailsID as detailId, 
                    mxcbc_dsi_ic_productnumber as productNumber 
                FROM s_articles_attributes 
                WHERE mxcbc_dsi_ic_productnumber IS NOT NULL
            ';
            $details = $this->db->fetchAll($sql);
            $repository = $this->modelManager->getRepository(Detail::class);

            foreach ($details as $detail) {
                $detailId = $detail['detailId'];
                $productNumber = $detail['productNumber'];
                $instock = @$stockInfo[$productNumber] ?? 0;
                $swDetail = $repository->find($detailId);
                $swDetail->setInStock($instock);
                ArticleTool::setDetailAttribute($detailId, 'mxcbc_dsi_ic_instock', intval($instock));
                ArticleTool::setDetailAttribute($detailId, 'mxcbc_dsi_supplier', $this->supplier);
                // dropshippers companion attributes (legacy dropship support)
                if (! $this->isCompanionInstalled()) {
                    continue;
                }
                ArticleTool::setDetailAttribute($detailId, 'dc_ic_instock', intval($instock));
            }
            $this->modelManager->flush();
            $this->log->info('Stock update job completed.');
            return true;
        } catch (Throwable $e) {
            $this->dropshipManager->handleDropshipException($this->supplier, 'updatePrices', $e, true);
            return false;
        }
    }

    protected function isCompanionInstalled() {
        return $this->companionPresent ?? $this->companionPresent = $this->companion->validate();
    }
}