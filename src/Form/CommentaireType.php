<?php

namespace App\Form;

use App\Entity\Commentaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CommentaireType extends AbstractType
// formulaire de commentaire
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        // le champs commentaire de type textarea
            ->add('commentaire',TextareaType::class,[
                'constraints'=>[
                    // il peut pas etre vide
                    new NotBlank(['message'=>'Veuillez remplir ce champ!']),
                    // contrainte de taille 
                    new Length([
                        'min'=>2,
                        'minMessage' => 'Votre commentaire doit contenir minumum {{ limit }} characteres',
                    ])
                ]
            ])
            // ->add('author')
            // ->add('recette')
        ;
    }
    // lie a une table
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commentaire::class,
        ]);
    }
}
