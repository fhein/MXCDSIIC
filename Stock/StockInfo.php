<?php

namespace MxcDropshipInnocigs\Stock;

use MxcDropship\Dropship\DropshipManager;
use MxcDropshipInnocigs\Api\ApiClient;

class StockInfo
{
    protected $client;
    protected $supplierId = DropshipManager::SUPPLIER_INNOCIGS;

    protected $stockCache;

    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }

    // query the current stock of a detail configured for InnoCigs dropship
    // $attr is an array containing the article_attributes or compatible
    // queries InnoCigs API if $live = true, consider performance impact
    public function getStock(array $attr, bool $live = false)
    {
        $mode = $attr['mxcbc_dsi_mode'] ?? DropshipManager::MODE_OWNSTOCK_ONLY;
        $stockInfo = [
            'supplierId' => $this->supplierId,
            'mode' => $mode,
        ];
        $productNumber = $attr['mxcbc_dsi_ic_productnumber'];
        $instock = $mode == DropshipManager::MODE_OWNSTOCK_ONLY ? 0 : $attr['mxcbc_dsi_ic_instock'];
        if (! empty($productNumber) && $mode != DropshipManager::MODE_OWNSTOCK_ONLY && $live) {
            $cachedStock = @$this->stockCache[$productNumber];
            $instock = $cachedStock ?? $this->client->getStockInfo($productNumber);
            $this->stockCache[$productNumber] = $instock;
        }
        $stockInfo['instock'] = $instock;
        return $stockInfo;
    }
}