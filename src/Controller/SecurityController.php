<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\SendEmail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Form\ForgotPassType;
use App\Form\ResetPasswordType;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     * Methode qui permet a l'utilisateur de se connecter
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            $this->addFlash("warning", 'Vous etes deja connecte');
            return $this->redirectToRoute('target_path');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     * Methode de deconnexion
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/forgot_password', name: 'forgot_password')]
    /**
     * Methode qui permet d'initialiser la procedure de changement de mdp
     * On utilise des composants symfony et le service SendEmail
     */
    public function forget_password(Request $request, TokenGeneratorInterface $tokenGenerator, UserRepository $userRepository, EntityManagerInterface $entityManager, SendEmail $sendEmail)
    {
        // on cree un formulaire pour que l'utilisateur fasse sa demande de changement de mot de passe

        $forget_pass = $this->createForm(ForgotPassType::class);
        $forget_pass->handleRequest($request);

        // si le formulaire est correctement rempli
        if ($forget_pass->isSubmitted() && $forget_pass->isValid()) {
            // on recupere l'email
            $email = $forget_pass->get('email')->getData();
            // on cherche dans la bdd , un utilisateur qui possede ce mail
            $user = $userRepository->findOneBy(['email' => $email]);
            // on verifie qu'il existe bien un utilisateur avec ce mail
            if (!$user) {
                // si il n'existe pas, on affiche un message et on redirige
                $this->addFlash('warning', 'Cette adresse n\'est lie a aucun compte.');
                return $this->redirectToRoute('app_login');
            }
            // on cree une chaine de caracteres unique qui nous permetera de confirmer l'identite de l'utilisateur
            $token = $tokenGenerator->generateToken();
            // on essaie de remplir le champ PasswordToken dans la bdd avec ce tokken
            try {
                $user->setPasswordTokken($token);
                $entityManager->persist($user);
                $entityManager->flush();
                // si il y a une erreur, on previent l'utilisateur et on redirige
            } catch (FileException $e) {
                $this->addFlash('danger', 'Une erreur est survenue : ' . $e->getMessage());
                return $this->redirectToRoute('app_login');
            }
            // on cree un lien sur laquelle l'utilisateur pourra cliquer pour acceder au formulaire de changement de mdp
            // dans ce lien on ajoute la route, et le token cree plutot
            $url = $this->generateUrl('app_reset_password', ['token_password' => $token], UrlGenerator::ABSOLUTE_URL);
            // on envoie le mail
            $sendEmail->send('BecomeChef@super-site.com', $email, 'Changement de Mot de passe', $url);
            // on affiche un message et on redirige vers la page de connexion
            $this->addFlash('success', 'Un email vous a ete envoye, cliquez sur le lien pour changer votre mdp');
            return $this->redirectToRoute('app_login');
        }
        // sinon on affiche le formulaire de demande de changement 
        return $this->renderForm('security/forgotPassword.html.twig', [
            'form_reset' => $forget_pass,
        ]);
    }


    #[Route('/reset_password/{token_password}', name: 'app_reset_password')]
    /**
     * Methode qui permet a l'utilisateur de changer le mdp
     * Cette methode est accessible uniquement grace au lien qu'on envoi dans le mail
     */
    public function reset_password($token_password, Request $request, UserPasswordHasherInterface $passwordEncoder, UserRepository $userRepository, EntityManagerInterface $entityManager)
    {
        // on affiche le formulaire de changement de mdp
        $reset_form = $this->createForm(ResetPasswordType::class);
        $reset_form->handleRequest($request);
        // on verifie qu'il existe un utilisateur avec le token recu en argument de fonction 
        $user = $userRepository->findOneBy(['passwordTokken' => $token_password]);
        
        if (!$user) {
            // s'il n'existe pas on affiche un message et on redirige vers la connexion
            $this->addFlash('danger', 'Aucun utilisateur trouve');
            return $this->redirectToRoute('app_login');
        }
        // on verifie si le formulaire est corectement rempli
        if ($reset_form->isSubmitted() && $reset_form->isValid()) {
            // on recupere le champs du mdp rempli par l'utilisateur
            $nvMdp = $reset_form->get('password')->getData();
            // on met a NULL le champ PasswordToken de l'utilisateur dans la bdd
            $user->setPasswordTokken(NULL);
            // on modifie le mdp de l'utilisateur grace au $hachage de mdp
            // pour hacher le mdp on utilise la methode hashPassword du composant UserPasswordHasherInterface
            // Ce qui permet de creer une chaine unique dans la base de donnes pour cacher le mdp
            $user->setPassword($passwordEncoder->hashPassword($user, $nvMdp));
            // on fait les modifications necessaires dans la bdd
            $entityManager->persist($user);
            $entityManager->flush();
            // on affiche un message et on redirige vers la connexion
            $this->addFlash('success', 'MDP change avec success');
            return $this->redirectToRoute('app_login');
        } else {
            // sinon on affiche le formulaire de changement de mdp
            return $this->renderForm('security/reset_password.html.twig', [
                'form_reset' => $reset_form,
            ]);
        }
    }

    #[Route('/accepte/cookie', name: 'accepte_cookie')]
    public function accept_cookie(Request $request){
        $response = new Response();
        $expires = time()+(365*24*60*60);
        $cookie = Cookie::create('accept_cookies',true,$expires);
        $response->headers->setCookie($cookie);
        $response->send();
        
        return $this->redirectToRoute('home');
    }
    #[Route('/delete/cookie', name: 'delete_cookie')]
    public function delete_cookie(Request $request){
        $response = new Response();
        
        $response->headers->clearCookie('accept_cookies');
        $response->send();
    
       return $this->redirectToRoute('home');
    }
    
}
