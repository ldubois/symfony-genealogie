<?php

namespace App\Entity;

use App\Repository\TypeLienRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TypeLienRepository::class)]
class TypeLien
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'nom', length: 50)]
    #[Assert\NotBlank]
    private ?string $nom = null;

    #[ORM\Column(name: 'description', length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'est_biologique')]
    private ?bool $estBiologique = null;

    #[ORM\Column(name: 'est_parental')]
    private ?bool $estParental = null;

    #[ORM\OneToMany(mappedBy: 'typeLien', targetEntity: Lien::class)]
    private Collection $liens;

    public function __construct()
    {
        $this->liens = new ArrayCollection();
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

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function isEstBiologique(): ?bool
    {
        return $this->estBiologique;
    }

    public function setEstBiologique(bool $estBiologique): static
    {
        $this->estBiologique = $estBiologique;
        return $this;
    }

    public function isEstParental(): ?bool
    {
        return $this->estParental;
    }

    public function setEstParental(bool $estParental): static
    {
        $this->estParental = $estParental;
        return $this;
    }

    /**
     * @return Collection<int, Lien>
     */
    public function getLiens(): Collection
    {
        return $this->liens;
    }

    public function addLien(Lien $lien): static
    {
        if (!$this->liens->contains($lien)) {
            $this->liens->add($lien);
            $lien->setTypeLien($this);
        }

        return $this;
    }

    public function removeLien(Lien $lien): static
    {
        if ($this->liens->removeElement($lien)) {
            // set the owning side to null (unless already changed)
            if ($lien->getTypeLien() === $this) {
                $lien->setTypeLien(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }
}