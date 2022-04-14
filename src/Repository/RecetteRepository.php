<?php

namespace App\Repository;

use App\Entity\Recette;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * classe de lecture de donnes de la table Recette
 * @method Recette|null find($id, $lockMode = null, $lockVersion = null)
 * @method Recette|null findOneBy(array $criteria, array $orderBy = null)
 * @method Recette[]    findAll()
 * @method Recette[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RecetteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recette::class);
    }
/**
 * Methode d'affichage de recettes par nb de recette par page.
 * Elle prend en parametres les entiers $page et $nbrecettes
 * et elle renvoi le resultat de la requete sql
 */
    public function findByPage($page,$nbrecettes){
        // on cree la requete sur la table recettes represente par 'r' ici.
        return $this->createQueryBuilder('r')
        // on demande que le resultat soit ordonnee par l'id de la recette par ordre Croissant
        ->orderBy('r.id','ASC')
        // on demande que le nombre maximal de resultats trouves soient inf ou egal a $nbrecettes
        ->setMaxResults($nbrecettes)
        // et on defini le premier resultat comme etat le produit de la page et de la recette
        // comme ca on peut afficher de maniere precise
        ->setFirstResult($page*$nbrecettes)
        // on execute la requete et on recup le resultat
        ->getQuery()
        ->getResult();
    }
    /**
     * Methode qui permet de compter le nb de recettes disponibles dans la base de donnes 
     * Cette methode ne prend pas de parametres
     * elle renvoie un resultat 
     */
    public function compter(){
        // on cree la requete sql sur la table recettes
        return $this->createQueryBuilder('r')
        // on compte le nombre de champs dans la colonne id de la recette
        ->select('count(r.id)')
        // on execute et on recupere le resultat
        ->getQuery()
        ->getSingleScalarResult();
    }
    /**
     * Methode qui permet de retrouver les 3 derniers recettes ajoutes dans la bdd
     * elle prend pas de parametres
     * elle renvoie un resultat sql
     * 
     */
    public function findLast(int $nbAfficher){
        // creation de la requette sur la table recettes
        return $this->createQueryBuilder('r')
        // On veut que le resultat soit ordonnee par id de recette de maniere decroissante
        ->orderBy('r.id','DESC')
        // on met le nb maximal de recettes a 3
        ->setMaxResults($nbAfficher)
        // on execute et on recupere les donnes
        ->getQuery()
        ->getResult();
    }

    /**
     * Methode qui permet de rechercher une recette par nom ou par ingredient
     * elle prend en paramentre une chaine de caracteres $value
     */
    public function findByExampleField($value)
    {
        // on cree la requete sur la table recette
        return $this->createQueryBuilder('r')
        // on met la condition sur la table recette et la colonne titre
            ->andWhere('r.titre LIKE :val')
            // ou sur la table ingredient et colonne nom
            ->orWhere('i.nom like :val')
            // on relie les tables
            ->innerJoin('r.posseders','p')
            ->innerJoin('p.ingredients','i')
            // on defini la valeur a chercher
            ->setParameter('val', '%'.$value.'%')
            // ordonne par id maniere croissante
            ->orderBy('r.id', 'ASC')
         
            // on execute la requete et on recup le resultat
            ->getQuery()
            ->getResult();
            // on execute et on renvoie le resultat
           
        ;
    }

    // /**
    //  * @return Recette[] Returns an array of Recette objects
    //  */
    /*
    
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Recette
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
