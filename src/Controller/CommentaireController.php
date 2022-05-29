<?php

namespace App\Controller;

use App\Repository\CommentaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class CommentaireController extends AbstractController
{
    /*
        Methode qui permet de supprimer un commentaire. 
        Cette methode prend en parametre 2 entiers, un id qui represente l'id du commentaire, et l'idR qui represente l'id de la recette du commentaire
        On a aussi les objets $commentaireRepository de type CommentaireRepository et $entityManager
        En fin de methode, on redirige vers une vue
    
    */
    #[Route('/delete_commentaire/{id}/{idR}', name: 'delete_commentaire')]
    public function index(int $id,int $idR,CommentaireRepository $commentaireRepository, EntityManagerInterface $entityManager)
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
        if($user === $comment_aSupp->getAuthor() ){
            $entityManager->remove($comment_aSupp) ;
            $entityManager->flush();
            $this->addFlash('success','Le commentaire a bien ete supprime');
            return $this->redirectToRoute('show_recette',['id'=>$idR,'page' => 1]);
        }
        else{
            $this->addFlash('warning','Vous ne disposez pas de ces droits');
            return $this->redirectToRoute('show_recette',['id'=>$idR,'page' => 1]);
        }
    }
}
