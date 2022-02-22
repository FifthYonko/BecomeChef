<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin_ban/{id}', name: 'admin_ban')]
    public function ban(Request $request, UserRepository $userRepository, int $id)
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            $user = $userRepository->find($id);
            if (!$user) {
                $this->addFlash('warning', 'L\'utilisateur n\'existe pas');
                return $this->redirect('home');
            } elseif (array_search('ROLE_SUPER_ADMIN', $user->getRoles())) {
                $this->addFlash('warning', 'Vous ne disposez pas des droits necessaires pour cette action');
                return $this->redirect('home');
            }
            $user->setEtat(1);
            return $this->redirectToRoute('home');
        }
    }
}
