<?php

namespace App\DataFixtures;

use Faker\Provider\ImmutableDateTime;
use Faker\Factory;
use App\Entity\User;
use App\Entity\Category;
use App\Entity\Recette;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;



class AppFixtures extends Fixture
{
    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 3; $i++) {
            $user = new User();
            $user->setEmail($faker->email());
            $password = $this->hasher->hashPassword($user, 'test45');
            $user->setPseudo("$faker->name()");
            $user->setPassword($password);
            $manager->persist($user);
        }

        for ($i = 0; $i < 20; $i++) {

            $recette = new Recette();
            $recette->setTitre($faker->text());
            $recette->setCreatedAt(ImmutableDateTime::immutableDateTimeBetween());
            $recette->setSynopsis($faker->text());
            $recette->setImg("papi-mamie-61fa49a963f14.jpg");

            $author = new Author();
            $author->setName($faker->name());
            $manager->persist($author);
            $book->setAuthor($author);

            $category = new Category();
            $category->setName($faker->word());
            $manager->persist($category);
            $book->setCategory($category);

            $manager->persist($book);
        }

        $manager->flush();
    }
}