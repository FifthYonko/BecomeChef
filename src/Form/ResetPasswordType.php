<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Rollerworks\Component\PasswordStrength\Validator\Constraints\PasswordStrength;

/**
 * Formulaire de changement de mdp
 * Lie a aucune entite
 * Comporte les champs : password
 */
class ResetPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('password',RepeatedType::class,[
            'type'=>PasswordType::class,
            'invalid_message'=>'Les mots de passe doivent être identiques',
            'options'=>['attr'=>['class'=>'password-field']],
            'required'=>true,
            'first_options'=>['label'=>'Password','label_attr'=>['class'=>'titres fs-5']],
            'second_options'=>['label'=>'Repeat Password','label_attr'=>['class'=>'titres fs-5']],
            'constraints' => [
                new NotBlank([
                    'message' => 'Entrez un mot de passe',
                ]),
                new PasswordStrength([
                    'minLength'=>8,
                    'minStrength'=>4,
                ]),
            ],
        ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
    }
}
