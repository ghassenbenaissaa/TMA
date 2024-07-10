<?php

namespace App\Entity;

use App\Repository\ImageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImageRepository::class)]
class Image
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\ManyToOne(inversedBy: 'images')]
    #[ORM\JoinColumn(nullable: false)]
    private ?aventure $idAventure = null;

    #[ORM\ManyToOne(inversedBy: 'images')]
    private ?Podcast $idPodcast = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getIdAventure(): ?aventure
    {
        return $this->idAventure;
    }

    public function setIdAventure(?aventure $idAventure): static
    {
        $this->idAventure = $idAventure;

        return $this;
    }

    public function getIdPodcast(): ?Podcast
    {
        return $this->idPodcast;
    }

    public function setIdPodcast(?Podcast $idPodcast): static
    {
        $this->idPodcast = $idPodcast;

        return $this;
    }

}
