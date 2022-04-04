<?php

namespace App\Form;

use App\Entity\Ingredient;
use App\Entity\Recette;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Formulaire d'ajout de recette
 */
class RecetteFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        // le champ du formulaire consacree au titre de la recette
            ->add('titre', TextType::class, [
                // les attributs du champ
                'attr' => [
                    'placeholder' => 'Le titre de la recette',
                ],
                // les contraintes
                'constraints' => [
                    // pas vide
                    new NotBlank([
                        'message' => 'Veuillez completer ce champ',
                    ]),
                    // une taille minimale
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Le titre doit contenir minumum {{limit}} caracteres',
                    ])
                ]
            ])
            // champ corespondant a l'affichage simple sur la page catalogue
            ->add('intro', TextType::class, [
                // l'attribut
                'attr' => [
                    'placeholder' => 'Nombre de personnes, temps de preparation',
                ],
                // les contraintes
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez completer ce champ',
                    ]),
                    new Length([
                        'min' => 25,
                        'minMessage' => 'Ce champ doit contenir minimum {{ limit }} caracteres',
                    ])
                ]
            ])
            // champ correspondant a la preparation de la recette
            ->add('preparation', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'Veuillez bien detailler le processus de preparation de la recette',
                ],
                // contraintes
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez completer ce champ',]),
                    // on met une taille minimale de 200 characteres
                    new Length([
                        'min' => 200,
                        'minMessage' => 'Ce champ necessite minimum {{ limit }} caracteres'
                    ])
                ]
            ])
            // champ correspondant a la photo de la recette
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
        //   champ corespondant a l'ensemble des ingredients possedes par la recette
        // type collectionType
            ->add('posseders', CollectionType::class, [
                // l'etiquette du champ
                    'label' => 'Ingredients',
                    // les valeurs qu'on peut choisir
                    'entry_type' => PossederType::class,
                    // on autorise les ajouts et les suppressions
                    'allow_add'=> true,
                    'allow_delete'=>true,
                    

                ])
            ;
               
             
           
                
    }
// formulaire lie a l'entite Recette
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recette::class,

        ]);
    }
}
