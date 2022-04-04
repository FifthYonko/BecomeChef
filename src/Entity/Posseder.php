<?php

namespace App\Entity;

use App\Repository\PossederRepository;
use Doctrine\ORM\Mapping as ORM;
/**
 * Entite posseder, c'est une table qui fait le lien entre les recettes et les ingredients qu'ils possedent
 */
#[ORM\Entity(repositoryClass: PossederRepository::class)]
class Posseder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    // colonne id 
    private $id;

    #[ORM\ManyToOne(targetEntity: Recette::class, inversedBy: 'posseders')]
    #[ORM\JoinColumn(nullable: false)]
    private $recette;

    #[ORM\ManyToOne(targetEntity: Ingredient::class, inversedBy: 'posseders')]
    #[ORM\JoinColumn(nullable: false)]
    private $ingredients;

    #[ORM\Column(type: 'string', length: 255)]
    private $quantite;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecette(): ?Recette
    {
        return $this->recette;
    }

    public function setRecette(?Recette $recette): self
    {
        $this->recette = $recette;

        return $this;
    }

    public function getIngredients(): ?Ingredient
    {
        return $this->ingredients;
    }

    public function setIngredients(?Ingredient $ingredients): self
    {
        $this->ingredients = $ingredients;

        return $this;
    }

    public function getQuantite(): ?string
    {
        return $this->quantite;
    }

    public function setQuantite(string $quantite): self
    {
        $this->quantite = $quantite;

        return $this;
    }
}
