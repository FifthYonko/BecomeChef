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
 * ON UTILISE KPNPAGINATOR DONC PLUS BESOIN DE CETTE FONCTION
 */
    // public function findByPage($page,$nbrecettes){
    //     return $this->createQueryBuilder('r')
    //     ->orderBy('r.id','ASC')
    //     ->setMaxResults($nbrecettes)
    //     ->setFirstResult($page*$nbrecettes)
    //     ->getQuery()
    //     ->getResult();
    // }


    /**
     * Methode qui permet de compter le nb de recettes disponibles dans la base de donnes 
     * Cette methode ne prend pas de parametres
     * elle renvoie un resultat 
     * !!!!! ON UTILISE KPNPAGINATOR DONC PLUS BESOIN DE CETTE FONCTION !!!!
     */

    public function compterRecette(){
        return $this->createQueryBuilder('r')
        ->select('count(r.id)')
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
        return $this->createQueryBuilder('r')
        ->orderBy('r.id','DESC')
        ->setMaxResults($nbAfficher)
        ->getQuery()
        ->getResult();
    }

    /**
     * Méthode qui permet de rechercher une recette par nom ou par ingrédient
     * elle prend en paramètre une chaine de caractères $value
     */
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.titre LIKE :val')
            ->orWhere('i.nom like :val')
            ->innerJoin('r.posseders','p')
            ->innerJoin('p.ingredients','i')
            ->setParameter('val', '%'.$value.'%')
            ->orderBy('r.id', 'ASC')
         
            ->getQuery()
            ->getResult();
           
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
