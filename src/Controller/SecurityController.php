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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     * Méthode qui permet à l'utilisateur de se connecter
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            $this->addFlash("warning", 'Vous êtes déjà connecté');
            return $this->redirectToRoute('home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $this->addFlash('warning',$error->getMessage());

        $lastUsername = $authenticationUtils->getLastUsername();


        return $this->redirectToRoute('home', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
    * Méthode de déconnexion
    */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

     /**
     * Méthode qui permet d'initialiser la procédure de changement de Mdp
     * On utilise des composants Symfony et le service Send Email
     */
    #[Route('/forgot_password', name: 'forgot_password')]
    public function forget_password(Request $request, TokenGeneratorInterface $tokenGenerator, UserRepository $userRepository, EntityManagerInterface $entityManager, SendEmail $sendEmail)
    {

        $utilisateurConnecte = $this->getUser();
        if ($utilisateurConnecte) {
            $email = $utilisateurConnecte->getEmail();
            $user = $utilisateurConnecte;
        } elseif (!$utilisateurConnecte) {

            $forget_pass = $this->createForm(ForgotPassType::class);
            $forget_pass->handleRequest($request);

            if ($forget_pass->isSubmitted() && $forget_pass->isValid()) {
                $email = $forget_pass->get('email')->getData();

                $user = $userRepository->findOneBy(['email' => $email]);
                if (!$user) {
                    $this->addFlash('warning', 'Cette adresse n\'est liée à aucun compte.');
                    return $this->redirectToRoute('app_login');
                }
            } else {
                return $this->renderForm('security/forgotPassword.html.twig', [
                    'form_reset' => $forget_pass,
                ]);
            }
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

        $sendEmail->ResetPassword('BecomeChef@admin.com', $email, 'Changement de Mot de passe', $url, 'emails/passwordchange.html.twig', $urlCancel);
        $this->addFlash('success', 'Un email vous a été envoyé, cliquez sur le lien pour changer votre mot de passe');
        if (!$utilisateurConnecte) {
            return $this->redirectToRoute('app_login');
        } else {

            return $this->redirectToRoute('profile', ['page' => 1]);
        }
    }


    #[Route('/reset_password/{token_password}', name: 'app_reset_password')]
    /**
     * Méthode qui permet à l'utilisateur de changer le Mdp
     * Cette méthode est accessible uniquement grace au lien qu'on envoie dans le mail
     */
    public function reset_password($token_password, Request $request, UserPasswordHasherInterface $passwordEncoder, UserRepository $userRepository, EntityManagerInterface $entityManager)
    {
        $reset_form = $this->createForm(ResetPasswordType::class);
        $reset_form->handleRequest($request);
        $user = $userRepository->findOneBy(['passwordTokken' => $token_password]);

        if (!$user) {
            $this->addFlash('danger', 'Aucun utilisateur trouvé');
            return $this->redirectToRoute('home');
        }
        if ($reset_form->isSubmitted() && $reset_form->isValid()) {

            $nvMdp = $reset_form->get('password')->getData();
            $user->setPasswordTokken(NULL);

            $user->setPassword($passwordEncoder->hashPassword($user, $nvMdp));
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Le mot de passe a été change avec succès');
            return $this->redirectToRoute('home');
        } else {
            return $this->renderForm('security/reset_password.html.twig', [
                'form_reset' => $reset_form,
            ]);
        }
    }
    #[Route('/cancel_reset/{token_password}', name: 'app_cancel_reset')]
    /**
     * Méthode qui permet à l'utilisateur d'annuler la demande de changement de Mdp par 
     * la suppression du token dans la base de données. 
     * Cette méthode est accessible uniquement grace au lien qu'on envoie dans le mail
     */
    public function cancel_reset($token_password, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager)
    {
        $user = $userRepository->findOneBy(['passwordTokken' => $token_password]);

        if (!$user) {
            $this->addFlash('danger', 'Aucun utilisateur trouvé');
            return $this->redirectToRoute('home');
        }

        $user->setPasswordTokken(NULL);
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->redirectToRoute('home');
    }


    /**
     * Méthode qui redirige un utilisateur vers une page ou il peut lire la politique de confidentialité
     * . Ce lien se trouve sur la page d'inscription ou en footer
     */
    #[Route('/politique_de_conf', name: 'pdc')]
    public function pdc()
    {
        return $this->render('politiqueDC/pDC.html.twig');
    }

    /**
     * Méthode qui redirige un utilisateur vers une page ou il peut lire les conditions
     * générales . Ce lien se trouve sur la page d'inscription et au niveau du footer
     */
    #[Route('/register_cgu', name: 'cgu')]
    public function cgu()
    {
        return $this->render('conditionsUtilisation/conditionUtilisation.html.twig');
    }

    #[Route('/mentions_legales', name: 'ml')]
    public function ml()
    {
        return $this->render('politiqueDC//mentionsLegales.html.twig');
    }
}
