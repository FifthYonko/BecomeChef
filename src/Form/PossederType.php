<?php

namespace App\Form;

use App\Entity\Ingredient;
use App\Entity\Posseder;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType as TypeTextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PossederType extends AbstractType
{
    // formulaire d'ajout dans la table Posseder
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        // champ ou on ajoute la quantite
            ->add('quantite',TypeTextType::class,[
                'attr'=>['class'=>'formInput mb-2'],
                'label_attr'=>['class'=>'titres'],
            ])
        //   champ ou on choisi l'ingredient a ajouter
        // de type Entity, ce qui nous permet d'ajouter les infos existants dans une autre table
            ->add('ingredients',EntityType::class,[
                // on donne la classe a laquelle ce champ est relie
                'class'=>Ingredient::class,
                // on donne le champ de la classe qu'on veut
                'choice_label'=>'nom',
                // on donne l'etiquete qu'on veut afficher au champ et qq attributs
                'attr'=>['class'=>'formInput selector '],
                'label'=>'Ingredients',
                'label_attr'=>['class'=>'titres'],
            ])
        ;
    }
// formulaire lie a la table Posseder
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Posseder::class,
        ]);
    }
}
