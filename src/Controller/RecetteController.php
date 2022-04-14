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
use DateTime;
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

    /*
        Methode d'affichage de recettes. Elle prend en parametre un entier $page .
        Elle redirige vers la page d'affichage des recettes disponibles sur le site
    */
    #[Route('/recette/{page}', name: 'recette')]
    public function index(int $page)
    {
        // on recupere les recettes qui se trouvent dans notre base de donnees grace a une la fonction findByPage (cf RecetteRepository.php dans Repository)
        // et on stocke les informations dans la variable recette 
        $recette = $this->recetteRepository->findByPage($page - 1, 3);
        // grace a la fonction (Aussi definie dans RecetteRepository) on compte le nombre total de recettes presentes sur notre site
        $nbtotal = $this->recetteRepository->compter();
        // On redirige vers la page d'affichage des recettes
        return $this->render('recette/index.html.twig', [
            'recettes' => $recette,
            'total' => $nbtotal,
        ]);
    }

    #[Route('/new_recette', name: 'new_recette')]
    /**
     * Methode qui permet d'ajouter des recettes. Elle prend en parametre, le composant Request et le service FileUploader
     */
    public function ajout_recette(Request $request, FileUploader $fileUploader): Response
    {
        //   on verifie qu'un utilisateur est connecte
        if (!$this->isGranted('ROLE_USER')) {
            // si il ne l'est pas, on affiche un message d'erreur et on redirige vers la page de connexion
            $this->addFlash('warning', 'Vous devez etre connecte pour acceder a cette page!');
            return $this->redirectToRoute('app_login');
        }
        // on verifie si l'utilisateur est banni
        if ($this->getUser()->getEtat() === true) {
            // si il l'est on affiche un message et on redirige vers la page d'accueil
            $this->addFlash('danger', 'Vous etes banni et donc vous ne pouvez plus acceder a cette fonctionnalite');
            return $this->redirectToRoute('home');
        }
        // On cree un formulaire d'ajout de recettes grace a la classe RecetteFormType et on le stocke dans une variable
        $form_recette = $this->createForm(RecetteFormType::class);
        $form_recette->handleRequest($request);
        // on verifie si le formulaire a bien ete rempli
        if ($form_recette->isSubmitted() && $form_recette->isValid()) {

            // on recupere les informations
            $new_recette = $form_recette->getData();
            $new_recette->setTitre(ucfirst($form_recette->get('titre')->getData()));
            $new_recette->setIntro(ucfirst($form_recette->get('intro')->getData()));
            $new_recette->setPreparation(ucfirst($form_recette->get('preparation')->getData()));
            // on recupere les infos du champs photo du formulaire
            $imgFile = $form_recette->get('photo')->getData();
            // on verifie qu'elle existe
            if ($imgFile) {
                // on modifie de facon safe les infos de la photo
                $newFileName = $fileUploader->upload($imgFile);
                // on remplace le contenu du champs photo
                $new_recette->setPhoto($newFileName);
            }
            else{
                $new_recette->setPhoto('DefaultPhotoDark.png');
            }
            // si l'utilisateur n'as pas ajoute de recette on met par default le logo du site
          
            // on defini l'auteur avec les informations de l'auteur authentifie
            $new_recette->setAuthor($this->getUser());
            $date = new DateTime();
            $new_recette->setDate($date);
            // on fait une boucle car le champs ingredients du formulaire est de type array
            foreach ($form_recette->get('posseders')->getData() as $ingredient) {
                // on ajoute la relation entre la recette et l'ingredient dans l'entite Posseder
                $ingredient->setRecette($new_recette);
            }
            // pour finir on prepare et execute la commande pour la modification de notre base de donnees
            $this->entityManager->persist($new_recette);
            $this->entityManager->flush();
            // on affiche un message de success, et on redirige vers le catalogue des recettes

            $this->addFlash('success', 'La recette a ete ajoute !');
            return $this->redirectToRoute('recette', ['page' => 1]);
        }
        // si le formulaire n'est pas complete, on redirige vers la vue du formulaire
        return $this->render('recette/new_recette.html.twig', [
            'form_recette' => $form_recette->createView(),
        ]);
    }
    /*
        Route utilisateur pour l'affichage d'une recette , elle prend en parametre le composant symfony Request et un entier, cet entier est l'id de la recette a afficher
        Cette methode renvoie sur une page d'affichage d'une seule recette    
    */
    #[Route('/show_recette/{id}', name: 'show_recette')]
    public function show_recette(Request $request, int $id)
    {
        // on utilise la methode findOneBy() definie dans le RecetteRepository.php qui nous permet d'interoger notre 
        // base de donnees pour retrouver un enregistrement en fonction de la colonne sur laquelle on recherche
        $recette = $this->recetteRepository->findOneBy(['id' => $id]);
        // on recupere aussi les commentaires en rapport avec la recette grace a la methode getCommentaire() definie dans l'entite Recette 
        $commentaires = $recette->getCommentaires();

        // on verifie que l'utilisateur est bien connecte pour pouvoir poster de commentaires
        if ($this->IsGranted('ROLE_USER') && $this->getUser()->getEtat()!= 1) {
            // on cree un formulaire grace a la classe CommentaireType 
            $form_comm = $this->createForm(CommentaireType::class);
            $form_comm->handleRequest($request);

            // on verifie si le formulaire du commentaire a ete ajoute et que les champs sont valides
            if ($form_comm->isSubmitted() && $form_comm->isValid()) {

                // on recupere les informations que l'utilisateur a insere dans les champs
                $new_comm = $form_comm->getData();
                // on defini le champ auteur comme etant l'utilisateur connecte
                $new_comm->setAuthor($this->getUser());
                // on complete le champ recette avec les informations des recettes contenues dans la variable $rcette definie au tout debut de la methode
                $new_comm->setRecette($recette);

                // on fait la modification dans la base de donnees 
                $this->entityManager->persist($new_comm);
                $this->entityManager->flush();
                // on affiche le message et on redirige sur la meme page que sur celle ou on etait
                $this->addFlash('success', 'Le commentaire a ete ajoute');
                return $this->redirectToRoute('show_recette', ['id' => $id]);
            }

            // sinon on affiche la page de la recette avec les informations contenues dans recette,commentaires,et le formulaire d'ajout de commentaire
            return $this->renderForm('recette/show_recette.html.twig', [
                'recette' => $recette,
                'comments' => $commentaires,
                'form_comm' => $form_comm,
            ]);
        }

        // si l'utilisateur n'est pas connecte, on n'affiche pas le formulaire d'ajout de commentaires

        return $this->renderForm('recette/show_recette.html.twig', [
            'recette' => $recette,
            'comments' => $commentaires,

        ]);
    }
    /*
        methode utilisateur pour modifier les recettes existantes.
        Cette methode prend en parametres le composant symfony Request, un entier id qui represente l'id de la recette a modifier, et le service FileUploader
        Elle renvoie une redirection vers le catalogue des recettes si tout se passe bien
    */
    #[Route('/update_recette/{id}', name: 'update_recette')]
    public function update_recette(Request $request, int $id, FileUploader $fileUploader)
    {
        $user = $this->getUser();
        if(!$user){
            $this->addFlash('warning','Vous devez être connecté pour accéder à cette fonctionnalité');
            return $this->redirectToRoute('app_login');
        }
        // on recherche la recette dans la bdd grace a son id
        $recette_modifie = $this->recetteRepository->find($id);

        if (!$recette_modifie) {
            // si elle existe pas, on met un warning 
            $this->addFlash('warning', 'Aucune recette trouve');
            return $this->redirectToRoute('recette', ['page' => 1]);
        }
        $imageExistante = $recette_modifie->getPhoto();
        // si elle existe , on verifie que l'utilisateur est soit connecte et proprio de la recette, soit un admin
        if ($user->getId() === $recette_modifie->getAuthor()->getId()) {
            // on cree le formulaire
            $update_recette = $this->createForm(RecetteFormType::class, $recette_modifie);
            $update_recette->handleRequest($request);

            if ($update_recette->isSubmitted() && $update_recette->isValid()) {
                // si le form a ete valide et soumis, on recupere les infos
                $recette_modifie = $update_recette->getData();
                $recette_modifie->setTitre(ucfirst($update_recette->get('titre')->getData()));
                $recette_modifie->setIntro(ucfirst($update_recette->get('intro')->getData()));
                $recette_modifie->setPreparation(ucfirst($update_recette->get('preparation')->getData()));
                foreach ($update_recette->get('posseders')->getData() as $ingredient) {
                    $ingredient->setRecette($recette_modifie);
                }
                // on modifie le nom de la photo et on stocke dans uploads
                $imgFile = $update_recette->get('photo')->getData();
                
                if ($imgFile) {
                    if ($imageExistante && $imageExistante != "DefaultPhotoDark.png") {
                        unlink('uploads/' . $imageExistante);
                    }
                    // on modifie l'info dans la recette
                    $newFileName = $fileUploader->upload($imgFile);
                    $recette_modifie->setPhoto($newFileName);
                }
                // on envoie les donnees dans la base et on redirect vers le catalogue
                $this->entityManager->persist($recette_modifie);
                $this->entityManager->flush();

                $this->addFlash('success', "La recette a bien ete modifie :)");
                return $this->redirectToRoute('recette', ['page' => 1]);
            }
            // si l'utilisateur n'est pas admin ou le proprietaire de la recette
        } else {
            // on verifie si l'utilisateur est connecte 
            if ($this->isGranted('ROLE_USER')) {
                // si oui, on lui affiche un meessage et on redirige vers la page catalogue recettes de l'utilisateur
                $this->addFlash("danger", "Vous devez etre Admin ou le proprietaire de la recette pour la modifier!");
                return $this->redirectToRoute('recette', ['page' => 1]);
            } 
        }
        // sinon on redirige vers la page d'ajout de recette
        return $this->render('recette/update_recette.html.twig', [
            'update_recette' => $update_recette->createView(),
        ]);
    }
    /* Methode admin pour supprimer une recette 
        Elle prend en parametre un entier id qui represente l'id de la recette a supprimer
    */
    #[Route('/delete_recette/{id}', name: 'delete_recette')]
    public function delete_recette(Request $request, int $id)
    {
        // on recupere les informations utilisateur 

        $user = $this->getUser();
        // on verifie qu'il y a bien un utilisateur connecte

        if (!$user) {
            // si il n'est pas connecte on redirige vers la page de connexion

            $this->addFlash('danger', 'Connectez-vous pour acceder a cette fonctionnalite');
            return $this->redirectToRoute('app_login');
        }

        // si il y a un utilisateur on verifie si il a les droits necessaires pour se trouver sur cette page
        if ($user === $recette_aSupp->getAuthor()) {
            // on cherche la recette dans la bdd grace a la methode find
            $recette_aSupp = $this->recetteRepository->find($id);
            if (!$recette_aSupp) {
                // si la recette n'existe pas, on affiche un message d'erreur et on redirige vers le catalogue

                $this->addFlash('warning', 'Aucune recette trouve');
            }
            // si la recette existe, on recupere le chemin de la photo

            $imgFile = 'uploads/' . $recette_aSupp->getPhoto();

            // on verifie qu'il y a bien de donnees stockes dans la variable
            if ($imgFile) {
                // si oui, on efface l'image
                unlink($imgFile);
            }
            // on fait les modifications necessaires dans la base de donnees grace a l'entityManager
            $this->entityManager->remove($recette_aSupp);
            $this->entityManager->flush();
            // on affiche un message et on redirige vers le catalogue
            $this->addFlash('success', "La recette a bien ete supprime!");
            return $this->redirectToRoute('recette', ['page' => 1]);

            // si l'utilisateur n'as pas les droits necessaires

        } elseif ($user === $recette_aSupp->getAuthor() || !$this->isGranted("ROLE_ADMIN")) {
            // on affiche un message et on redirige vers le catalogue recette utilisateur

            $this->addFlash('warning', "Vous ne pouvez pas supprimer cette recette car vous n'etez ni admin, ni auteur");
            return $this->redirectToRoute('recette', ['page' => 1]);
        }
    }

    /**
     * Methode de recherche dans la base de donnes 
     */
    #[Route('/search/{page}', name: 'search')]

    public function search( int $page ,Request $request)
    {
        // on cherche dans la base de donnes ce que l'utilisateur a insere dans le champs search
        // grace a une fonction definie dans recetteRepository
    
        // $var = explode($request->query->get('search_value'));
        $valeurs = explode(' ',$request->query->get('search_value'));
        $recettes = array();
       for ($i=0; $i < count($valeurs); $i++) { 
        $resultats  = $this->recetteRepository->findByExampleField($valeurs[$i],$page-1,count($valeurs));
           for ($j=0; $j < count($resultats) ; $j++) { 
            array_push($recettes,$resultats[$j]);
            
           }
       }
        if(empty($recettes)){

            // faudra creer un template pour cette erreur
            $recettes = "Nous n'avons rien trouvé , veuillez essayer autre chose";
        }
        
        // et on redirige vers la page d'affichage des recettes
        return $this->render('recette/index.html.twig', [
            'recettes' => $recettes,
            'total'=>count($recettes),
        ]);
    }
    /* fonction qui supprime un ingredient de la liste d'ingredients d'une recette.
        accessible a partir de la page d'affichage d'une recette
        prend en parametre un entier qui represente l'id de l'enregistrement a supprimer
        Cette fonction redirige vers la vue d'une recette
    */
    #[Route('/delete_ingredient/{id}', name: 'delete_ingredient')]

    public function delete_ingredient(int $id)
    {
        // on verifie si l'utilisateur est connecte
        if (!$this->IsGranted('ROLE_USER')) {
            $this->addFlash('danger', 'vous devez vous connecter pour acceder a cette fonctionnalite');
            return $this->redirectToRoute('app_login');
        }
        // on cherche la relation dans la base grace a l'id
        $relationaSupp = $this->possederRepository->find($id);
        // on cherche le proprietaire de la recette pour faire des verifications plus tard
        $proprietaire = $relationaSupp->getRecette()->getAuthor()->getId();

        // on verifie que l'utilisateur est bien le proprio de la recette pour pouvoir la modifier 
        if (!$this->getUser()->getId() != $proprietaire) {
            // si il ne l'est pas, on redirige avec un message
            $this->addFlash('warning', 'vous devez etre admin ou proprietaire de la recette pour effectuer cette tache');
            return $this->redirectToRoute('show_recette', ['id' => $relationaSupp->getRecette()->getId()]);
        }
        // si oui, on retire la ligne de la bdd
        $this->entityManager->remove($relationaSupp);
        $this->entityManager->flush();

        // on redirige vers la page ou l'utilisateur etait avant
        $this->addFlash('success', 'Ingredient retire');
        return $this->redirectToRoute('show_recette', ['id' => $relationaSupp->getRecette()->getId()]);
    }
}
