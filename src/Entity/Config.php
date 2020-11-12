<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ConfigRepository")
 */
class Config
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="smallint")
     */
    private $maintenance;

    /**
     * @ORM\Column(type="text")
     */
    private $carousel;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMaintenance(): ?int
    {
        return $this->maintenance;
    }

    public function setMaintenance(int $maintenance): self
    {
        $this->maintenance = $maintenance;

        return $this;
    }

    public function getCarousel(): ?string
    {
        return $this->carousel;
    }

    public function setCarousel(string $carousel): self
    {
        $this->carousel = $carousel;

        return $this;
    }
}
