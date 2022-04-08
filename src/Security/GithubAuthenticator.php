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

    public function supports(Request $request): ?bool
    {
        // continue ONLY if the current ROUTE matches the check ROUTE
        return $request->attributes->get('_route') === 'oauth_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('github');
        $accessToken = $this->fetchAccessToken($client);
        


        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {

                /** @var GithubRessourceOwner $githubUser */
                $githubUser = $client->fetchUserFromToken($accessToken);
                

                // on recupere l'email  de l'utilisateur


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
              
                foreach ($emails as $email) {
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

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // change "app_homepage" to some route in your app
        $targetUrl = $this->router->generate('home');

        return new RedirectResponse($targetUrl);

        // or, on success, let the request continue to be handled by the controller
        //return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }
}