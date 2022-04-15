<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

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
            'invalid_message'=>'les mots de passes doivent etre identiques',
            'options'=>['attr'=>['class'=>'password-field']],
            'required'=>true,
            'first_options'=>['label'=>'Password','label_attr'=>['class'=>'titres fs-5']],
            'second_options'=>['label'=>'Repeat Password','label_attr'=>['class'=>'titres fs-5']],
        ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
    }
}
