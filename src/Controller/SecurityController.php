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
use App\Repository\RecetteRepository;
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

        $error = $authenticationUtils->getLastAuthenticationError();
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

        $forget_pass = $this->createForm(ForgotPassType::class);
        $forget_pass->handleRequest($request);

        if ($forget_pass->isSubmitted() && $forget_pass->isValid()) {
            $email = $forget_pass->get('email')->getData();
           
            $user = $userRepository->findOneBy(['email' => $email]);
            if (!$user) {
                $this->addFlash('warning', 'Cette adresse n\'est lie a aucun compte.');
                return $this->redirectToRoute('app_login');
            }
            

            $token = $tokenGenerator->generateToken();
            try {
                $user->setPasswordTokken($token);
                $entityManager->persist($user);
                $entityManager->flush();

            } catch (FileException $e) {
                $this->addFlash('danger', 'Une erreur est survenue : ' . $e->getMessage());
                return $this->redirectToRoute('app_login');
            }
            $url = $this->generateUrl('app_reset_password', ['token_password' => $token], UrlGenerator::ABSOLUTE_URL);
           
            $urlCancel = $this->generateUrl('app_cancel_reset', ['token_password' => $token], UrlGenerator::ABSOLUTE_URL);
           
            $sendEmail->ResetPassword('BecomeChef@admin.com', $email, 'Changement de Mot de passe', $url,'emails/passwordchange.html.twig',$urlCancel);
            $this->addFlash('success', 'Un email vous à été envoyé, cliquez sur le lien pour changer votre mot de passe');
            if(!$user){
               
                return $this->redirectToRoute('app_login');

            }
            else {
                
                return $this->redirectToRoute('profile');

            }
        }
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
        $reset_form = $this->createForm(ResetPasswordType::class);
        $reset_form->handleRequest($request);
        $user = $userRepository->findOneBy(['passwordTokken' => $token_password]);
        
        if (!$user) {
            $this->addFlash('danger', 'Aucun utilisateur trouve');
            return $this->redirectToRoute('home');
        }
        if ($reset_form->isSubmitted() && $reset_form->isValid()) {
      
            $nvMdp = $reset_form->get('password')->getData();
            $user->setPasswordTokken(NULL);
         
            $user->setPassword($passwordEncoder->hashPassword($user, $nvMdp));
            $entityManager->persist($user);
            $entityManager->flush();
          
                $this->addFlash('success', 'Le mot de passe a ete change avec success');
                return $this->redirectToRoute('home');

            
        } else {
            return $this->renderForm('security/reset_password.html.twig', [
                'form_reset' => $reset_form,
            ]);
        }
    }
    #[Route('/cancel_reset/{token_password}', name: 'app_cancel_reset')]
    /**
     * Methode qui permet a l'utilisateur d'annuler la demande de changement de mdp par 
     * la suppression du token dans la base de donnees. 
     * Cette methode est accessible uniquement grace au lien qu'on envoi dans le mail
     */
    public function cancel_reset($token_password, Request $request,UserRepository $userRepository, EntityManagerInterface $entityManager)
    {
        $user = $userRepository->findOneBy(['passwordTokken' => $token_password]);
        
        if (!$user) {
            $this->addFlash('danger', 'Aucun utilisateur trouve');
            return $this->redirectToRoute('home');
        }
       
            $user->setPasswordTokken(NULL);
            $entityManager->persist($user);
            $entityManager->flush();
          
            return $this->redirectToRoute('home');
    }

    #[Route('/accepte/cookie', name: 'accepte_cookie')]
    public function accept_cookie(Request $request ,RecetteRepository $recetteRepository ){
        $response = new Response();
        $expires = time()+(365*24*60*60);
        $cookie = Cookie::create('accept_cookies','oui',$expires);
        $response->headers->setCookie($cookie);
        $lasts = $recetteRepository->findLast();
       
        $content = $this->renderView('home/index.html.twig', [
            'last' =>$lasts,
        ]);
        $response->setContent($content);
        return $response;
    }
    #[Route('/refuse/cookie', name: 'refuse_cookie')]
    public function refuse_cookie(Request $request ,RecetteRepository $recetteRepository ){
        $response = new Response();
        $expires = time()+(365*24*60*60);
        $cookie = Cookie::create('accept_cookies','non',$expires);
        $response->headers->setCookie($cookie);
        $lasts = $recetteRepository->findLast();
        $content = $this->renderView('home/index.html.twig', [
            'last' =>$lasts,
        ]);
        $response->setContent($content);
        return $response;
    }

    #[Route('/politique_de_conf', name: 'pdc')]
    public function pdc(){
        return $this->render('politiqueDC/pDC.html.twig');
    }
    
}
