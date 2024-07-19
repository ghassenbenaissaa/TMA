<?php

namespace App\Entity;

use App\Repository\FriendRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FriendRepository::class)]
class Friend
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: user::class, inversedBy: 'id_2')]
    private Collection $id_user;

    #[ORM\Column]
    private ?int $id_2 = null;

    public function __construct()
    {
        $this->id_user = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, user>
     */
    public function getIdUser(): Collection
    {
        return $this->id_user;
    }

    public function addIdUser(user $idUser): static
    {
        if (!$this->id_user->contains($idUser)) {
            $this->id_user->add($idUser);
        }

        return $this;
    }

    public function removeIdUser(user $idUser): static
    {
        $this->id_user->removeElement($idUser);

        return $this;
    }

    public function getId2(): ?int
    {
        return $this->id_2;
    }

    public function setId2(int $id_2): static
    {
        $this->id_2 = $id_2;

        return $this;
    }
}
