<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @method string getUserIdentifier()
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


#[ORM\Column(length: 255)]
#[Assert\NotBlank(message: "The last name is required.")]
#[Assert\Length(
    min: 3,
    minMessage: "The last name must be at least {{ limit }} characters long."
)]
#[Assert\Regex(
    pattern: "/^[a-zA-ZÀ-ÿ\s]+$/",
    message: "The last name should only contain letters."
)]
private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "The first name is required.")]
    #[Assert\Length(
        min: 3,
        minMessage: "The first name must be at least {{ limit }} characters long."
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s]+$/",
        message: "The first name should only contain letters and spaces."
    )]
    private ?string $prenom = null;

    #[ORM\Column]
    #[Assert\Range(
        notInRangeMessage: "You must be between {{ min }} and {{ max }} old to enter",
        min: 8,
        max: 100
    )]
    private ?int $age = null;

#[ORM\Column(length: 255)]
private ?string $email = null;

#[ORM\Column(length: 255)]
#[Assert\NotBlank(message: "The password is required.")]
#[Assert\Length(
    min: 8,
    minMessage: "The password must be at least {{ limit }} characters long."
)]
#[Assert\Regex(
    pattern: "/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/",
    message: "The password must contain at least one letter, one number, and one special character."
)]
private ?string $mdp = null;


    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(nullable: true)]
    private ?int $theme = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pageNom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\OneToMany(targetEntity: Section::class, mappedBy: 'id_user')]
    private Collection $sections;

    #[ORM\OneToMany(targetEntity: Continent::class, mappedBy: 'id_user')]
    private Collection $continents;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $systemDate = null;

    #[ORM\OneToMany(targetEntity: Aventure::class, mappedBy: 'IdUser')]
    private Collection $aventures;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $facebook = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $instagram = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $twitter = null;


    #[ORM\OneToMany(targetEntity: Podcast::class, mappedBy: 'idUser')]
    private Collection $podcasts;

    #[ORM\OneToMany(targetEntity: AddFriend::class, mappedBy: 'User_id')]
    private Collection $addFriends;

    #[ORM\Column(nullable: true)]
    private ?int $Star = null;

    public function __construct()
    {
        $this->sections = new ArrayCollection();
        $this->continents = new ArrayCollection();
        $this->aventures = new ArrayCollection();
        $this->podcasts = new ArrayCollection();
        $this->friends = new ArrayCollection();
        $this->addFriends = new ArrayCollection();
    }

    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'nom' => $this->getNom(),
            'prenom' => $this->getPrenom(),
            'email' => $this->getEmail(),
        ];
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(int $age): static
    {
        $this->age = $age;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getMdp(): ?string
    {
        return $this->mdp;
    }

    public function setMdp(string $mdp): static
    {
        $this->mdp = $mdp;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getTheme(): ?int
    {
        return $this->theme;
    }

    public function setTheme(int $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    public function getPageNom(): ?string
    {
        return $this->pageNom;
    }

    public function setPageNom(string $pageNom): static
    {
        $this->pageNom = $pageNom;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(string $logo): static
    {
        $this->logo = $logo;

        return $this;
    }


    /**
     * @return Collection<int, Section>
     */
    public function getSections(): Collection
    {
        return $this->sections;
    }

    public function addSection(Section $section): static
    {
        if (!$this->sections->contains($section)) {
            $this->sections->add($section);
            $section->setIdUser($this);
        }

        return $this;
    }

    public function removeSection(Section $section): static
    {
        if ($this->sections->removeElement($section)) {
            // set the owning side to null (unless already changed)
            if ($section->getIdUser() === $this) {
                $section->setIdUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Continent>
     */
    public function getContinents(): Collection
    {
        return $this->continents;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getPassword(): string
    {
        return $this->mdp;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function getUsername(): string
    {
        return $this->email;
    }

    public function eraseCredentials()
    {
        // Si vous stockez des données sensibles temporaires sur l'utilisateur, nettoyez-les ici
    }

    public function getSystemDate(): ?\DateTimeInterface
    {
        return $this->systemDate;
    }

    public function setSystemDate(\DateTimeInterface $systemDate): static
    {
        $this->systemDate = $systemDate;

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
            $aventure->setIdUser($this);
        }

        return $this;
    }

    public function removeAventure(Aventure $aventure): static
    {
        if ($this->aventures->removeElement($aventure)) {
            // set the owning side to null (unless already changed)
            if ($aventure->getIdUser() === $this) {
                $aventure->setIdUser(null);
            }
        }

        return $this;
    }

    public function getFacebook(): ?string
    {
        return $this->facebook;
    }

    public function setFacebook(string $facebook): static
    {
        $this->facebook = $facebook;

        return $this;
    }

    public function getInstagram(): ?string
    {
        return $this->instagram;
    }

    public function setInstagram(?string $instagram): static
    {
        $this->instagram = $instagram;

        return $this;
    }

    public function getTwitter(): ?string
    {
        return $this->twitter;
    }

    public function setTwitter(?string $twitter): static
    {
        $this->twitter = $twitter;

        return $this;
    }


    /**
     * @return Collection<int, Podcast>
     */
    public function getPodcasts(): Collection
    {
        return $this->podcasts;
    }

    public function addPodcast(Podcast $podcast): static
    {
        if (!$this->podcasts->contains($podcast)) {
            $this->podcasts->add($podcast);
            $podcast->setIdUser($this);
        }

        return $this;
    }

    public function removePodcast(Podcast $podcast): static
    {
        if ($this->podcasts->removeElement($podcast)) {
            // set the owning side to null (unless already changed)
            if ($podcast->getIdUser() === $this) {
                $podcast->setIdUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AddFriend>
     */
    public function getAddFriends(): Collection
    {
        return $this->addFriends;
    }

    public function addAddFriend(AddFriend $addFriend): static
    {
        if (!$this->addFriends->contains($addFriend)) {
            $this->addFriends->add($addFriend);
            $addFriend->setUserId($this);
        }

        return $this;
    }

    public function removeAddFriend(AddFriend $addFriend): static
    {
        if ($this->addFriends->removeElement($addFriend)) {
            // set the owning side to null (unless already changed)
            if ($addFriend->getUserId() === $this) {
                $addFriend->setUserId(null);
            }
        }

        return $this;
    }

    public function getStar(): ?int
    {
        return $this->Star;
    }

    public function setStar(?int $Star): static
    {
        $this->Star = $Star;

        return $this;
    }
}
