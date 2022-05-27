<?php

namespace App\Repository;

use App\Entity\Notation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Notation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Notation[]    findAll()
 * @method Notation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notation::class);
    }
    
    /**
     * methode qui permet de trouver la recette ayant la meilleure note parmis toutes les recettes
     *
     * 
     * AVEC SOUS-REQUETE
     * SELECT recette,max(moyenne) FROM (SELECT recette_note_id AS recette ,AVG(note) as moyenne FROM `notation` GROUP BY recette_note_id) as moyRecette; 
     *
     * SANS SOUS-REQUETE
     * 
     * SELECT recette_note_id, avg(note) as moyenne FROM `notation` GROUP BY recette_note_id ORDER by avg(note) DESC LIMIT 1; 
     */


    public function meilleureRecette(){
        return $this->createQueryBuilder('n')
        ->select('avg(n.note) , rn.id')
        ->innerJoin('n.recetteNote','rn')
        ->groupBy('n.recetteNote')
        ->orderBy('avg(n.note)','DESC')
        ->setMaxResults(1)
        ->getQuery()
        ->getResult();
    }

    // public function meilleureRecette(){
    //     return $this->createQueryBuilder('n')
    //     ->select('rn.id')
    //     ->innerJoin('n.recetteNote','rn')
    //     ->getQuery()
    //     ->getResult();
    // }

    /***
     * Methode qui permet de recuperer la note moyenne d'une recette grace a son id
     * 
     */
    public function moyenneNotation($idRecette){
        return $this->createQueryBuilder('n')
        ->select('avg(n.note)')
        ->where('n.recetteNote = :val')
        ->setParameter('val', $idRecette)
        ->getQuery()
        ->getSingleScalarResult();
    }

      /***
     * Methode qui permet de verifier si un utilisateur a deja notee une recette.
     * 
     */
    public function verifierNotation($idRecette,$idUser){
        return $this->createQueryBuilder('n')
        ->select('1')
        ->where('n.recetteNote = :val1')
        ->andwhere('n.noteur = :val2')
        ->setParameters(['val1'=> $idRecette, 'val2'=>$idUser])
        ->getQuery()
        ->getOneOrNullResult();
    }
    

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Notation $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Notation $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return Notation[] Returns an array of Notation objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('n.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Notation
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
