<?php

namespace AppBundle\Entity\Base;

use ApiPlatform\Core\Annotation\ApiProperty;
use Symfony\Component\Validator\Constraints as Assert;

class BaseAddress extends Place
{
    /**
     * @var string Additional instructions about the place
     *
     * @Assert\Type(type="string")
     * @ApiProperty(iri="https://schema.org/addressLocality")
     */
    protected $description;

    /**
     * @var string Floor
     *
     * @Assert\Type(type="string")
     * @ApiProperty(iri="https://schema.org/addressLocality")
     */
    protected $floor;

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
    public function setDescription(string $description = null)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getFloor()
    {
        return $this->floor;
    }

    /**
     * @param string $floor
     */
    public function setFloor(string $floor = null)
    {
        $this->floor = $floor;
    }
}
