<?php

namespace App\Controller;

use App\Repository\CommentaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommentaireController extends AbstractController
{
    #[Route('/delete_commentaire/{id}/{idR}', name: 'delete_commentaire')]
    public function index(Request $request,int $id,int $idR,CommentaireRepository $commentaireRepository, EntityManagerInterface $entityManager)
    {
        $user =$this->getUser(); 
        if(!$user){
            $this->addFlash('danger','Connectez-vous pour acceder a cette fonctionnalite');
            return $this->redirectToRoute('app_login');
        }

        $comment_aSupp = $commentaireRepository->find($id);
        if(!$comment_aSupp){
            $this->addFlash('warning','Aucun commentaire trouve');
        }
        if($this->isGranted('ROLE_ADMIN') || $user === $comment_aSupp->getAuthor() ){
            $entityManager->remove($comment_aSupp) ;
            $entityManager->flush();

            $this->addFlash('success','Le commentaire a bien ete supprime');
            return $this->redirectToRoute('show_recette',['id'=>$idR]);
        }
        else{
            $this->addFlash('warning','Vous ne disposez pas de ces droits');
            return $this->redirectToRoute('show_recette',['id'=>$idR]);
        }
    }
}
