<?php

namespace App\Form;
// autocompletation
use App\Entity\User;
use Rollerworks\Component\PasswordStrength\Validator\Constraints\PasswordStrength;
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
 * Formulaire d'inscription d'utilisateur, ce formulaire est lié à l'entité User
 * Il comporte les champs : email, pseudo, photo,agree terms, et password
 */
class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class ,[
                'attr'=>[
                    'placeholder'=>'Votre Adresse mail'
                ],
                'constraints'=>[
                    new NotBlank(['message'=>'Veuillez entrer un mail valide']),
                    new Email(["message"=>"Votre mail n'est pas valide"]),
                ]
            ])
            ->add('pseudo', TextType::class,[
                'attr'=>[
                    'placeholder'=>'Votre Pseudo',
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
                'label' => 'photo',
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
            ])

            ->add('password', PasswordType::class, [
            
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Entrez un mot de passe',
                    ]),
                    new PasswordStrength([
                        'minLength'=>8,
                        'minStrength'=>4,
                    ]),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter les termes.',
                    ]),
                ],
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
