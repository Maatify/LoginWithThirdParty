
/**
 * Created by Maatify.dev
 * User: Maatify.dev
 * Date: 2024-11-08
 * Time: 3:47 PM
 * https://www.Maatify.dev
 */


// ============================================================================================================
// ============================================================================================================
// ============================================ GoogleSignIn Class ============================================
// ============================================================================================================
// ============================================================================================================
namespace App\Assist\SignInProviders;

use App\Assist\AppFunctions;
use App\Assist\Encryptions\LoginProviderStateEncryption;
use App\Assist\RedirectDashboard;
use App\DB\Tables\Customers\CustomerFailedLogin;
use JetBrains\PhpStorm\NoReturn;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Google;

abstract class GoogleSignIn
{
    protected string  $clientId = '';
    protected string  $clientSecret = '';
    protected string  $redirectUri = 'http://127.0.0.1/google-callback.php';

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
        $state = (new LoginProviderStateEncryption())->Hash($state);
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
        if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
            unset($_SESSION['oauth2state']);
            CustomerFailedLogin::obj()->Failed('');
            RedirectDashboard::obj()->LoginUrlUnauthorized();
            //            throw new Exception("Invalid state, possible CSRF attack.");
        }

        // Fetch the access token using the authorization code
        if (isset($_GET['code'])) {
            $token = $this->provider->getAccessToken('authorization_code', [
                'code' => $_GET['code']
            ]);

            $customer_id = 0;
            // Retrieve the customer ID from the state parameter
            $state = json_decode(urldecode($_GET['state']), true);
            if (isset($state['hashed']) && isset($state['hash'])) {
                $retrieve = $state = (new LoginProviderStateEncryption())->DeHashed($state['hash']);
                if(!empty($retrieve)) {
                    $state = json_decode($retrieve, true);
                    if(!empty($state['ct_id']) && !empty($_SESSION['ct_id']) && $_SESSION['ct_id'] == $state['ct_id']) {

                        unset($_SESSION['ct_id']);
                        unset($_SESSION['oauth2state']);

                        $customer_id = (int)$state['ct_id']; // This is the customer ID you passed earlier
                    }
                }
            }

            //            if ($token->getRefreshToken()) {
            //                $_SESSION['refresh_token'] = $token->getRefreshToken();
            //            }
            /**  // =============== Store tokens and expiration in session ===============
            $_SESSION['access_token'] = $token->getToken();
            $_SESSION['token_expires'] = $token->getExpires();
            if ($token->getRefreshToken()) {
            $_SESSION['refresh_token'] = $token->getRefreshToken();
            }
             */

            // Fetch user details from Google
            $user = $this->provider->getResourceOwner($token);
            $userData = $user->toArray();
            if(!empty($userData)){
                // Register or find existing user in the database
                $this->findOrCreateUser($userData, $token->getRefreshToken(), $token->getExpires(), $customer_id);
            }


            /**  // =============== Set session data for the user ===============
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $userData['name'];
            $_SESSION['user_email'] = $userData['email'];
            $_SESSION['logged_in'] = true;
             */

            return true;
        }
        RedirectDashboard::obj()->LoginUrlUnauthorized();
    }

    // Refresh the access token if expired
    /**
     * @throws IdentityProviderException
     */
    public function refreshTokenIfNeeded(): void
    {
        //        if (time() >= $_SESSION['token_expires']) {
        if (isset($_SESSION['refresh_token'])) {
            $newToken = $this->provider->getAccessToken('refresh_token', [
                'refresh_token' => $_SESSION['refresh_token']
            ]);

            // Update session with new token and expiry
            $_SESSION['access_token'] = $newToken->getToken();
            $_SESSION['token_expires'] = $newToken->getExpires();
        } else {
            $this->redirectToLogin();
        }
        //        }
    }

    // Redirect to Google login
    #[NoReturn] public function redirectToLogin(): void
    {
        header("Location: " . $this->getLoginUrl());
        exit();
    }

    // Check if user is logged in
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    // Logout user by clearing session
    public function logout(): void
    {
        session_unset();
        session_destroy();
    }
}

// ================================================================================================
// ================================================================================================
// ============================================ Login ============================================
// ================================================================================================
// ================================================================================================

use App\Assist\SignInProviders\GoogleSignInCustomer;

require __DIR__ . '/../vendor/autoload.php';

session_start();

$googleAuth = new GoogleSignInCustomer();
header("Location: " . $googleAuth->getLoginUrl());
exit();


$client = new Google_Client();
$client->setClientId('apps.googleusercontent.com');
$client->setClientSecret('');
$client->setRedirectUri('http://127.0.0.1/google-callback.php');
$client->addScope("email");
$client->addScope("profile");

// Request offline access to get a refresh token
$client->setAccessType('offline');
$client->setPrompt('consent');

$loginUrl = $client->createAuthUrl();
header("Location: $loginUrl");
exit();

// ================================================================================================
// ================================================================================================
// =========================================== CallBack ===========================================
// ================================================================================================
// ================================================================================================

/**
 * Created by Maatify.dev
 * User: Maatify.dev
 * Date: 2024-10-31
 * Time: 9:03 AM
 * https://www.Maatify.dev
 */

use App\Assist\AppFunctions;
use App\Assist\RedirectDashboard;
use App\Assist\SignInProviders\GoogleSignInCustomer;
use App\DB\Tables\Customers\CustomerFailedLogin;

require __DIR__ . '/../app/loader.php';

$googleAuth = new GoogleSignInCustomer();

try {
    $googleAuth->handleCallback();
} catch (Exception $e) {
    CustomerFailedLogin::obj()->Failed('');
    RedirectDashboard::obj()->LoginUrlUnauthorized();
}
exit();
$client = new Google_Client();
$client->setClientId('apps.googleusercontent.com');
$client->setClientSecret('');
$client->setRedirectUri('http://127.0.0.1/google-callback.php?referrer=' . $referrer);

if (isset($_GET['code'])) {
    // Exchange the authorization code for an access token
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (! isset($token['error'])) {
        // Save the access token to the session
        $client->setAccessToken($token['access_token']);

        // Get user info from Google
        $googleService = new Google_Service_Oauth2($client);
        try {
            $userData = $googleService->userinfo->get();
        } catch (\Google\Service\Exception $e) {
            echo "Error: " . $e->getMessage();
        }

        // Store user info in session or database
        $_SESSION['user_id'] = $userData->id;
        $_SESSION['user_name'] = $userData->name;
        $_SESSION['user_email'] = $userData->email;
        $_SESSION['user_picture'] = $userData->picture;

        // Store the refresh token if available
        if (isset($token['refresh_token'])) {
            $_SESSION['refresh_token'] = $token['refresh_token'];
        } else {
            echo "No refresh token received. Make sure to use 'access_type=offline' and 'prompt=consent'.";
        }

        echo '<pre>';
        print_r($token);
        echo '<br><hr><br>';
        print_r($userData);
        echo '<br><hr><br>';
        print_r($_SESSION);
        echo '</pre>';
        exit();
        // Redirect to the homepage or user dashboard
        header("Location: index.php");
        exit();
    } else {
        // Handle errors, e.g., failed authentication
        echo "Failed to authenticate!";
        exit();
    }
} else {
    echo "No code provided!";
    exit();
}
