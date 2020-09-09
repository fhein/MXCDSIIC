<?php

namespace MxcDropshipInnocigs\Jobs;

use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcCommons\Toolbox\Shopware\ArticleTool;
use MxcDropshipInnocigs\Api\ApiClient;
use MxcDropshipInnocigs\Companion\DropshippersCompanion;
use Shopware\Models\Article\Detail;

class UpdateStock implements AugmentedObject
{
    use LoggerAwareTrait;
    use DatabaseAwareTrait;

    /** @var ApiClient */
    protected $client;

    protected $companion;

    protected $companionPresent;

    public function __construct(ApiClient $client, DropshippersCompanion $companion)
    {
        $this->companion = $companion;
        $this->client = $client;
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

        /** @var Detail $detail */
        foreach ($details as $detail) {
            $detailId = $detail['detailId'];
            $productNumber = $detail['productNumber'];
            $instock = @$stockInfo[$productNumber] ?? 0;
            ArticleTool::setDetailAttribute($detailId, 'mxcbc_dsi_ic_instock', intval($instock));
            // dropshippers companion attributes (legacy dropship support)
            if (! $this->isCompanionInstalled()) continue;
            ArticleTool::setDetailAttribute($detailId, 'dc_ic_instock', intval($instock));
        }
        $this->log->info('Stock update job completed.');
    }

    protected function isCompanionInstalled() {
        return $this->companionPresent ?? $this->companionPresent = $this->companion->validate();
    }
}