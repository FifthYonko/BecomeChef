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
            ->add('quantite',TypeTextType::class,[
                'attr'=>['class'=>'formInput mb-2'],
                'label_attr'=>['class'=>'titres'],
            ])
    
            ->add('ingredients',EntityType::class,[
                'class'=>Ingredient::class,
                'choice_label'=>'nom',
                'attr'=>['class'=>'formInput selector '],
                'label'=>'Ingredients',
                'label_attr'=>['class'=>'titres'],
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
