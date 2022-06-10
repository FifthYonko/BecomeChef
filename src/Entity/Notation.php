<?php

namespace App\Entity;

use App\Repository\NotationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: NotationRepository::class)]
/**
 * @ORM\Entity
 * @UniqueEntity(
 *     fields={"noteur", "recetteNote"},
 *     errorPath="noteur",
 *     message="Vous avez déjà donné une note à cette recette"
 * )
 */
class Notation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'notations')]
    #[ORM\JoinColumn(nullable: false)]
    private $noteur;

    #[ORM\ManyToOne(targetEntity: Recette::class, inversedBy: 'notations')]
    #[ORM\JoinColumn(nullable: false)]
    private $recetteNote;

    #[ORM\Column(type: 'integer')]
    private $note;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNoteur(): ?User
    {
        return $this->noteur;
    }

    public function setNoteur(?User $noteur): self
    {
        $this->noteur = $noteur;

        return $this;
    }

    public function getRecetteNote(): ?Recette
    {
        return $this->recetteNote;
    }

    public function setRecetteNote(?Recette $recetteNote): self
    {
        $this->recetteNote = $recetteNote;

        return $this;
    }

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(int $note): self
    {
        $this->note = $note;

        return $this;
    }
}
