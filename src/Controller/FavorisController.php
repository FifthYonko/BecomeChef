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

    #[Route('/add/{id}', name: 'add_favori')]
    public function add(int $id): Response
    {
        // on recupere la session
        $session = $this->requestStack->getSession();
        // on recupere le tableau de favoris, si ca n'existe pas, on recupere un tableau vide.
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
