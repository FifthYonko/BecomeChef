<?php
namespace App\Security;

use App\Entity\User; // your user entity
use App\Repository\UserRepository;
use App\Security\Exception\NotVerifiedEmailException;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use League\OAuth2\Client\Provider\GithubResourceOwner;

class GithubAuthenticator extends OAuth2Authenticator
{
    private $clientRegistry;
    private $entityManager;
    private $router;
    protected $userRepository;

    public function __construct(ClientRegistry $clientRegistry, EntityManagerInterface $entityManager, RouterInterface $router, UserRepository $ur)
    {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->userRepository = $ur;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new RedirectResponse($this->router->generate('app_login'));
    }
    
    /**
     * Si la route correspond à celle attendue, alors on déclenche l'authentification
    **/
    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'github_check';
    }

  /***
     * Méthode d'authentification via git Hub
     */
    public function authenticate(Request $request): Passport
    {
    /**
     * Récupère l'utilisateur à partir du AccessToken
     * 
     */
        $client = $this->clientRegistry->getClient('github');
        $accessToken = $this->fetchAccessToken($client);
        


        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {

                /** @var GithubRessourceOwner $githubUser */
                $githubUser = $client->fetchUserFromToken($accessToken);
                

                // on récupère l'email  de l'utilisateur
                $respose = HttpClient::create()->request(
                    'GET',
                    'https://api.github.com/user/emails',
                    [
                        'headers' => [
                            'authorization' => "token {$accessToken->getToken()} "
                        ]
                    ]

                );
                
                $emails = json_decode($respose->getContent(), true);

             //   avec github on peut creer des publics emails pour des raisons de sécurité ou données privées.
            // donc on va vérifier les emails du compte afin de récupérer celui qui a servi a la création du compte
                foreach ($emails as $email) {

                    // donc on vérifie qu'il est primaire mais aussi que l'utilisateur a bien vérifié son email 
                    // car ça empeche un utilisateur de se connecter à notre application avec des comptes pas vérifie et de faire 
                    // Quelque chose qui peut nuire à notre site.

                    if ( $email['primary'] === true && $email['verified'] === true) {
                        $data = $githubUser->toArray();
                        $data['email'] = $email['email'];
                        $githubUser = new GithubResourceOwner($data);
                    }
                }
                if ($githubUser->getEmail() === null){
                    throw new NotVerifiedEmailException();
                }
                
                return $this->userRepository->findorCreateFromOauth($githubUser);

               
            })
        );
    }
/**
 * Si l'authentification a réussi, on redirige vers la page d'accueil
 */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $targetUrl = $this->router->generate('home');

        return new RedirectResponse($targetUrl);
    }
/**
 * Sinon on interdit l'access
 */
  
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }
}
