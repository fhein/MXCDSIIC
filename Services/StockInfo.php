<?php

namespace MxcDropshipInnocigs\Services;

use MxcDropshipIntegrator\Dropship\DropshipManager;

class StockInfo
{
    // @todo: Configuration

    // if true we request stock data live from InnoCigs which is time consuming
    // if false we rely on the stock data we refresh via cronjob
    protected $liveStock = false;

    protected $client;
    protected $supplierId = DropshipManager::SUPPLIER_INNOCIGS;

    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param array $sArticle
     * @return array
     */
    public function getStockInfo($sArticle)
    {
        $stockInfo = [];
        // Find if any dropship article has stock-value
        if ($sArticle['mxc_dsi_ic_productnumber'] != '' &&
            $sArticle['mxc_dsi_ic_active'] == 1 &&
            $sArticle['mxc_dsi_ic_instock'] > 0
        ) {
            $instock = $sArticle['mxc_dsi_ic_instock'];
            if ($this->liveStock) {
                $instock = $this->client->getStockInfo($sArticle['mxc_dsi_ic_productnumber']);
            }
            $stockInfo = [
                'supplierId' => $this->supplierId,
                'instock'    => $instock,
            ];
        }
        return $stockInfo;
    }
}