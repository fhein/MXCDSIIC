<?php

namespace MxcDropshipInnocigs\Models;

use Doctrine\ORM\Mapping as ORM;
use MxcDropshipIntegrator\Models\BaseModelTrait;
use MxcCommons\Toolbox\Models\PrimaryKeyTrait;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="s_plugin_mxc_dsi_innocigs_order_detail_attributes")
 */
class OrderDetailAttributes extends ModelEntity
{
    use PrimaryKeyTrait;
    /**
     * @var int
     * @ORM\Column(name="detail_id", type="integer", nullable=true)
     */
    private $detailId;

    /**
     * @var string
     * @ORM\Column(name="supplier", type="string", nullable=true)
     */
    private $supplier;

    /**
     * @var int
     * @ORM\Column(name="instock", type="integer", nullable=true)
     */
    private $instock;

    /**
     * @var float
     * @ORM\Column(name="purchaseprice", type="float", nullable=true)
     */
    private $purchasePrice;

    /**
     * @var string
     * @ORM\Column(name="ds_date", type="string", nullable=true)
     */
    private $dropshipDate;

    /**
     * @var string
     * @ORM\Column(name="ds_status", type="string", nullable=true)
     */
    private $dropshipStatus;

    /**
     * @var string
     * @ORM\Column(name="ds_message", type="string", nullable=true)
     */
    private $dropshipStatusMessage;

    /**
     * @var string
     * @ORM\Column(name="ds_id", type="string", nullable=true)
     */
    private $dropshipId;

    /**
     * @var string
     * @ORM\Column(name="ds_order_id", type="string", nullable=true)
     */
    private $dropshipOrderId;

    /**
     * @var string
     * @ORM\Column(name="info", type="string", nullable=true)
     */
    private $info;

    /**
     * @var string
     * @ORM\Column(name="carrier", type="string", nullable=true)
     */
    private $carrier;

    /**
     * @var string
     * @ORM\Column(name="tracking_id", type="string", nullable=true)
     */
    private $trackingId;

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
    public function setDetailId($detailId)
    {
        $this->detailId = $detailId;
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
     * @return string
     */
    public function getDropshipDate()
    {
        return $this->dropshipDate;
    }

    /**
     * @param string $dropshipDate
     */
    public function setDropshipDate($dropshipDate)
    {
        $this->dropshipDate = $dropshipDate;
    }

    /**
     * @return string
     */
    public function getDropshipStatus()
    {
        return $this->dropshipStatus;
    }

    /**
     * @param string $dropshipStatus
     */
    public function setDropshipStatus($dropshipStatus)
    {
        $this->dropshipStatus = $dropshipStatus;
    }

    /**
     * @return string
     */
    public function getDropshipStatusMessage()
    {
        return $this->dropshipStatusMessage;
    }

    /**
     * @param string $dropshipStatusMessage
     */
    public function setDropshipStatusMessage(string $dropshipStatusMessage)
    {
        $this->dropshipStatusMessage = $dropshipStatusMessage;
    }

    /**
     * @return string
     */
    public function getDropshipId()
    {
        return $this->dropshipId;
    }

    /**
     * @param string $dropshipId
     */
    public function setDropshipId($dropshipId)
    {
        $this->dropshipId = $dropshipId;
    }

    /**
     * @return string
     */
    public function getDropshipOrderId()
    {
        return $this->dropshipOrderId;
    }

    /**
     * @param string $dropshipOrderId
     */
    public function setDropshipOrderId($dropshipOrderId)
    {
        $this->dropshipOrderId = $dropshipOrderId;
    }

    /**
     * @return string
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @param string $info
     */
    public function setInfo($info)
    {
        $this->info = $info;
    }

    /**
     * @return string
     */
    public function getCarrier()
    {
        return $this->carrier;
    }

    /**
     * @param string $carrier
     */
    public function setCarrier($carrier)
    {
        $this->carrier = $carrier;
    }

    /**
     * @return string
     */
    public function getTrackingId()
    {
        return $this->trackingId;
    }

    /**
     * @param string $trackingId
     */
    public function setTrackingId($trackingId)
    {
        $this->trackingId = $trackingId;
    }

    /**
     * @return string
     */
    public function getSupplier()
    {
        return $this->supplier;
    }

    /**
     * @param string $supplier
     */
    public function setSupplier($supplier)
    {
        $this->supplier = $supplier;
    }
}
