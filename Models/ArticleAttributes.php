<?php

namespace MxcDropshipInnocigs\Models;

use Doctrine\ORM\Mapping as ORM;
use MxcDropshipIntegrator\Models\BaseModelTrait;
use MxcCommons\Toolbox\Models\PrimaryKeyTrait;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="s_plugin_mxc_dsi_innocigs_article_attributes")
 */
class ArticleAttributes extends ModelEntity
{
    use PrimaryKeyTrait;

    /**
     * @var string
     *
     * @ORM\Column(name="productnumber", type="string", nullable=false)
     */
    private $productNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="productname", type="string", nullable=false)
     */
    private $productName;

    /**
     * @var float
     *
     * @ORM\Column(name="purchaseprice", type="float", nullable=false)
     */
    private $purchasePrice;

    /**
     * @var float
     *
     * @ORM\Column(name="uvp", type="float", nullable=false)
     */
    private $recommendedRetailPrice;

    /**
     * @var int
     *
     * @ORM\Column(name="instock", type="integer", nullable=false)
     */
    private $instock;

    /**
     * @var bool
     *
     * @ORM\Column(name="preferownstock", type="boolean", nullable=false)
     */
    private $preferOwnStock;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active;

    /**
     * @var int
     *
     * @ORM\Column(name="detailid", type="integer", nullable=false)
     */
    private $detailId;

    /**
     * @return string
     */
    public function getProductNumber()
    {
        return $this->productNumber;
    }

    /**
     * @param string $productNumber
     */
    public function setProductNumber($productNumber)
    {
        $this->productNumber = $productNumber;
    }

    /**
     * @return string
     */
    public function getProductName()
    {
        return $this->productName;
    }

    /**
     * @param string $productName
     */
    public function setProductName($productName)
    {
        $this->productName = $productName;
    }

    /**
     * @return float
     */
    public function getPurchasePrice()
    {
        return $this->purchasePrice;
    }

    /**
     * @param float $purchasePrice
     */
    public function setPurchasePrice($purchasePrice)
    {
        $this->purchasePrice = $purchasePrice;
    }

    /**
     * @return float
     */
    public function getRecommendedRetailPrice()
    {
        return $this->recommendedRetailPrice;
    }

    /**
     * @param float $recommendedRetailPrice
     */
    public function setRecommendedRetailPrice($recommendedRetailPrice)
    {
        $this->recommendedRetailPrice = $recommendedRetailPrice;
    }

    /**
     * @return int
     */
    public function getInstock()
    {
        return $this->instock;
    }

    /**
     * @param int $instock
     */
    public function setInstock($instock)
    {
        $this->instock = $instock;
    }

    /**
     * @return bool
     */
    public function isPreferOwnStock()
    {
        return $this->preferOwnStock;
    }

    /**
     * @param bool $preferOwnStock
     */
    public function setPreferOwnStock($preferOwnStock)
    {
        $this->preferOwnStock = $preferOwnStock;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param mixed $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @return int
     */
    public function getDetailId()
    {
        return $this->detailId;
    }

    /**
     * @param int $detailId
     */
    public function setDetailId(int $detailId)
    {
        $this->detailId = $detailId;
    }
}
