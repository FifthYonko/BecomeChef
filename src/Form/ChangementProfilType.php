<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
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
        ->add('pseudo',TextType::class,[
            'attr'=>[
                'placeholder'=>'Votre Pseudo'
            ],
            'constraints'=>[
                new NotBlank(['message'=>'Entrez un pseudo valide']),
                new Length([
                    'min'=>3,
                    'minMessage' => 'Votre Pseudo doit contenir minumum {{ limit }} caractères',
                ])
            ]
        ])
            ->add('photo', FileType::class, [
                'label' => 'Ajoutez/Modifiez la photo',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new ConstraintsFile([
                        'maxSize' => '2048k',
                        'maxSizeMessage' => 'La taille autorisé est de {{ limit }}k  ',
                        'mimeTypes' => [
                            'image/*',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image!',
                    ])
                ],
                'label_attr'=>[
                    'class'=>'LabelBtn coloredText  titres fs-5'
                ],
                'attr'=>[
                    'class'=>'d-none'
                ]
              
            ])
            ->add('adresse',TextType::class,[
                'required'=>false,
            ])
            ->add('codePostal',NumberType::class,[
                'required'=> false,
            ])
            ->add('ville',TextType::class,[
                'required'=>false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
