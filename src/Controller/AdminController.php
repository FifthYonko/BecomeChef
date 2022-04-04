<?php

namespace App\Controller;

use App\Form\CommentaireType;
use App\Form\RecetteFormType;
use App\Repository\CommentaireRepository;
use App\Repository\RecetteRepository;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    public function __construct(private UserRepository $userRepository, private EntityManagerInterface $entityManager,private RecetteRepository $recetteRepository)
    {
    }

    // fonction de ban reserve aux utilisateurs disposant du role Admin. 
    // Cette fonction prend en parametres un entier qui est l'id de l'utilisateur a bannir et ne renvoie aucune donnee, juste une redirection vers une autre page, en fonction des conditions remplies.
    #[Route('admin/ban/{id}', name: 'ban')]
    public function ban( int $id)
    {
        // verification de securite du role de l'utilisateur
        if (!$this->isGranted('ROLE_ADMIN')) {
            // s'il n'est pas admin, on affiche un message et on redirige vers la page d'accueil
            $this->addFlash('danger', 'Vous ne disposez pas des access necessaires');
            return $this->redirectToRoute('home');
        }
        // si il arrive ici, c'est qu'il dispose des droits admin, du coup on cherche dans la base de donnees, si l'utilisateur a bannir existe, grace a l'id recu en argument de la fonction
        $user = $this->userRepository->find($id);
        // si  l'utilisateur n'existe pas, on affiche un message et on redirige vers la page d'accueil
        if (!$user) {
            $this->addFlash('warning', 'L\'utilisateur n\'existe pas');
            return $this->redirectToRoute('home');
        // si l'utilisateur existe, mais que c'est le meme que l'utilisateur connecte, on affiche un message, et on redirige vers l'accueil car un utilisateur ne peut pas se bannir lui-meme
        } elseif ($user->getId() == $this->getUser()->getId()) {
            $this->addFlash('warning', 'Vous ne pouvez pas vous bannir vous-meme');
            return $this->redirectToRoute('home');
            // on verifie que l'utilisateur a bannir n'est ni un Admin ni le SuperAdmin. car un admin ne peut pas bannir un autre admin, ou le superadmin
        } elseif (in_array('ROLE_SUPER_ADMIN', $user->getRoles()) || in_array('ROLE_ADMIN', $user->getRoles())) {
            // si c'est le cas, on affiche un message et on redirige vers l'accueil
            $this->addFlash('warning', 'Vous ne disposez pas des droits necessaires pour cette action');
            return $this->redirectToRoute('home');
        }
        // si on arrive ici, ca veut dire que l'utilisateur existe et qu'il ne dispose pas d'autres droits que celui de USER
        $user->setEtat(1); // on ajoute 1 a l'etat de l'utilisateur car 1 signifie true, et la colonne etat dans la bdd  represente l'etat de bannisement de l'utilisateur
        
        // en utilisant l'entity manager de doctrine, on modifie l'enregistrement dans la bdd
        $this->entityManager->persist($user);// la methode persist permet de preparer la requette sql
        $this->entityManager->flush();// la methode flush, execute la commande prepare avant.
        return $this->redirectToRoute('home'); // on redirige vers l'accueil
    }

    /* Fonction inverse a la fonction ban, elle permet de debannir
        elle prend en parametre un entier, qui represente l'id de l'utilisateur sur qui on veut faire l'action

    */
    #[Route('admin/unban/{id}', name: 'unban')]
    public function unban( int $id)
    {   
        // on met en place des mesures de securite , pour verifier le role de l'utilisateur
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'Vous ne disposez pas des access necessaires');
            return $this->redirectToRoute('home');
        }
        // on suit la meme procedure que pour la fonction ban 
        $user = $this->userRepository->find($id);

        if (!$user) {
            $this->addFlash('warning', 'L\'utilisateur n\'existe pas');
            return $this->redirectToRoute('home');
        } elseif (array_search('ROLE_SUPER_ADMIN', $user->getRoles())) {
            $this->addFlash('warning', 'Vous ne disposez pas des droits necessaires pour cette action');
            return $this->redirectToRoute('home');
        }
        // Ici au lieu de mettre 1 (true) a l'etat de l'utilisateur, on mettra 0 qui represente false
        $user->setEtat(0);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $this->redirectToRoute('home');
    }

    /* La methode inspecter_profil est une methode reserve aux ceux disposant du droit Admin
        Cette fonction comme son nom l'indique , permet d'inspecter le profil d'un utilisateur autre que soi-meme
        Elle prend en parametre un entier , l'id de l'utilisateur a qui on veut inspecter le profil
        // cette methode ne renvoie rien, elle redirige vers une page
    */
    #[Route('admin/inspect_profil/{id}', name: 'inspect_profil')]
    public function inspect_profil(int $id)
    {
        // on verifie les droits de l'utilisateur qui veut acceder a cette page
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'Vous ne disposez pas des access necessaires');
            return $this->redirectToRoute('home');
        }
        // si id recu en argument est le meme qui l'id de l'utilisateur connecte, on redirige vers la page profil
        if ($this->getUser()->getId() === $id) {
            $this->addFlash('success', 'Voici votre profil');
            return $this->redirectToRoute('profile');
        }
        // sinon , on cherche l'id dans la base de donnees et on affiche le profil de l'utilisateur
        $user = $this->userRepository->find($id);
        return $this->render('admin/inspect_profile.html.twig', [
            'user' => $user,

        ]);
    }
    /* Cette methode redirige l'utilisateur disposant du droit admin vers la page catalogue ou on trouve l'ensemble des recettes disponibles
        elle prend en parametre un entier page, qui represente le nr de page sur lequel on veut aller
    */
    #[Route('/admin/recette/{page}', name: 'recette_admin')]
    public function index(int $page): Response
    {
        /* En utilisant la methode findByPage (definie dans RecetteRepository.php) qui prend en argument 2 entiers, une page, et un nombre de recettes a afficher
            on recupere dans une variable $recette , le resultat de la recherche dans la base de donnnes 
        */
        $recette = $this->recetteRepository->findByPage($page-1,3);
        /* Dans une autre variable, nbtotal et en utilisant la methode compter (aussi definie dans RecetteRepository.php), on recupere le nombre total de recettes disponibles
        dans notre base de donnees. 
        */
        $nbtotal = $this->recetteRepository->compter();
        // on fait une redirection vers le template(la vue) avec les arguments definis plus haut dans la fonction
        return $this->render('admin/index_recetteAdmin.html.twig', [
            'recettes' => $recette,
            'total' =>$nbtotal,
        ]);
    }

    /* Methode destinee aux admin pour ajouter des nouvelles recettes
        Cette methode instancie le composant symfony Request qui nous permet de gerer les les donnees envoies en post ou en GET
        On recupere aussi le Service FileUploader defini dans le dossier Service. Dans ce service on retrouve les methodes necessaires
        au l'upload de fichiers par l'utilisateur
    
    */
    #[Route('/admin/new_recette', name: 'new_recette_admin')]
    public function ajout_recette(Request $request, FileUploader $fileUploader): Response
    {
    //   on verifie que l'utilisateur dispose des droits necessaires a cette action
        if (!$this->isGranted('ROLE_USER') || !$this->isGranted('ROLE_ADMIN')) {

            $this->addFlash('warning', 'Vous devez etre connecte ou etre admin pour acceder a cette page!');
            return $this->redirectToRoute('app_login');
        }
        // on verifie que l'utilisateur n'est pas banni car les utilisateurs bannis n'ont pas le droit d'ajouter des recettes
        if($this->getUser()->getEtat() === true ){
            $this->addFlash('danger','Vous etes banni et donc vous ne pouvez plus acceder a cette fonctionnalite');
            return $this->redirectToRoute('home');
        }

        // si les conditions sont verifies , on cree le formulaire grace au type RecetteFormType qui se trouve dans le dossier Form 
        // et qui nous permet de definir les champs du formulaire avec contraintes necessaires
        $form_recette = $this->createForm(RecetteFormType::class);
        // on s'occupe des informations transmises par l'utilisateur
        $form_recette->handleRequest($request);
        // on verifie que l'utilisateur a bien appuye sur le submit, et que tous les champs sont bien remplis
        if ($form_recette->isSubmitted() && $form_recette->isValid()) {
            // si oui, on recupere les donnees et on les stocke dans la variable new_recette
            $new_recette = $form_recette->getData();
            // on recupere aussi les donnees de l'image 
            $imgFile = $form_recette->get('photo')->getData();
            // on verifie que l'image existe
            if ($imgFile) {
                // si elle existe on appele la methode upload du service FIleUploader
                $newFileName = $fileUploader->upload($imgFile);
                // on modifie le nom de la photo dans la recette car le nouveau nom a ete modifie de telle sorte a etre safe pour notre application
                $new_recette->setPhoto($newFileName);
            }
            else{
                $new_recette->setPhoto('BecomeChefLogo.png');
            }
            //on complete aussi l'autheur de la recette en recuperant les donnes du compte connecte     
            $new_recette->setAuthor($this->getUser());
            // on recupere les informations du champ ingredient pour completer la relation entre les tables
            // pour avoir les ingredients de chaque recette
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
            return $this->redirectToRoute('recette_admin',['page'=>1]);
        }
        // si le formulaire n'est pas complete, on redirige vers la vue du formulaire
        return $this->render('admin/new_recetteAdmin.html.twig', [
            'form_recette' => $form_recette->createView(),
        ]);
    }
    /*
        Route admin pour l'affichage d'une recette , elle prend en parametre le composant symfony Request et un entier, cet entier est l'id de la recette a afficher
        Cette methode renvoie sur une page d'affichage d'une seule recette    
    */
    #[Route('admin/show_recette/{id}', name: 'show_recette_admin')]
    public function show_recette(Request $request, int $id)
    {
        // on utilise la methode findOneBy() definie dans le RecetteRepository.php qui nous permet d'interoger notre 
        // base de donnees pour retrouver un enregistrement en fonction de la colonne sur laquelle on recherche
        $recette = $this->recetteRepository->findOneBy(['id' => $id]);
        // on recupere aussi les commentaires en rapport avec la recette grace a la methode getCommentaire() definie dans l'entite Recette 
        $commentaires = $recette->getCommentaires();
        
        // on verifie que l'utilisateur est bien connecte pour pouvoir poster de commentaires
        if ($this->IsGranted('ROLE_USER') ) {
            $form_comm = $this->createForm(CommentaireType::class);
            $form_comm->handleRequest($request);
    
            // si le formulaire du commentaire a ete ajoute et que les champs sont valides
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
                return $this->redirectToRoute('show_recette_admin', ['id' => $id]);
            }
            // sinon on affiche la page de la recette avec les informations contenues dans recette,commentaires,et le formulaire d'ajout de commentaire
            return $this->renderForm('admin/show_recetteAdmin.html.twig', [
                'recette' => $recette,
                'comments' => $commentaires,
                'form_comm' => $form_comm,
            ]);
        }
      
        // si l'utilisateur n'est pas connecte, on affiche pas le formulaire d'ajout de commentaires
        return $this->renderForm('admin/show_recetteAdmin.html.twig', [
            'recette' => $recette,
            'comments' => $commentaires,
            
        ]);
    }

    /*
        methode admin pour modifier les recettes existantes.
        Cette methode prend en parametres le composant symfony Request, un entier id qui represente l'id de la recette a modifier, et le service FileUploader
        Elle renvoie une redirection vers le catalogue des recettes si tout se passe bien
    */
    #[Route('admin/update_recette/{id}', name: 'update_recette_admin')]
    public function update_recette(Request $request, int $id, FileUploader $fileUploader )
    {
        // on recherche la recette dans la bdd grace a son id
        $recette_modifie = $this->recetteRepository->find($id);
        
        if (!$recette_modifie) {
            // si elle existe pas, on met un warning 
            $this->addFlash('warning', 'Aucune recette trouve');
        }
        // on recupere le nom de l'image existante (si elle existe)
        $imageExistante = $recette_modifie->getPhoto();
        // si elle existe , on verifie que l'utilisateur est soit connecte et proprio de la recette, soit un admin
        if ($this->isGranted('ROLE_ADMIN') ) {
            // on cree le formulaire
            $update_recette = $this->createForm(RecetteFormType::class, $recette_modifie);
            $update_recette->handleRequest($request);

            if ($update_recette->isSubmitted() && $update_recette->isValid()) {
                // si le form a ete valide et soumis, on recupere les infos
                $recette_modifie = $update_recette->getData();
                // on ajoute les informations concernant les ingredients
                foreach ($update_recette->get('posseders')->getData() as $ingredient) {

                    $ingredient->setRecette($recette_modifie);
                }
                // on recupere les informations concernant la photo
                $imgFile = $update_recette->get('photo')->getData();
                // on verifie si elle existe, cad si la variable contient qq chose different de null 
                if ($imgFile) {
                    // si on a une nouvelle image on verifie si il y avait une autre image avant
                    if($imageExistante && $imageExistante != "BecomeChefLogo.png"){
                        // si oui, on efface l'image precendante grace a la methode unlink qui prend en parametre un string contenant le chemin vers l'image
                        unlink('uploads/'.$imageExistante);
                    }
                    // on appelle le service Fileupload pour les modifications sur l'image
                    $newFileName = $fileUploader->upload($imgFile);
                    // on affecte la nouvelle valeur au champ photo de l'objet recette
                    $recette_modifie->setPhoto($newFileName);
                    // on modifie l'info dans la recette

                }
                // on envoie les donnees dans la base et on redirect vers le catalogue avec un message
                $this->entityManager->persist($recette_modifie);
                $this->entityManager->flush();

            
                $this->addFlash('success', "La recette a bien ete modifie :)");
                return $this->redirectToRoute('recette_admin',['page'=>1]);
            }
            // si l'utilisateur n'est pas admin ou le proprietaire de la recette
        } else {
            // on verifie si l'utilisateur est connecte 
            if ($this->isGranted('ROLE_USER')) {
                // si oui, on lui affiche un meessage et on redirige vers la page catalogue recettes de l'utilisateur
                $this->addFlash("danger", "Vous devez etre Admin ou le proprietaire de la recette pour la modifier!");
                return $this->redirectToRoute('recette',['page'=>1]);
                
            } else {
                // si il n'est pas connecte , on affiche un message et on redirige vers le formulaire de connexion
                $this->addFlash("danger", "Vous devez etre soit connecte soit Admin ou le proprietaire de la recette pour la modifier!");
                return $this->redirectToRoute('app_login');
            }
        }
        // sinon on redirige vers la page d'ajout de recette
        return $this->render('admin/update_recetteAdmin.html.twig', [
            'update_recette' => $update_recette->createView(),
        ]);
    }
    /* Methode admin pour supprimer une recette 
        Elle prend en parametre un entier id qui represente l'id de la recette a supprimer
    */
    #[Route('admin/delete_recette/{id}', name: 'delete_recette_admin')]
    public function delete_recette( int $id)
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
        // grace a la methode IsGranted();
       
        if ($this->isGranted('ROLE_ADMIN') ) {
            // si oui, on cherche la recette dans la bdd grace a la methode find
            $recette_aSupp = $this->recetteRepository->find($id);
            // si la recette n'existe pas, on affiche un message d'erreur et on redirige vers le catalogue
            if (!$recette_aSupp) {
                $this->addFlash('warning', 'Aucune recette trouve');
                return $this->redirectToRoute('recette_admin',['page'=>1]);
            }
            // si la recette existe, on recupere le chemin de la photo
            $imgFile ='uploads/'.$recette_aSupp->getPhoto();
            // on verifie qu'il y a bien de donnees stockes dans la variable
            if($imgFile){
                // si oui, on efface l'image
                unlink($imgFile);
            }
            // on fait les modifications necessaires dans la base de donnees grace a l'entityManager
            $this->entityManager->remove($recette_aSupp);
            $this->entityManager->flush();
            // on affiche un message et on redirige vers le catalogue
            $this->addFlash('success', "La recette a bien ete supprime!");
            return $this->redirectToRoute('recette_admin',['page'=>1]);
        } 
        // si l'utilisateur n'as pas les droits necessaires
        elseif (!$this->isGranted("ROLE_ADMIN")) {
            // on affiche un message et on redirige vers le catalogue recette utilisateur
            $this->addFlash('warning', "Vous ne pouvez pas supprimer cette recette car vous n'etez ni admin, ni auteur");
            return $this->redirectToRoute('recette',['page'=>1]);
        }
    }

    /* fonction qui supprime un ingredient de la liste d'ingredients d'une recette.
        accessible a partir de la page d'affichage d'une recette
        prend en parametre un entier qui represente l'id de l'enregistrement a supprimer
        Cette fonction redirige vers la vue d'une recette
    */
    #[Route('admin/delete_ingredient/{id}', name: 'delete_ingredient_admin')]

    public function delete_ingredient(int $id){
        // on verifie si l'utilisateur est connecte
        if(!$this->IsGranted('ROLE_USER')){
            $this->addFlash('danger','vous devez vous connecter pour acceder a cette fonctionnalite');
            return $this->redirectToRoute('app_login');
        }
        // on cherche la relation dans la base grace a l'id
        $relationaSupp = $this->possederRepository->find($id);
    
        // on verifie que l'utilisateur est bien admin 
        if(!$this->isGranted('ROLE_ADMIN')  ){
            // si il ne l'est pas, on redirige avec un message
            $this->addFlash('warning','vous devez etre admin ou proprietaire de la recette pour effectuer cette tache');
            return $this->redirectToRoute('show_recette',['id'=>$relationaSupp->getRecette()->getId()]);
        }
        // si oui, on retire la ligne de la bdd
        $this->entityManager->remove($relationaSupp);
        $this->entityManager->flush();

        // on redirige vers la page ou l'utilisateur etait avant
        $this->addFlash('success','Ingredient retire');
           return $this->redirectToRoute('show_recette_admin',['id'=>$relationaSupp->getRecette()->getId()]);
       
        
    }

    #[Route('admin/delete_commentaire/{id}/{idR}', name: 'delete_commentaire_admin')]
    public function delete_commentaire(int $id,int $idR,CommentaireRepository $commentaireRepository, EntityManagerInterface $entityManager)
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
        // on verifie si l'utilisateur connecte est bien un admin
        if($this->isGranted('ROLE_ADMIN') ){
            // si oui, on fais les modifications necessaires dans la bdd
            $entityManager->remove($comment_aSupp) ;
            $entityManager->flush();
            // et on affiche un message avec une redirection vers la page sur laquelle on etait
            $this->addFlash('success','Le commentaire a bien ete supprime');
            return $this->redirectToRoute('show_recette_admin ',['id'=>$idR]);
        }
        // sinon on affiche un message d'erreur et on redirige
        else{
            $this->addFlash('warning','Vous ne disposez pas de ces droits');
            return $this->redirectToRoute('show_recette',['id'=>$idR]);
        }
    }
}
