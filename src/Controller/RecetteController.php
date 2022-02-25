<?php

namespace App\Controller;

use App\Entity\Posseder;
use App\Entity\Recette;
use App\Form\CommentaireType;
use App\Form\RecetteFormType;
use App\Repository\CommentaireRepository;
use App\Repository\PossederRepository;
use App\Repository\RecetteRepository;
use App\Service\FileUploader;
use App\Service\RecetteHasIngredient;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Id;
use phpDocumentor\Reflection\DocBlock\Tags\Author;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class RecetteController extends AbstractController
{
    public function __construct(private RecetteRepository $recetteRepository, private PossederRepository $possederRepository, private EntityManagerInterface $entityManager, private SluggerInterface $slugger)
    {
    }

    #[Route('/recette/{page}', name: 'recette')]
    public function index(int $page): Response
    {
        $recette = $this->recetteRepository->findByPage($page-1,3);
        $nbtotal = $this->recetteRepository->compter();
        return $this->render('recette/index.html.twig', [
            'recettes' => $recette,
            'total' =>$nbtotal,
        ]);
    }

    #[Route('/new_recette', name: 'new_recette')]
    public function ajout_recette(Request $request, FileUploader $fileUploader): Response
    {
      
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('warning', 'Vous devez etre connecte pour acceder a cette page!');
            return $this->redirectToRoute('app_login');
        }
        
        if($this->getUser()->getEtat() === true ){
            $this->addFlash('danger','Vous etes banni et donc vous ne pouvez plus acceder a cette fonctionnalite');
            return $this->redirectToRoute('home');
        }

        $form_recette = $this->createForm(RecetteFormType::class);
        $form_recette->handleRequest($request);
        if ($form_recette->isSubmitted() && $form_recette->isValid()) {

            $new_recette = $form_recette->getData();
            $imgFile = $form_recette->get('photo')->getData();
            
            if ($imgFile) {
                $newFileName = $fileUploader->upload($imgFile);
                $new_recette->setPhoto($newFileName);
            }
           
            $new_recette->setAuthor($this->getUser());
            
            foreach ($form_recette->get('posseders')->getData() as $ingredient) {

                $ingredient->setRecette($new_recette);
            }
            $this->entityManager->persist($new_recette);
            $this->entityManager->flush();

            $this->addFlash('success', 'La recette a ete ajoute !');
            return $this->redirectToRoute('recette',['page'=>1]);
        }

        return $this->render('recette/new_recette.html.twig', [
            'form_recette' => $form_recette->createView(),
        ]);
    }

    #[Route('/show_recette/{id}', name: 'show_recette')]
    public function show_recette(Request $request, int $id)
    {
        $recette = $this->recetteRepository->findOneBy(['id' => $id]);
        $commentaires = $recette->getCommentaires();

        if ($this->getUser()!==null ) {
            $form_comm = $this->createForm(CommentaireType::class);
            $form_comm->handleRequest($request);
    
    
            if ($form_comm->isSubmitted() && $form_comm->isValid()) {
    
                $new_comm = $form_comm->getData();
                $new_comm->setAuthor($this->getUser());
                $new_comm->setRecette($recette);
    
                $this->entityManager->persist($new_comm);
                $this->entityManager->flush();
    
                $this->addFlash('success', 'Le commentaire a ete ajoute');
                return $this->redirectToRoute('show_recette', ['id' => $id]);
            }
            return $this->renderForm('recette/show_recette.html.twig', [
                'recette' => $recette,
                'comments' => $commentaires,
                'form_comm' => $form_comm,
            ]);
        }
      

        return $this->renderForm('recette/show_recette.html.twig', [
            'recette' => $recette,
            'comments' => $commentaires,
            
        ]);
    }

    #[Route('/update_recette/{id}', name: 'update_recette')]
    public function update_recette(Request $request, int $id, FileUploader $fileUploader )
    {
        // on recherche la recette dans la bdd grace a son id
        $recette_modifie = $this->recetteRepository->find($id);
        
        if (!$recette_modifie) {
            // si elle existe pas, on met un warning 
            $this->addFlash('warning', 'Aucune recette trouve');
        }
        $imageExistante = $recette_modifie->getPhoto();
        // si elle existe , on verifie que l'utilisateur est soit connecte et proprio de la recette, soit un admin
        if ($this->isGranted('ROLE_ADMIN') || $this->getUser()->getId() === $recette_modifie->getAuthor()->getId()) {
            // on cree le formulaire
            $update_recette = $this->createForm(RecetteFormType::class, $recette_modifie);
            $update_recette->handleRequest($request);

            if ($update_recette->isSubmitted() && $update_recette->isValid()) {
                // si le form a ete valide et soumis, on recupere les infos
                $recette_modifie = $update_recette->getData();
                foreach ($update_recette->get('posseders')->getData() as $ingredient) {

                    $ingredient->setRecette($recette_modifie);
                }
                // on modifie le nom de la photo et on stocke dans uploads
                $imgFile = $update_recette->get('photo')->getData();
                if ($imgFile) {
                    if($imageExistante){
                        unlink('uploads/'.$imageExistante);
                    }
                    $newFileName = $fileUploader->upload($imgFile);
                    $recette_modifie->setPhoto($newFileName);
                    // on modifie l'info dans la recette

                }
                // on envoie les donnees dans la base et on redirect vers le catalogue
                $this->entityManager->persist($recette_modifie);
                $this->entityManager->flush();

                // foreach ($ingredients as $ingredient) {
                //     $recetteHasIngredient->posseder($ingredient, $recette_modifie, 'comme tu veux');
                // }
                $this->addFlash('success', "La recette a bien ete modifie :)");
                return $this->redirectToRoute('recette',['page'=>1]);
            }
        } else {

            if ($this->isGranted('ROLE_USER')) {
                $this->addFlash("danger", "Vous devez etre Admin ou le proprietaire de la recette pour la modifier!");
                return $this->redirectToRoute('recette',['page'=>1]);
            } else {
                $this->addFlash("danger", "Vous devez etre soit connecte soit Admin ou le proprietaire de la recette pour la modifier!");
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('recette/update_recette.html.twig', [
            'update_recette' => $update_recette->createView(),
        ]);
    }

    #[Route('/delete_recette/{id}', name: 'delete_recette')]
    public function delete_recette(Request $request, int $id)
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('danger', 'Connectez-vous pour acceder a cette fonctionnalite');
            return $this->redirectToRoute('app_login');
        }
        $recette_aSupp = $this->recetteRepository->find($id);
        if (!$recette_aSupp) {
            $this->addFlash('warning', 'Aucune recette trouve');
        }
        if ($this->isGranted('ROLE_ADMIN') || $user === $recette_aSupp->getAuthor()) {
            $imgFile ='uploads/'.$recette_aSupp->getPhoto();
            if($imgFile){
                unlink($imgFile);
            }
            $this->entityManager->remove($recette_aSupp);
            $this->entityManager->flush();
            $this->addFlash('success', "La recette a bien ete supprime!");
            return $this->redirectToRoute('recette',['page'=>1]);
        } elseif ($user === $recette_aSupp->getAuthor() || !$this->isGranted("ROLE_ADMIN")) {
            $this->addFlash('warning', "Vous ne pouvez pas supprimer cette recette car vous n'etez ni admin, ni auteur");
            return $this->redirectToRoute('recette',['page'=>1]);
        }
    }

    #[Route('/search', name: 'search')]

    public function search(Request $request){
        
        $recettes = $this->recetteRepository->findByExampleField($request->query->get('search_value'));
    
        return $this->render('recette/index.html.twig', [
            'recettes' => $recettes,
        ]);
        
    }
    /* fonction qui supprime un ingredient de la liste d'ingredients d'une recette.
        accessible a partir de la page d'affichage d'une recette
        prend en parametre un entier qui represente l'id de l'enregistrement a supprimer
        Cette fonction redirige vers la vue d'une recette
    */
    #[Route('/delete_ingredient/{id}', name: 'delete_ingredient')]

    public function delete_ingredient(int $id){
        // on verifie si l'utilisateur est connecte
        if(!$this->IsGranted('ROLE_USER')){
            $this->addFlash('danger','vous devez vous connecter pour acceder a cette fonctionnalite');
            return $this->redirectToRoute('app_login');
        }
        // on cherche la relation dans la base grace a l'id
        $relationaSupp = $this->possederRepository->find($id);
        // on cherche le proprietaire de la recette pour faire des verifications plus tard
        $proprietaire = $relationaSupp->getRecette()->getAuthor()->getId();
    
        // on verifie que l'utilisateur est bien le proprio de la recette pour pouvoir la modifier 
        if(!$this->getUser()->getId() != $proprietaire ){
            // si il ne l'est pas, on redirige avec un message
            $this->addFlash('warning','vous devez etre admin ou proprietaire de la recette pour effectuer cette tache');
            return $this->redirectToRoute('show_recette',['id'=>$relationaSupp->getRecette()->getId()]);
        }
        // si oui, on retire la ligne de la bdd
        $this->entityManager->remove($relationaSupp);
        $this->entityManager->flush();

        // on redirige vers la page ou l'utilisateur etait avant
        $this->addFlash('success','Ingredient retire');
        return $this->redirectToRoute('show_recette',['id'=>$relationaSupp->getRecette()->getId()]);
       
        
    }
}
