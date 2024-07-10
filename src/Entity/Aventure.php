<?php

namespace App\Entity;

use App\Repository\AventureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AventureRepository::class)]
class Aventure
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'aventures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?pays $id_pays = null;

    #[ORM\ManyToOne(inversedBy: 'aventures')]
    private ?User $IdUser = null;

    #[ORM\OneToMany(targetEntity: Image::class, mappedBy: 'idAventure', cascade: ['persist', 'remove'])]
    private Collection $images;

    #[ORM\Column]
    private ?bool $recommander = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Regex( pattern :"/^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/" , message:"Please enter a valid YouTube video link." )]
    private ?string $video = null;

    public function __construct()
    {
        $this->images = new ArrayCollection();
    }

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }


    public function getIdPays(): ?pays
    {
        return $this->id_pays;
    }

    public function setIdPays(?pays $id_pays): static
    {
        $this->id_pays = $id_pays;

        return $this;
    }

    public function getIdUser(): ?User
    {
        return $this->IdUser;
    }

    public function setIdUser(?User $IdUser): static
    {
        $this->IdUser = $IdUser;

        return $this;
    }

    /**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setIdAventure($this);
        }

        return $this;
    }

    public function removeImage(Image $image): static
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getIdAventure() === $this) {
                $image->setIdAventure(null);
            }
        }

        return $this;
    }

    public function getRecommander(): ?bool
    {
        return $this->recommander;
    }

    public function setRecommander(int $recommander): self
    {
        $this->recommander = $recommander;

        return $this;
    }

    public function getVideo(): ?string
    {
        return $this->video;
    }

    public function setVideo(?string $video): static
    {
        $this->video = $video;

        return $this;
    }
}
