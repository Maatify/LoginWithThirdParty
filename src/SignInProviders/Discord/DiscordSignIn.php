<?php
/**
 * @PHP       Version >= 8.2
 * @Liberary  LoginWithThirdParty
 * @Project   LoginWithThirdParty
 * @copyright ©2024 Maatify.dev
 * @author    Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since     2024-12-12 4:25 AM
 * @link      https://www.maatify.dev Maatify.com
 * @link      https://github.com/Maatify/LoginWithThirdParty  view project on GitHub
 * @Maatify   LoginWithThirdParty :: DiscordSignIn
 */

namespace Maatify\ThirdPartyLogins\SignInProviders\Discord;

use App\Assist\Encryptions\LoginProviderStateEncryption;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Maatify\Json\Json;
use Maatify\Logger\Logger;
use Maatify\ThirdPartyLogins\RedirectHandler;
use Maatify\ThirdPartyLogins\SignInProviders\SignInProvidersAssistance;

abstract class DiscordSignIn extends SignInProvidersAssistance
{
    protected string  $clientId = '';
    protected string  $clientSecret = '';
    protected string  $redirectUri = 'http://127.0.0.1/discord-callback.php';
    protected string  $scope = 'identify email';
    protected string $redirect_class = RedirectHandler::class;

    // Generate Google login URL
    public function getLoginUrl(string $state = ''): string
    {
        if(empty($state)){
            try {
                $state = (new LoginProviderStateEncryption())->Hash($this->generateSecureToken(32));
            } catch (Exception $e) {
                Logger::RecordLog($e, 'LoginProviderSecretEncryption_Exception');
                Json::TryAgain( __LINE__);
            }

        }
        // Construct the authorization URL
        $authorizeUrl = "https://discord.com/api/oauth2/authorize?" . http_build_query([
                'response_type' => 'code',
                'client_id' => $this->clientId,
                'redirect_uri' => $this->redirectUri,
                'scope' => $this->scope,
                'state' => $state // Pass the state parameter
            ]);

        $_SESSION['oauth2state'] = $state; // Store state for CSRF protection
        return $authorizeUrl;
    }

    public function getLoginUrlWithCustomerId(int $ct_id): string
    {
        $_SESSION['ct_id'] = $ct_id;
        $state = json_encode(['ct_id' => $ct_id]);
//        $state = (new LoginProviderStateEncryption())->Hash($state);
        try {
            $state = (new LoginProviderStateEncryption())->Hash($state);
        } catch (Exception $e) {
            Logger::RecordLog($e, 'LoginProviderSecretEncryption_Exception');
            Json::TryAgain( __LINE__);
        }
        $state = json_encode(['hashed' => true, 'hash' => $state]);
        $state = urlencode($state);

        return $this->getLoginUrl($state);
    }

    // Handle Google callback and process tokens
    #[NoReturn] public function handleCallback(): bool
    {
        $this->validateOauth2state();

        // Fetch the access token using the authorization code
        if (isset($_GET['code'])) {
            $code = $_GET['code'];

            $this->validateCustomer();

            // Token exchange using cURL
            $url = 'https://discord.com/api/oauth2/token';
            $data = [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->redirectUri,
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded'
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $tokenData = json_decode($response, true);

                if (isset($tokenData['access_token'])) {
                    $accessToken = $tokenData['access_token'];
                    $refreshToken = $tokenData['refresh_token'];
                    $expiryToken = $tokenData['expires_in'];

                    // Retrieve user information using cURL
                    $userUrl = 'https://discord.com/api/users/@me';
                    $ch = curl_init($userUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Authorization: Bearer $accessToken",
                        'Content-Type: application/json'
                    ]);

                    $userResponse = curl_exec($ch);
                    $userHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($userHttpCode === 200) {
                        $userData = json_decode($userResponse, true);

                        // Access user data (e.g., username, profile picture)
                        if(!empty($userData)){
                            if(!empty($userData['avatar'])) {
                                $userData['picture'] = "https://cdn.discordapp.com/avatars/{$userData['id']}/{$userData['avatar']}.png";
                            }else{
                                $userData['picture'] = '';
                            }

                            $this->email = $userData['email'];
                            if(empty($this->email)){
                                $this->email = $userData['username']??'';
                            }
                            $this->id = $userData['id'];
                            $this->name = $userData['name'] ?? '';
                            $this->picture = $userData['picture'];
                            $this->refresh_token = $refreshToken;
                            $this->token_expires = $expiryToken;

                            // Register or find existing user in the database
                            $this->findOrCreateUser();
                        }
                    }else{
                        if(!empty($this->current_ct_id)){
                            (new $this->redirect_class())->assignUrlServiceUnavailable();
                        }else{
                            (new $this->redirect_class())->loginUrlServiceUnavailable();
                        }
                    }
                }
            }else{
                if(!empty($this->current_ct_id)){
                    (new $this->redirect_class())->assignUrlServiceUnavailable();
                }else{
                    (new $this->redirect_class())->loginUrlServiceUnavailable();
                }
            }
        }

        (new $this->redirect_class())->loginUrlUnauthorized();
        return false;
    }


}