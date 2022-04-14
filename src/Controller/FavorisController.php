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
        // on recupere la session
        $session = $this->requestStack->getSession();
        // on cree un tableau pour pouvoir stocker plus tard les elements 
        $favoris = [];
        // on recupere les informations de la session du champs 'favoris' (si il n'existe pas on recupere un tableau vide) et on le nomme $id 
        foreach ($session->get('favoris', []) as $id) {
            // on stocke les informations dans le tableau favoris avec la cle 'Favori' et les valeurs seront les recettes trouves grace a la variable $id trouve plus haut
            $favoris[] = [
                'favori' => $this->recetteRepository->find($id),
            ];
        }
        // on redirige vers une page d'affichage
        return $this->render('favoris/index.html.twig', [
            'favoris' => $favoris,
        ]);
    }
    // methode d'ajout des recettes au favoris
    #[Route('/add/{id}', name: 'add_favori')]
    public function add(int $id): Response
    {
        // on recupere la session
        $session = $this->requestStack->getSession();
        // on recupere le tableau de favoris, si ca n'existe pas, on recupere un tableau vide.
        $favoris = $session->get('favoris', []);
        // si la recette existe deja dans les favoris , on affiche un message et on redirige vers la liste des favoris
        if (array_key_exists($id, $favoris)) {
            $this->addFlash('warning', 'cette recette est deja aux favoris');
            
            return $this->redirectToRoute('list');
            // si elle n'existe pas, on l'ajoute  au favoris 
        } else {
            $favoris[$id] = $id;
        }
        // on defini le champs favoris dans la session et on affecte le tableau $favoris
        $session->set('favoris', $favoris);
        // on affiche un message de succes
        $this->addFlash("success", "La recette  a bien été ajouté");
        // on redirige vers la liste des favoris
        return $this->redirectToRoute('list');
    }

    // methode qui permet de retirer une recette des favoris , elle prend en parametre un id
    #[Route('/remove/{id}', name: 'remove_favoris')]
    public function remove(int $id): Response
    {
        // on recupere la session et on l'affecte a la variable $session
        $session = $this->requestStack->getSession();
        // on recupere le contenu des favoris (s'il est vide on recupere un tableau vide)
        $favoris = $session->get('favoris', []);
        // on recherche dans les favoris  la recette dont l'id est ce qu'on a recupere en argument de la methode
        if (array_key_exists($id, $favoris)) {
            // si elle existe on l'efface
                unset($favoris[$id]);
            }
        
            // on re-affecte la liste modifie des favoris a la session 
        $session->set('favoris', $favoris);
            // on affiche un message, et on redirige vers la liste des favoris
        $this->addFlash("success", "La recette a bien ete retire ");

        return $this->redirectToRoute('list');
    }

  
}
