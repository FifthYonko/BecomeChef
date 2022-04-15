<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;


/**
 *  * classe de lecture de donnes de la table User
 * 
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */

class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * Methode de recherche des utilisateurs bannis
     * Elle ne prend pas de parametres
     * elle renvoie un resultat
     */
    public function findBannedUsers()
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.etat = :val')
            ->setParameter('val', 1)
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findorCreateFromOauth(ResourceOwnerInterface $owner)
    {
        $user = $this->createQueryBuilder('u')
            ->where('u.githubId = :githubId')
            ->setParameters([
                'githubId' => $owner->getId()
            ])
            ->getQuery()
            ->getOneOrNullResult();
        if ($user) {
            return $user;
        }
        $user = (new User())
            ->setRoles(['ROLE_USER'])
            ->setGithubId($owner->getId())
            ->setEmail($owner->getEmail())
            ->setPseudo($owner->getNickname());
        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }
    public function findOrCreateGoogleAuth($owner)
    {
        $user = $this->createQueryBuilder('u')
            ->where('u.googleId = :googleId')
            ->setParameters([
                'googleId' => $owner->getId()
            ])
            ->getQuery()
            ->getOneOrNullResult();
        if ($user) {
            return $user;
        }
        $user = (new User())
            ->setRoles(['ROLE_USER'])
            ->setGoogleId($owner->getId())
            ->setEmail($owner->getEmail())
            ->setPseudo($owner->getFirstName());
        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    // /**
    //  * @return User[] Returns an array of User objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
