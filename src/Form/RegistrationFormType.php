<?php

namespace App\Form;
// autocompletation
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File as ConstraintsFile;

/**
 * Formulaire d'inscription d'utilisateur, ce formulaire est lie a l'entite User
 * Il comporte les champs : email, pseudo, photo , agree terms, et password
 */
class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        // champ de email
            ->add('email', EmailType::class ,[
                'attr'=>[
                    'placeholder'=>'Votre Adresse mail'
                ],
                'constraints'=>[
                    new NotBlank(['message'=>'Veuillez entrer un mail valide']),
                    new Email(["message"=>"Votre mail n'est pas valide"]),
                ]
            ])
            // champ pseudo
            ->add('pseudo', TextType::class,[
                'attr'=>[
                    'placeholder'=>'Votre Pseudo'
                ],
                'constraints'=>[
                    new NotBlank(['message'=>'Entrez un pseudo valide']),
                    new Length([
                        'min'=>3,
                        'minMessage' => 'Votre Pseudo doit contenir minumum {{ limit }} characteres',
                    ])
                ]
            ])
            // champ photo (pas obligatoire)
            ->add('photo', FileType::class, [
                'label' => 'photo',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new ConstraintsFile([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/*',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image!',
                    ])
                ],
            ])
            // champ accepter les terms
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    // contrainte d'obligation d'accepter
                    new IsTrue([
                        'message' => 'Vous devez accepter les termes.',
                    ]),
                ],
            ])
            // champ password
            ->add('password', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                // il n'est pas ajoute a la bdd directement car on va le hash d'abord.
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    // contrainte de ne pas etre vide
                    new NotBlank([
                        'message' => 'Entrez un mot de passe',
                    ]),
                    // contrainte de taille
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit contenir minumum {{ limit }} characteres',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ])
        ;
    }
// lie a l'entite User
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
