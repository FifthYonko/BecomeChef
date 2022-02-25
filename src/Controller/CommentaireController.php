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
        // on recupere les informations de l'utilisateur qui essaie d'acceder a cette fonctionnalite
        $user =$this->getUser(); 
        // on verifie s'il existe bien
        if(!$user){
            // si il n'existe pas, on redirige avec un message
            $this->addFlash('danger','Connectez-vous pour acceder a cette fonctionnalite');
            return $this->redirectToRoute('app_login');
        }
        // on cherche le commentaire dans la bdd grace a l'id
        $comment_aSupp = $commentaireRepository->find($id);
        if(!$comment_aSupp){
            // si il n'existe pas , on affiche un message d'erreur
            $this->addFlash('warning','Aucun commentaire trouve');
        }
        // on verifie si l'utilisateur connecte est bien l'auteur du commentaire
        if($user === $comment_aSupp->getAuthor() ){
            // si oui, on fais les modifications necessaires dans la bdd
            $entityManager->remove($comment_aSupp) ;
            $entityManager->flush();
            // et on affiche un message avec une redirection vers la page sur laquelle on etait
            $this->addFlash('success','Le commentaire a bien ete supprime');
            return $this->redirectToRoute('show_recette',['id'=>$idR]);
        }
        // sinon on affiche un message d'erreur et on redirige
        else{
            $this->addFlash('warning','Vous ne disposez pas de ces droits');
            return $this->redirectToRoute('show_recette',['id'=>$idR]);
        }
    }
}
