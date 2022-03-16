<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\File as ConstraintsFile;

class ChangementProfilType extends AbstractType
{
    // formulaire de modification de profil
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        // le champ pseudo
        ->add('pseudo',TextType::class,[
            // attribut placeholder
            'attr'=>[
                'placeholder'=>'Votre Pseudo'
            ],
            // contraintes
            'constraints'=>[
                // il peut pas etre vide
                new NotBlank(['message'=>'Entrez un pseudo valide']),
                // contrainte de taille 
                new Length([
                    'min'=>3,
                    'minMessage' => 'Votre Pseudo doit contenir minumum {{ limit }} characteres',
                ])
            ]
        ])
            // champ photo 
            ->add('photo', FileType::class, [
                // label, etiquette du champ a afficher
                'label' => 'photo',
                // on n'ajoute pas les infos de ce champs dans la bdd
                'mapped' => false,
                // il peut etre null
                'required' => false,
                // contraintes
                'constraints' => [
                    new ConstraintsFile([
                        // contraintes de taille
                        'maxSize' => '1024k',
                        // contraintes de type
                        'mimeTypes' => [
                            'image/*',
                        ],
                        // message a afficher quand le fichier n'est pas une image
                        'mimeTypesMessage' => 'Veuillez uploader une image!',
                    ])
                ],
            ])
        ;
    }
    // formulaire lie a une table
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
