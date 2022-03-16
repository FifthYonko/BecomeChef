<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    /**
     * Methode qui permet a un utilisateur de s'inscrire 
     * Prend en parametre 3 composants symfony
     * 
     */
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        // initialise une variable en tant que objet User
        $user = new User();
        // creation de formulaire
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        // verification de formulaire
        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
            $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                )
            );
            // modification bdd
            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email
            // redirection vers la page d'accueil
            return $this->redirectToRoute('home');
        }
        // sinon redirection vers le formulaire d'inscription
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
    /**
     * Methode qui redirige un utilisateur vers une page ou il peut lire les conditions
     * generales . Ce lien se trouve sur la page d'inscription
     */
    #[Route('/register_cgu', name: 'cgu')]
    public function cgu(){
        return $this->render('conditionsUtilisation/conditionUtilisation.html.twig');
    }
}
