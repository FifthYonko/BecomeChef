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
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantite',TypeTextType::class)
            // ->add('recette')
            ->add('ingredients',EntityType::class,[
                'class'=>Ingredient::class,
                'choice_label'=>'nom',
                'label'=>'Ingredients',

            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Posseder::class,
        ]);
    }
}
