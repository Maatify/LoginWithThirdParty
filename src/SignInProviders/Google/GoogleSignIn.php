<?php
/**
 * @PHP       Version >= 8.0
 * @Liberary  LoginWithThirdParty
 * @Project   LoginWithThirdParty
 * @copyright ©2024 Maatify.dev
 * @author    Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since     2024-12-12 4:25 AM
 * @link      https://www.maatify.dev Maatify.com
 * @link      https://github.com/Maatify/LoginWithThirdParty  view project on GitHub
 * @Maatify   LoginWithThirdParty :: GoogleSignIn
 */

namespace Maatify\ThirdPartyLogins\SignInProviders\Google;

use App\Assist\Encryptions\LoginProviderStateEncryption;
use Exception;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Google;
use Maatify\Json\Json;
use Maatify\Logger\Logger;
use Maatify\ThirdPartyLogins\RedirectHandler;
use Maatify\ThirdPartyLogins\SignInProviders\SignInProvidersAssistance;

abstract class GoogleSignIn extends SignInProvidersAssistance
{
    protected string  $clientId = '';
    protected string  $clientSecret = '';
    protected string  $redirectUri = 'http://127.0.0.1/google-callback2.php';
    protected string $redirect_class = RedirectHandler::class;

    private Google $provider;

    public function __construct()
    {
        // Initialize Google Provider
        $this->provider = new Google([
            'clientId'     => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'redirectUri'  => $this->redirectUri,
        ]);
    }

    // Generate Google login URL
    public function getLoginUrl(): string
    {
        $authUrl = $this->provider->getAuthorizationUrl([
            'access_type' => 'offline',   // Request offline access for a refresh token
            'prompt' => 'consent'         // Prompt consent to ensure refresh token
        ]);
        $_SESSION['oauth2state'] = $this->provider->getState(); // Store state for CSRF protection
        return $authUrl;
    }

    public function getLoginUrlWithCustomerId(int $ct_id): string
    {
        $_SESSION['ct_id'] = $ct_id;
        $state = json_encode(['ct_id' => $ct_id]);
        try {
            $state = (new LoginProviderStateEncryption())->Hash($this->generateSecureToken(32));
        } catch (Exception $e) {
            Logger::RecordLog($e, 'LoginProviderStateEncryption_Exception');
            Json::TryAgain( __LINE__);
        }
//        $state = (new LoginProviderStateEncryption())->Hash($state);
        $state = json_encode(['hashed' => true, 'hash' => $state]);
        $state = urlencode($state);
        $authUrl = $this->provider->getAuthorizationUrl([
            'access_type' => 'offline',                         // Request offline access for a refresh token
            'prompt' => 'consent',                              // Prompt consent to ensure refresh token
            'state' => $state,                                  // Encode customer ID in state
        ]);
        $_SESSION['oauth2state'] = $this->provider->getState(); // Store state for CSRF protection
        return $authUrl;
    }

    // Handle Google callback and process tokens

    /**
     * @throws IdentityProviderException
     */
    public function handleCallback(): bool
    {
        $this->validateOauth2state();

        // Fetch the access token using the authorization code
        if (isset($_GET['code'])) {
            $token = $this->provider->getAccessToken('authorization_code', [
                'code' => $_GET['code']
            ]);

            $this->validateCustomer();

            // Fetch user details from Google
            $user = $this->provider->getResourceOwner($token);
            $userData = $user->toArray();
            if(!empty($userData)){
                $this->email = $userData['email'];
                if(empty($this->email)){
                    $this->email = $userData['username'] ?? '';
                }
                $this->id = $userData['sub'];
                $this->name = $userData['name'] ?? '';
                $this->picture = $userData['picture'] ?? '';
                $this->refresh_token = $token->getRefreshToken();
                $this->token_expires = $token->getExpires();

                // Register or find existing user in the database
                $this->findOrCreateUser();
            }

            return true;
        }

        (new $this->redirect_class())->LoginUrlUnauthorized();
        return false;
    }
}