<?php

namespace MxcDropshipInnocigs\Models;

use Doctrine\ORM\Mapping as ORM;
use MxcCommons\Toolbox\Models\PrivatePropertyAccessTrait;
use MxcCommons\Toolbox\Models\PrimaryKeyTrait;
use MxcCommons\Toolbox\Models\TrackCreationAndUpdateTrait;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_mxcbc_dsi_innocigs_model")
 * @ORM\Entity(repositoryClass="ModelRepository")
 */
class Model extends ModelEntity
{
    use PrimaryKeyTrait;
    use TrackCreationAndUpdateTrait;
    use PrivatePropertyAccessTrait;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $category;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    private $master;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    private $model;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $ean;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(name="product_name", type="string", nullable=true)
     */
    private $productName;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $unit;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $content;

    /**
     * @var string $description
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @var string $purchasePrice
     * @ORM\Column(name="purchase_price", type="string", nullable=false)
     */
    private $purchasePrice;

    /**
     * @var string
     * @ORM\Column(name="uvp", type="string", nullable=false)
     */
    private $recommendedRetailPrice;

    /**
     * @var string
     * @ORM\Column(name="images", type="text", nullable=true)
     */
    private $images;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $manufacturer;

    /**
     * @var string $manual ;
     * @ORM\Column(name="manual", type="string", nullable=true)
     */
    private $manual;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $options;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $deleted = false;

    public function fromImport(array $data) {
        foreach ($data as $key => $value) {
            if ($value === null) continue;
            $setter = 'set' . ucfirst($key);
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            }
        }
    }

    /**
     * @return null|string
     */
    public function getCategory(): ?string
    {
        return $this->category;
    }

    /**
     * @param null|string $category
     */
    public function setCategory(?string $category)
    {
        $this->category = $category;
    }

    /**
     * @return string
     */
    public function getMaster(): string
    {
        return $this->master;
    }

    /**
     * @param string $master
     */
    public function setMaster(string $master)
    {
        $this->master = $master;
    }

    /**
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * @param string $model
     */
    public function setModel(string $model)
    {
        $this->model = $model;
    }

    /**
     * @return null|string
     */
    public function getEan(): ?string
    {
        return $this->ean;
    }

    /**
     * @param null|string $ean
     */
    public function setEan(?string $ean)
    {
        $this->ean = $ean;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getPurchasePrice(): string
    {
        return $this->purchasePrice;
    }

    /**
     * @param string $purchasePrice
     */
    public function setPurchasePrice(string $purchasePrice)
    {
        $this->purchasePrice = $purchasePrice;
    }

    /**
     * @return string
     */
    public function getRecommendedRetailPrice(): string
    {
        return $this->recommendedRetailPrice;
    }

    /**
     * @param string $recommendedRetailPrice
     */
    public function setRecommendedRetailPrice(string $recommendedRetailPrice)
    {
        $this->recommendedRetailPrice = $recommendedRetailPrice;
    }

    /**
     * @return null|string
     */
    public function getImages() : ?string
    {
        return $this->images;
    }

    /**
     * @param null|string $images
     */
    public function setImages(?string $images)
    {
        $this->images = $images;
    }

    /**
     * @return null|string
     */
    public function getManufacturer(): ?string
    {
        return $this->manufacturer;
    }

    /**
     * @param null|string $manufacturer
     */
    public function setManufacturer(?string $manufacturer)
    {
        $this->manufacturer = $manufacturer;
    }

    /**
     * @return null|string
     */
    public function getManual(): ?string
    {
        return $this->manual;
    }

    /**
     * @param null|string $manual
     */
    public function setManual(?string $manual)
    {
        $this->manual = $manual;
    }

    /**
     * @return string
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $options
     */
    public function setOptions(string $options)
    {
        $this->options = $options;
    }

    /**
     * @return bool
     */
    public function getDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @param bool $deleted
     */
    public function setDeleted(bool $deleted)
    {
        $this->deleted = $deleted;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getProductName()
    {
        return $this->productName;
    }

    /**
     * @param mixed $productName
     */
    public function setProductName($productName)
    {
        $this->productName = $productName;
    }

    /**
     * @return mixed
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param mixed $unit
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }
}
