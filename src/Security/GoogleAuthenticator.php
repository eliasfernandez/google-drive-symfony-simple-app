<?php

namespace App\Security;

use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Google;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\{RedirectResponse, Response};
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private Google $provider,
        private DocumentManager $dm,
        private LoggerInterface $logger,
    ) { }

    public function supports(Request $request): ?bool
    {
        return $request->get('_route') == 'auth_google';
    }

    public function authenticate(Request $request): Passport
    {
        $authCode = $request->query->get('code');
        if (!$authCode) {
            return new SelfValidatingPassport(new UserBadge('failed'));
        }

        return new SelfValidatingPassport(
            new UserBadge($authCode, [$this, 'getUserFromGoogleAuthCode']),
            [new RememberMeBadge]
        );
    }

    public function getUserFromGoogleAuthCode($authCode)
    {
        // Exchange an authorization code for an access token
        $token = $this->provider->getAccessToken('authorization_code', ['code' => $authCode]);

        $ownerDetails = $this->provider->getResourceOwner($token);
        $googleId = $ownerDetails->getId();

        $users = $this->dm->getRepository(User::class);
        $user = $users->findOneBy(['googleId' => $googleId]);

        // This user has already connected -- sign them in to their existing account
        // and refresh the token
        if ($user) {
            $user->setLastToken(json_encode($token->jsonSerialize()));
            $this->dm->flush();
            return $user;
        }


        // The user hasn't connected via Google before, but there's an account with the same email
        // as the one on the Google account. Add the Google ID to the existing account and sign in.
        $email = $ownerDetails->getEmail();

        if ($user = $users->findOneBy(['email' => $email])) {
            $user->setGoogleId($googleId);
            $user->setLastToken(json_encode($token->jsonSerialize()));
            $this->dm->flush();
            return $user;
        }

        // The user hasn't connected via Google before, and there's no existing account with the
        // Google account's email. Register a new user.
        $user = (new User)
            ->setEmail($email)
            ->setName($ownerDetails->getName())
            ->setLastToken(json_encode($token->jsonSerialize()))
            ->setGoogleId($googleId);

        $this->dm->persist($user);
        $this->dm->flush();

        return $user;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewall_name): ?Response
    {
        // The user has logged in, redirect them somewhere.
        // It's important that they're redirected so that the auth code doesn't remain in their URL.
        return new RedirectResponse('/');
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new RedirectResponse('/login');
    }
}
