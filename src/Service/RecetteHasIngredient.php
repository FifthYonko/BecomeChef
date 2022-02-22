<?php

namespace App\Service;

use App\Entity\Ingredient;
use App\Entity\Posseder;
use App\Entity\Recette;
use Doctrine\ORM\EntityManagerInterface;


class RecetteHasIngredient
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function posseder(Ingredient $ingredient, Recette $recette, string $quantite)
    {
        $posseder = new Posseder();
        $posseder->setIngredients($ingredient);
        $posseder->setRecette($recette);
        $posseder->setQuantite($quantite);
        $this->entityManager->persist($posseder);
        $this->entityManager->flush();
    }
}
