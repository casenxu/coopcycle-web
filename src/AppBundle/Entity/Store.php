<?php

namespace AppBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use AppBundle\Entity\Base\LocalBusiness;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * A retail good store.
 *
 * @see http://schema.org/Store Documentation on Schema.org
 *
 * @ORM\Entity
 * @ApiResource(iri="http://schema.org/Store",
 *   attributes={
 *     "normalization_context"={"groups"={"store", "place"}}
 *   },
 *   collectionOperations={
 *     "get"={"method"="GET"}
 *   },
 *   itemOperations={
 *     "get"={"method"="GET"}
 *   }
 * )
 * @Vich\Uploadable
 */
class Store extends LocalBusiness
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string The name of the item
     *
     * @Assert\Type(type="string")
     * @ORM\Column(nullable=true)
     * @ApiProperty(iri="http://schema.org/name")
     * @Groups({"store"})
     */
    protected $name;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", options={"default": false})
     * @Groups({"store"})
     */
    protected $enabled = false;

    /**
     * @Vich\UploadableField(mapping="store_image", fileNameProperty="imageName")
     * @Assert\File(maxSize = "1024k")
     * @var File
     */
    private $imageFile;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string
     */
    private $imageName;

    /**
     * @ORM\OneToOne(targetEntity="Address", cascade={"all"})
     * @Groups({"store"})
     */
    private $address;

    /**
     * @var string The website.
     *
     * @ORM\Column(nullable=true)
     * @ApiProperty(iri="https://schema.org/URL")
     */
    private $website;

    /**
     * @var string The telephone number.
     *
     * @ORM\Column(nullable=true)
     * @Assert\Type(type="string")
     */
    protected $telephone;

    /**
     * @var string The Stripe params
     *
     * @ORM\ManyToOne(targetEntity="StripeParams")
     */
    private $stripeParams;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Delivery\PricingRuleSet", cascade={"persist"})
     * @ORM\JoinColumn(name="pricing_rule_set_id", referencedColumnName="id")
     */
    private $pricingRuleSet;

    /**
     * @ORM\OneToMany(targetEntity="Delivery", mappedBy="store")
     */
    private $deliveries;


    public function __construct() {
        $this->deliveries = new ArrayCollection();
    }

    /**
     * Gets id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets name.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled)
    {
        $this->enabled = $enabled;
    }

    public function getWebsite()
    {
        return $this->website;
    }

    public function setWebsite($website)
    {
        $this->website = $website;

        return $this;
    }

    public function setImageName($imageName)
    {
        $this->imageName = $imageName;

        return $this;
    }

    public function getImageName()
    {
        return $this->imageName;
    }

    /**
     * @return Address
     */
    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress(Address $address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the  update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|Symfony\Component\HttpFoundation\File\UploadedFile $image
     */
    public function setImageFile(File $image = null)
    {
        $this->imageFile = $image;

        if ($image) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    /**
     * @return File|null
     */
    public function getImageFile()
    {
        return $this->imageFile;
    }

    public function getStripeParams()
    {
        return $this->stripeParams;
    }

    public function setStripeParams(StripeParams $stripeParams)
    {
        $this->stripeParams = $stripeParams;

        return $this;
    }

    public function getPricingRuleSet()
    {
        return $this->pricingRuleSet;
    }

    public function setPricingRuleSet($pricingRuleSet)
    {
        $this->pricingRuleSet = $pricingRuleSet;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getDeliveries()
    {
        return $this->deliveries;
    }

    /**
     * @param ArrayCollection $deliveries
     */
    public function setDeliveries(ArrayCollection $deliveries)
    {
        $this->deliveries = $deliveries;
    }

}
