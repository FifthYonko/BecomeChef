<?php

namespace App\Form;

use App\Entity\Ingredient;
use App\Entity\Recette;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use FOS\CKEditorBundle\Form\Type\CKEditorType;

/**
 * Formulaire d'ajout de recette
 */
class RecetteFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'attr' => [
                    'placeholder' => 'Le titre de la recette',
                  
                ],
                'label_attr'=>['class'=>'titres'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez compléter ce champ',
                        
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Le titre doit contenir minumum {{ limit }} caractères',
                    ])
                ]
            ])
            ->add('temps', TextType::class, [
                'attr' => [
                    'placeholder' => 'Temps de préparation',
                  
                ],
                'label_attr'=>['class'=>'titres'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez compléter ce champ',
                    ]),
                    new Length([
                        'min' => 1,
                        'minMessage' => 'Ce champ doit contenir minimum {{ limit }} caractères',
                    ])
                ]
            ])
            ->add('nbPersonnes', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'Nombre de personnes',
                    'min' => 1,
                    
                ],
               
            ])
            ->add('preparation', CKEditorType::class, [
                'attr' => [
                    'placeholder' => 'Veuillez bien détailler le processus de préparation de la recette',
                   
                ],
                'label_attr'=>['class'=>'titres'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez compléter ce champ',]),
                    new Length([
                        'min' => 200,
                        'minMessage' => 'Ce champ nécessite minimum {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo',
                'label_attr'=>['class'=>'titres'],
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

            ->add('posseders', CollectionType::class, [
                    'label' => false,
                    'entry_type' => PossederType::class,
                    'allow_add'=> true,
                    'allow_delete'=>true,
                    

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
