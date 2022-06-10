<?php

namespace App\Controller;

use App\Repository\RecetteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/favoris')]
class FavorisController extends AbstractController
{

    public function __construct(private RequestStack $requestStack, private RecetteRepository $recetteRepository)
    {
    }
    // Méthode qui affiche la liste des recettes ajoutées aux favoris
    #[Route('/list', name: 'list')]
    public function index()
    {
        $userPseudo = $this->getUser()->getPseudo();
        if(!$this->getUser()){
            $this->addFlash('warning', 'Vous devez être connecter pour accéder à cette fonctionnalité');
            
            return $this->redirectToRoute('app_login');
        }
        $session = $this->requestStack->getSession();
        $favoris = [];
        foreach ($session->get('favoris'.$userPseudo , []) as $id) {
            $favoris[] = [
                'favori'.$userPseudo  => $this->recetteRepository->find($id),
            ];
        }
        return $this->render('favoris/index.html.twig', [
            'favoris' => $favoris,
        ]);
    }

// Méthode d'ajout des recettes aux favoris
    #[Route('/add/{id}', name: 'add_favori')]
    public function add(int $id): Response
    {
        $userPseudo = $this->getUser()->getPseudo();
        $session = $this->requestStack->getSession();
        $favoris = $session->get('favoris'.$userPseudo , []);
        if (array_key_exists($id, $favoris)) {
            $this->addFlash('warning', 'Cette recette est déjà aux favoris');
            
            return $this->redirectToRoute('list');
        } else {
            $favoris[$id] = $id;
        }
        $session->set('favoris'.$userPseudo , $favoris);
        $this->addFlash("success", "La recette  a bien été ajouté");
        return $this->redirectToRoute('list');
    }

// Méthode qui permet de retirer une recette des favoris,elle prend en paramètre un id
    #[Route('/remove/{id}', name: 'remove_favoris')]
    public function remove(int $id): Response
    {
        $userPseudo = $this->getUser()->getPseudo();
        $session = $this->requestStack->getSession();
        $favoris = $session->get('favoris'.$userPseudo, []);
        if (array_key_exists($id, $favoris)) {
                unset($favoris[$id]);
            }
        
        $session->set('favoris'.$userPseudo, $favoris);
        $this->addFlash("success", "La recette a bien été retiré ");

        return $this->redirectToRoute('list');
    }

  
}
