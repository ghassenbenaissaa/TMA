<?php

namespace App\Entity;

use App\Repository\AddFriendRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AddFriendRepository::class)]
class AddFriend
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'addFriends')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $User_id = null;

    #[ORM\Column]
    private ?int $user_id2 = null;

    #[ORM\Column]
    private ?int $confirmation = null;

    #[ORM\Column(length: 255)]
    private ?string $etat = null;

    #[ORM\Column]
    private ?int $notification = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?User
    {
        return $this->User_id;
    }

    public function setUserId(?User $User_id): static
    {
        $this->User_id = $User_id;

        return $this;
    }

    public function getUserId2(): ?int
    {
        return $this->user_id2;
    }

    public function setUserId2(int $user_id2): static
    {
        $this->user_id2 = $user_id2;

        return $this;
    }

    public function getConfirmation(): ?int
    {
        return $this->confirmation;
    }

    public function setConfirmation(int $confirmation): static
    {
        $this->confirmation = $confirmation;

        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    public function getNotification(): ?int
    {
        return $this->notification;
    }

    public function setNotification(int $notification): static
    {
        $this->notification = $notification;

        return $this;
    }
}
