<?php

namespace App\Entity;

use App\Repository\PaysRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaysRepository::class)]
class Pays
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\ManyToOne(inversedBy: 'pays')]
    private ?continent $id_continent = null;

    #[ORM\OneToMany(targetEntity: Aventure::class, mappedBy: 'id_pays')]
    private Collection $aventures;

    public function __construct()
    {
        $this->aventures = new ArrayCollection();
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

    public function getIdContinent(): ?continent
    {
        return $this->id_continent;
    }

    public function setIdContinent(?continent $id_continent): static
    {
        $this->id_continent = $id_continent;

        return $this;
    }

    /**
     * @return Collection<int, Aventure>
     */
    public function getAventures(): Collection
    {
        return $this->aventures;
    }

    public function addAventure(Aventure $aventure): static
    {
        if (!$this->aventures->contains($aventure)) {
            $this->aventures->add($aventure);
            $aventure->setIdPays($this);
        }

        return $this;
    }

    public function removeAventure(Aventure $aventure): static
    {
        if ($this->aventures->removeElement($aventure)) {
            // set the owning side to null (unless already changed)
            if ($aventure->getIdPays() === $this) {
                $aventure->setIdPays(null);
            }
        }

        return $this;
    }
}
