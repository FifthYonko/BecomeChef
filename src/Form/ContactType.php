<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    // formulaire de contact admin
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
        // champ adresse email
            ->add('email',EmailType::class)
            // champ sujet du mail
            ->add('subject',TextType::class)
        // champ message a envoyer
            ->add('message',TextareaType::class)
        ;
    }

    // ce formulaire n'est relie a aucune table
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
