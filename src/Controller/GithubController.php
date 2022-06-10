<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;


class GithubController extends AbstractController
{
   /**
     * Méthode qui permet la connexion via son compte github
     * Paramètres : $clientregistry du type ClientRegistry qui est un type de l'api Oauth2 installé
     */
    #[Route(path: '/connect/github', name: 'github_connect')]
    public function connect(ClientRegistry $clientRegistry)
    {
        /** @var GithubClient $client */
        $client = $clientRegistry->getClient('github');

        return $client->redirect(['read:user', 'user:email']);
    }

    #[Route(path: '/connect/google', name: 'google_connect')]
    public function connectG(ClientRegistry $clientRegistry){
        $client = $clientRegistry->getClient('google');
        return $client->redirect();
    }
}