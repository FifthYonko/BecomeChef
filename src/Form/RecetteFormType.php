<?php

namespace App\Form;

use App\Entity\Ingredient;
use App\Entity\Recette;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RecetteFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'attr' => [
                    'placeholder' => 'Le titre de la recette',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez completer ce champ',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Le titre doit contenir minumum {{limit}} caracteres',
                    ])
                ]
            ])
            ->add('intro', TextType::class, [
                'attr' => [
                    'placeholder' => 'Nombre de personnes, temps de preparation',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez completer ce champ',
                    ]),
                    new Length([
                        'min' => 25,
                        'minMessage' => 'Ce champ doit contenir minimum {{limit}} caracteres',
                    ])
                ]
            ])
            ->add('preparation', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'Veuillez bien detailler le processus de preparation de la recette',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez completer ce champ',]),
                    new Length([
                        'min' => 200,
                        'minMessage' => 'Ce champ necessite minimum {{ limit }} caracteres'
                    ])
                ]
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/*',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image!',
                    ])
                ]
            ])
          
            ->add('ingredients', EntityType::class, [
                    'label' => 'Ingredients',
                    'class' => Ingredient::class,
                    'choice_label' => 'nom',
                    'expanded' => true,
                    'multiple' => true,
                    'mapped'=>false,

                ])
            ;
               
             
           
                
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recette::class,

        ]);
    }
}
