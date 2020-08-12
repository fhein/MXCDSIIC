<?php

namespace MxcDropshipInnocigs\Models;

use Doctrine\ORM\Mapping as ORM;
use MxcDropshipIntegrator\Models\BaseModelTrait;
use MxcCommons\Toolbox\Models\PrimaryKeyTrait;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="s_plugin_mxc_dsi_innocigs_order_attributes")
 */
class OrderAttributes extends ModelEntity
{
    use PrimaryKeyTrait;

    /**
     * @var int
     * @ORM\Column(name="order_id", type="integer", nullable=false)
     */
    private $orderId;

    /**
     * @var bool
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active;

    /**
     * @var int
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var int
     * @ORM\Column(name="cron_status", type="integer", nullable=false)
     */
    private $cronStatus;
}
