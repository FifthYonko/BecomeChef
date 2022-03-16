<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ForgotPassType extends AbstractType
{
    // formulaire d'envoi de lien de changement de mdp
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // champ email auquel envoyer le mail
        $builder
            ->add('email',EmailType::class)
        ;
    }

    // pas de lien avec une table
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
