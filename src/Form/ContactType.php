<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email as ConstraintsEmail;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactType extends AbstractType
{
    // formulaire de contact admin
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('email',EmailType::class,[
                'label_attr'=>['class'=>'titres'],
                'attr'=>[
                    'placeholder'=>'Votre Adresse mail'
                ],
                'constraints'=>[
                    new NotBlank(['message'=>'Veuillez completer ce champ']),
                    new ConstraintsEmail(["message"=>"Votre mail n'est pas valide"]),
                ]
            ])
            ->add('subject',TextType::class,[
                'label_attr'=>['class'=>'titres'],
                'constraints'=>[
                    new NotBlank(['message'=>'Veuillez completer ce champ']),
                    new Length([
                        'min'=>2,
                        'minMessage' => 'Votre sujet doit contenir minumum {{ limit }} characteres',
                    ])
                ]
            ])
            ->add('message',TextareaType::class,[
                'label_attr'=>['class'=>'titres'],
                'constraints'=>[
                    new NotBlank(['message'=>'Veuillez completer ce champ']),
                    new Length([
                        'min'=>5,
                        'minMessage' => 'Votre sujet doit contenir minumum {{ limit }} characteres',
                    ])
                ]
                
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
    }
}
