<?php

namespace App\Entity;

use App\Repository\PersonRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

enum Gender: string
{
    case MALE = 'homme';
    case FEMALE = 'femme';
}

#[ORM\Entity(repositoryClass: PersonRepository::class)]
class Person
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'prenom', length: 255)]
    #[Assert\NotBlank]
    private ?string $firstName = null;

    #[ORM\Column(name: 'nom', length: 255)]
    #[Assert\NotBlank]
    private ?string $lastName = null;

    #[ORM\Column(name: 'date_naissance', type: 'date', nullable: true)]
    private ?\DateTimeInterface $birthDate = null;

    #[ORM\Column(name: 'date_deces', type: 'date', nullable: true)]
    private ?\DateTimeInterface $deathDate = null;

    #[ORM\Column(name: 'lieu_naissance', length: 255, nullable: true)]
    private ?string $birthPlace = null;

    #[ORM\Column(name: 'lieu_deces', length: 255, nullable: true)]
    private ?string $deathPlace = null;

    #[ORM\Column(name: 'biographie', type: 'text', nullable: true)]
    private ?string $biography = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'childrenAsFather')]
    #[ORM\JoinColumn(name: 'pere_id', referencedColumnName: 'id', nullable: true)]
    #[Assert\Expression(
        "this.getFather() == null or this.getMother() == null or this.getFather() != this.getMother()",
        message: "Une personne ne peut pas être à la fois le père et la mère"
    )]
    private ?self $father = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'childrenAsMother')]
    #[ORM\JoinColumn(name: 'mere_id', referencedColumnName: 'id', nullable: true)]
    private ?self $mother = null;

    #[ORM\Column(name: 'photo', length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(name: 'sexe', type: 'string', enumType: Gender::class, nullable: true)]
    private ?Gender $gender = null;

    #[ORM\Column(name: 'generation', type: 'integer', nullable: true)]
    private ?int $generation = null;

    #[ORM\OneToMany(mappedBy: 'father', targetEntity: Person::class)]
    private Collection $childrenAsFather;

    #[ORM\OneToMany(mappedBy: 'mother', targetEntity: Person::class)]
    private Collection $childrenAsMother;

    #[ORM\OneToMany(mappedBy: 'personne1', targetEntity: Lien::class)]
    private Collection $liensCommePersonne1;

    #[ORM\OneToMany(mappedBy: 'personne2', targetEntity: Lien::class)]
    private Collection $liensCommePersonne2;

    public function __construct()
    {
        $this->childrenAsFather = new ArrayCollection();
        $this->childrenAsMother = new ArrayCollection();
        $this->liensCommePersonne1 = new ArrayCollection();
        $this->liensCommePersonne2 = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeInterface $birthDate): static
    {
        $this->birthDate = $birthDate;
        return $this;
    }

    public function getDeathDate(): ?\DateTimeInterface
    {
        return $this->deathDate;
    }

    public function setDeathDate(?\DateTimeInterface $deathDate): static
    {
        $this->deathDate = $deathDate;
        return $this;
    }

    public function getBirthPlace(): ?string
    {
        return $this->birthPlace;
    }

    public function setBirthPlace(?string $birthPlace): static
    {
        $this->birthPlace = $birthPlace;
        return $this;
    }

    public function getDeathPlace(): ?string
    {
        return $this->deathPlace;
    }

    public function setDeathPlace(?string $deathPlace): static
    {
        $this->deathPlace = $deathPlace;
        return $this;
    }

    public function getBiography(): ?string
    {
        return $this->biography;
    }

    public function setBiography(?string $biography): static
    {
        $this->biography = $biography;
        return $this;
    }

    public function getFather(): ?self
    {
        return $this->father;
    }

    public function setFather(?self $father): static
    {
        $this->father = $father;
        return $this;
    }

    public function getMother(): ?self
    {
        return $this->mother;
    }

    public function setMother(?self $mother): static
    {
        $this->mother = $mother;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;
        return $this;
    }

    public function getGender(): ?Gender
    {
        return $this->gender;
    }

    public function setGender(?Gender $gender): static
    {
        $this->gender = $gender;
        return $this;
    }

    public function getGeneration(): ?int
    {
        return $this->generation;
    }

    public function setGeneration(?int $generation): static
    {
        $this->generation = $generation;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getChildren(): Collection
    {
        return new ArrayCollection(
            array_merge(
                $this->childrenAsFather->toArray(),
                $this->childrenAsMother->toArray()
            )
        );
    }

    /**
     * @return Collection<int, Lien>
     */
    public function getLiensCommePersonne1(): Collection
    {
        return $this->liensCommePersonne1;
    }

    public function addLiensCommePersonne1(Lien $lien): static
    {
        if (!$this->liensCommePersonne1->contains($lien)) {
            $this->liensCommePersonne1->add($lien);
            $lien->setPersonne1($this);
        }

        return $this;
    }

    public function removeLiensCommePersonne1(Lien $lien): static
    {
        if ($this->liensCommePersonne1->removeElement($lien)) {
            if ($lien->getPersonne1() === $this) {
                $lien->setPersonne1(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Lien>
     */
    public function getLiensCommePersonne2(): Collection
    {
        return $this->liensCommePersonne2;
    }

    public function addLiensCommePersonne2(Lien $lien): static
    {
        if (!$this->liensCommePersonne2->contains($lien)) {
            $this->liensCommePersonne2->add($lien);
            $lien->setPersonne2($this);
        }

        return $this;
    }

    public function removeLiensCommePersonne2(Lien $lien): static
    {
        if ($this->liensCommePersonne2->removeElement($lien)) {
            if ($lien->getPersonne2() === $this) {
                $lien->setPersonne2(null);
            }
        }

        return $this;
    }

    /**
     * Récupère tous les liens de cette personne
     * @return Collection<Lien>
     */
    public function getTousLesLiens(): Collection
    {
        return new ArrayCollection(
            array_merge(
                $this->liensCommePersonne1->toArray(),
                $this->liensCommePersonne2->toArray()
            )
        );
    }

    /**
     * Récupère les liens d'un type spécifique
     * @param string $typeLienNom
     * @return Collection<Lien>
     */
    public function getLiensParType(string $typeLienNom): Collection
    {
        $liens = new ArrayCollection();
        
        foreach ($this->getTousLesLiens() as $lien) {
            if ($lien->getTypeLien()->getNom() === $typeLienNom) {
                $liens->add($lien);
            }
        }
        
        return $liens;
    }

    /**
     * Récupère les parents (père et mère biologiques ou adoptifs)
     * @return Collection<Person>
     */
    public function getParents(): Collection
    {
        $parents = new ArrayCollection();
        
        // Ajouter les parents via les anciens champs (pour compatibilité)
        if ($this->father) {
            $parents->add($this->father);
        }
        if ($this->mother) {
            $parents->add($this->mother);
        }
        
        // Ajouter les parents via les nouveaux liens
        foreach ($this->getTousLesLiens() as $lien) {
            $typeLien = $lien->getTypeLien();
            if ($typeLien->isEstParental() && $lien->isActifADate()) {
                $parent = $lien->getAutrePersonne($this);
                if ($parent && !$parents->contains($parent)) {
                    $parents->add($parent);
                }
            }
        }
        
        return $parents;
    }

    /**
     * Récupère tous les frères et sœurs d'une personne
     * @return Collection<Person>
     */
    public function getSiblings(): Collection
    {
        $siblings = new ArrayCollection();
        
        // Récupérer les enfants des mêmes parents (ancienne méthode)
        if ($this->father) {
            foreach ($this->father->getChildren() as $sibling) {
                if ($sibling !== $this) {
                    $siblings->add($sibling);
                }
            }
        }
        
        if ($this->mother) {
            foreach ($this->mother->getChildren() as $sibling) {
                if ($sibling !== $this && !$siblings->contains($sibling)) {
                    $siblings->add($sibling);
                }
            }
        }
        
        // Récupérer les frères et sœurs via les nouveaux liens
        foreach ($this->getParents() as $parent) {
            foreach ($parent->getEnfants() as $enfant) {
                if ($enfant !== $this && !$siblings->contains($enfant)) {
                    $siblings->add($enfant);
                }
            }
        }
        
        return $siblings;
    }

    /**
     * Récupère tous les enfants (biologiques et adoptifs)
     * @return Collection<Person>
     */
    public function getEnfants(): Collection
    {
        $enfants = new ArrayCollection();
        
        // Ajouter les enfants via les anciens champs (pour compatibilité)
        foreach ($this->getChildren() as $child) {
            $enfants->add($child);
        }
        
        // Ajouter les enfants via les nouveaux liens
        foreach ($this->getTousLesLiens() as $lien) {
            $typeLien = $lien->getTypeLien();
            if ($typeLien->isEstParental() && $lien->isActifADate()) {
                $enfant = $lien->getAutrePersonne($this);
                if ($enfant && !$enfants->contains($enfant)) {
                    // Vérifier que c'est bien un lien parent->enfant et non enfant->parent
                    if ($lien->getPersonne1() === $this) {
                        $enfants->add($enfant);
                    }
                }
            }
        }
        
        return $enfants;
    }
}
