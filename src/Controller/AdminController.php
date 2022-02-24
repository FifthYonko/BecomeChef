<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    public function __construct(private UserRepository $userRepository, private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/ban/{id}', name: 'ban')]
    public function ban(Request $request, int $id)
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'Vous ne disposez pas des access necessaires');
            return $this->redirectToRoute('home');
        }

        $user = $this->userRepository->find($id);

        if (!$user) {
            $this->addFlash('warning', 'L\'utilisateur n\'existe pas');
            return $this->redirectToRoute('home');
        } elseif ($user->getId() == $this->getUser()->getId()) {
            $this->addFlash('warning', 'Vous ne pouvez pas vous bannir vous-meme');
            return $this->redirectToRoute('home');
        } elseif (in_array('ROLE_SUPER_ADMIN', $user->getRoles()) || in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('warning', 'Vous ne disposez pas des droits necessaires pour cette action');
            return $this->redirectToRoute('home');
        }

        $user->setEtat('1');
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $this->redirectToRoute('home');
    }

    #[Route('/unban/{id}', name: 'unban')]
    public function unban(Request $request,  int $id)
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'Vous ne disposez pas des access necessaires');
            return $this->redirectToRoute('home');
        }

        $user = $this->userRepository->find($id);

        if (!$user) {
            $this->addFlash('warning', 'L\'utilisateur n\'existe pas');
            return $this->redirectToRoute('home');
        } elseif (array_search('ROLE_SUPER_ADMIN', $user->getRoles())) {
            $this->addFlash('warning', 'Vous ne disposez pas des droits necessaires pour cette action');
            return $this->redirectToRoute('home');
        }

        $user->setEtat(0);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $this->redirectToRoute('home');
    }

    #[Route('/inspect_profil/{id}', name: 'inspect_profil')]
    public function inspect_profil(Request $request, int $id)
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'Vous ne disposez pas des access necessaires');
            return $this->redirectToRoute('home');
        }
        if ($this->getUser()->getId() === $id) {
            $this->addFlash('success', 'Voici votre profil');
            return $this->redirectToRoute('profile');
        }

        $user = $this->userRepository->find($id);
        return $this->render('admin/inspect_profile.html.twig', [
            'user' => $user,

        ]);
    }
}
