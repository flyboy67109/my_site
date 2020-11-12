<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ImagesRepository")
 */
class Images
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $fileName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $postDate;

    /**
     * @ORM\Column(type="integer")
     */
    private $sectionsId;

    /**
     * @ORM\Column(type="text")
     */
    private $imageDescription;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getPostDate(): ?string
    {
        return $this->postDate;
    }

    public function setPostDate(string $postDate): self
    {
        $this->postDate = $postDate;

        return $this;
    }

    public function getSectionsId(): ?int
    {
        return $this->sectionsId;
    }

    public function setSectionsId(int $sectionsId): self
    {
        $this->sectionsId = $sectionsId;

        return $this;
    }

    public function getImageDescription(): ?string
    {
        return $this->imageDescription;
    }

    public function setImageDescription(string $imageDescription): self
    {
        $this->imageDescription = $imageDescription;

        return $this;
    }
}
