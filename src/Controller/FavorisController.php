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
    // methode qui affiche la liste des recettes ajoutes au favoris
    #[Route('/list', name: 'list')]
    public function index()
    {
        $session = $this->requestStack->getSession();
        $favoris = [];
        foreach ($session->get('favoris', []) as $id) {
            $favoris[] = [
                'favori' => $this->recetteRepository->find($id),
            ];
        }
        return $this->render('favoris/index.html.twig', [
            'favoris' => $favoris,
        ]);
    }

    // methode d'ajout des recettes au favoris
    #[Route('/add/{id}', name: 'add_favori')]
    public function add(int $id): Response
    {
        $session = $this->requestStack->getSession();
        $favoris = $session->get('favoris', []);
        if (array_key_exists($id, $favoris)) {
            $this->addFlash('warning', 'cette recette est deja aux favoris');
            
            return $this->redirectToRoute('list');
        } else {
            $favoris[$id] = $id;
        }
        $session->set('favoris', $favoris);
        $this->addFlash("success", "La recette  a bien été ajouté");
        return $this->redirectToRoute('list');
    }

    // methode qui permet de retirer une recette des favoris , elle prend en parametre un id
    #[Route('/remove/{id}', name: 'remove_favoris')]
    public function remove(int $id): Response
    {
        $session = $this->requestStack->getSession();
        $favoris = $session->get('favoris', []);
        if (array_key_exists($id, $favoris)) {
                unset($favoris[$id]);
            }
        
        $session->set('favoris', $favoris);
        $this->addFlash("success", "La recette a bien ete retire ");

        return $this->redirectToRoute('list');
    }

  
}
