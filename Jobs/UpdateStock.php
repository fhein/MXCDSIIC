<?php

namespace MxcDropshipInnocigs\Jobs;

use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcCommons\Toolbox\Shopware\ArticleTool;
use MxcDropshipInnocigs\Api\ApiClient;
use MxcDropshipInnocigs\Companion\DropshippersCompanion;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use Shopware\Models\Article\Detail;

class UpdateStock implements AugmentedObject
{
    use LoggerAwareTrait;
    use DatabaseAwareTrait;
    use ModelManagerAwareTrait;

    /** @var ApiClient */
    protected $client;

    protected $supplierId;

    protected $companion;

    protected $companionPresent;

    public function __construct(ApiClient $client, DropshippersCompanion $companion)
    {
        $this->companion = $companion;
        $this->client = $client;
        $this->supplierId = MxcDropshipInnocigs::getModule()->getId();
    }

    public function run()
    {
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

        /** @var Detail $detail */
        foreach ($details as $detail) {
            $detailId = $detail['detailId'];
            $productNumber = $detail['productNumber'];
            $instock = @$stockInfo[$productNumber] ?? 0;
            $detail = $repository->find($detailId);
            $detail->setInStock($instock);
            ArticleTool::setDetailAttribute($detailId, 'mxcbc_dsi_ic_instock', intval($instock));
            ArticleTool::setDetailAttribute($detailId, 'mxcbc_dsi_supplier_id', $this->supplierId);
            // dropshippers companion attributes (legacy dropship support)
            if (! $this->isCompanionInstalled()) continue;
            ArticleTool::setDetailAttribute($detailId, 'dc_ic_instock', intval($instock));
        }
        $this->modelManager->flush();
        $this->log->info('Stock update job completed.');
    }

    protected function isCompanionInstalled() {
        return $this->companionPresent ?? $this->companionPresent = $this->companion->validate();
    }
}