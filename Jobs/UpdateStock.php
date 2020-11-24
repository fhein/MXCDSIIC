<?php

namespace MxcDropshipInnocigs\Jobs;

use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcCommons\Toolbox\Shopware\ArticleTool;
use MxcDropship\Dropship\DropshipManager;
use MxcDropshipInnocigs\Api\ApiClient;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
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

    public function __construct(ApiClient $client, DropshipManager $dropshipManager)
    {
        $this->client = $client;
        $this->supplier = MxcDropshipInnocigs::getModule()->getName();
        $this->dropshipManager = $dropshipManager;
    }

    public function run()
    {
        try {
            // throw ApiException::fromHttpStatus(404);
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
                // @todo: Next line suspicious if more than one dropship module is installed
                ArticleTool::setDetailAttribute($detailId, 'mxcbc_dsi_supplier', $this->supplier);
            }
            $this->modelManager->flush();
            $this->log->info('Stock update job completed.');
            return true;
        } catch (Throwable $e) {
            $this->dropshipManager->handleDropshipException($this->supplier, 'updateStock', $e, true);
            return false;
        }
    }
}