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
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/forgot_password', name: 'forgot_password')]
    public function forget_password(Request $request, TokenGeneratorInterface $tokenGenerator, UserRepository $userRepository ,EntityManagerInterface $entityManager, SendEmail $sendEmail)
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
            $sendEmail->send('BecomeChef@super-site.com', $email , 'Changement de Mot de passe', $url);
            $this->addFlash('success', 'Un email vous a ete envoye, cliquez sur le lien pour changer votre mdp');
            return $this->redirectToRoute('app_login');
        }
        return $this->renderForm('security/forgotPassword.html.twig',[
            'form_reset' => $forget_pass,
        ]);
    }


    #[Route('/reset_password/{token_password}', name: 'app_reset_password')]
    public function reset_password($token_password,Request $request, UserPasswordHasherInterface $passwordEncoder, UserRepository $userRepository,EntityManagerInterface $entityManager){
        
        $reset_form = $this->createForm(ResetPasswordType::class );
        $reset_form->handleRequest($request);

        $user = $userRepository->findOneBy(['passwordTokken'=>$token_password]);
        if(!$user){
            $this->addFlash('danger','Aucun utilisateur trouve');
            return $this->redirectToRoute('app_login');
        }
        if($reset_form->isSubmitted() && $reset_form->isValid()){
            $nvMdp = $reset_form->get('password')->getData();
            $user->setPasswordTokken(NULL);
            $user->setPassword($passwordEncoder->hashPassword($user,$nvMdp));
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success','MDP change avec success');
            return $this->redirectToRoute('app_login');
        }else{
            return $this->renderForm('security/reset_password.html.twig',[
                'form_reset'=>$reset_form,
            ]);
        }
    }
}
