<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;


class GithubController extends AbstractController
{
   /**
     * Méthode qui permet la connexion via son compte github, cette methode redirige l'utilisateur vers une page d'autorisation de 
     * d'access aux informations.On demande la permision d'acceder aux infos comme le nom ou l'email
     * Paramètres :  clientRegistry inclus dans le bundle KnpOauth qui permet de recup tous les clients installees.
     * 
     */
    #[Route(path: '/connect/github', name: 'github_connect')]
    public function connect(ClientRegistry $clientRegistry)
    {
       
        $client = $clientRegistry->getClient('github');

        return $client->redirect(['read:user', 'user:email']);
    }

    #[Route(path: '/connect/google', name: 'google_connect')]
    public function connectG(ClientRegistry $clientRegistry){
        $client = $clientRegistry->getClient('google');
        return $client->redirect();
    }
}