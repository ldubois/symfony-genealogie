<?php

namespace App\Entity;

use App\Repository\LienRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LienRepository::class)]
class Lien
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Person::class, inversedBy: 'liensCommePersonne1')]
    #[ORM\JoinColumn(name: 'personne1_id', nullable: false)]
    #[Assert\NotNull]
    private ?Person $personne1 = null;

    #[ORM\ManyToOne(targetEntity: Person::class, inversedBy: 'liensCommePersonne2')]
    #[ORM\JoinColumn(name: 'personne2_id', nullable: false)]
    #[Assert\NotNull]
    private ?Person $personne2 = null;

    #[ORM\ManyToOne(targetEntity: TypeLien::class, inversedBy: 'liens')]
    #[ORM\JoinColumn(name: 'type_lien_id', nullable: false)]
    #[Assert\NotNull]
    private ?TypeLien $typeLien = null;

    #[ORM\Column(name: 'date_debut', type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(name: 'date_fin', type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(name: 'notes', type: 'text', nullable: true)]
    private ?string $notes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPersonne1(): ?Person
    {
        return $this->personne1;
    }

    public function setPersonne1(?Person $personne1): static
    {
        $this->personne1 = $personne1;
        return $this;
    }

    public function getPersonne2(): ?Person
    {
        return $this->personne2;
    }

    public function setPersonne2(?Person $personne2): static
    {
        $this->personne2 = $personne2;
        return $this;
    }

    public function getTypeLien(): ?TypeLien
    {
        return $this->typeLien;
    }

    public function setTypeLien(?TypeLien $typeLien): static
    {
        $this->typeLien = $typeLien;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    /**
     * Vérifie si le lien est actif à une date donnée
     */
    public function isActifADate(?\DateTimeInterface $date = null): bool
    {
        if ($date === null) {
            $date = new \DateTime();
        }

        $debutOk = $this->dateDebut === null || $this->dateDebut <= $date;
        $finOk = $this->dateFin === null || $this->dateFin >= $date;

        return $debutOk && $finOk;
    }

    /**
     * Retourne l'autre personne du lien
     */
    public function getAutrePersonne(Person $personne): ?Person
    {
        if ($this->personne1 === $personne) {
            return $this->personne2;
        } elseif ($this->personne2 === $personne) {
            return $this->personne1;
        }
        
        return null;
    }
}