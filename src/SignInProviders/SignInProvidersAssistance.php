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
 * @Maatify   LoginWithThirdParty :: SignInProvidersAssistance
 */

namespace Maatify\ThirdPartyLogins\SignInProviders;

use App\Assist\Encryptions\LoginProviderStateEncryption;
use JetBrains\PhpStorm\NoReturn;
use Maatify\ThirdPartyLogins\AuthLoginProviders;
use Maatify\ThirdPartyLogins\RedirectHandler;

abstract class SignInProvidersAssistance
{
    protected string $provider_name;
    protected string $id;
    protected string $email;
    protected string $name;
    protected string $picture;
    protected string $refresh_token;
    protected string $token_expires;
    protected int $current_ct_id = 0;
    protected string $failed_login_class = '';

    // this to change it from child class it's can be dashboard which extend RedirectHandler
    protected string $redirect_class = RedirectHandler::class;


    // this to change it from child class it's can be Admin Class with methods emailIsExist, RegisterByProvider and LoginByProvider
    protected string $entity_class = Entity::class;


    // this to change it from child class it's can be adminAuthLoginProvider which extend AuthLoginProviders
    protected string $auth_login_provider_class = AuthLoginProviders::class;

    protected function generateSecureToken($length = 32): string
    {
        // Validate input length
        if ($length < 1) {
            $length = 32; // Fallback to a default length if invalid
        }

        // Try to generate a secure random token
        if (function_exists('random_bytes')) {
            $token = random_bytes($length);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $token = openssl_random_pseudo_bytes($length);
        } else {
            // Fallback to a less secure method if no secure randomness is available (should be avoided if possible)
            $token = '';
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $max = strlen($characters) - 1;
            for ($i = 0; $i < $length; $i++) {
                $token .= $characters[random_int(0, $max)];
            }
        }

        // Return token as a readable string
        return bin2hex($token);
    }

    protected function validateOauth2state(): void
    {
        if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
            unset($_SESSION['oauth2state']);
            if(!empty($this->failed_login_class)){
//                $this->failed_login_class::obj()->Failed('');
                (new $this->failed_login_class())->Failed('');
            }
            (new $this->redirect_class())->LoginUrlUnauthorized();
        }
    }

    protected function validateCustomer(): void
    {
        // Retrieve the customer ID from the state parameter
        $state = json_decode(urldecode($_GET['state']), true);
        if (isset($state['hashed']) && isset($state['hash'])) {
            $retrieve = (new LoginProviderStateEncryption())->DeHashed($state['hash']);
            if(!empty($retrieve)) {
                $state = json_decode($retrieve, true);
                if(!empty($state['ct_id']) && !empty($_SESSION['ct_id']) && $_SESSION['ct_id'] == $state['ct_id']) {

                    unset($_SESSION['ct_id']);
                    if(!empty($_SESSION['oauth2state'])) {
                        unset($_SESSION['oauth2state']);
                    }

                    $this->current_ct_id = (int)$state['ct_id']; // This is the customer ID you passed earlier
                }
            }
        }
    }

    public function providerExist(int $current_ct_id): void
    {
        (new $this->auth_login_provider_class($this->redirect_class, $this->entity_class))
            ->isCustomerProviderExistPage($current_ct_id, $this->provider_name);
    }

    // Register new user or retrieve existing user ID
    #[NoReturn] protected function findOrCreateUser(): void
    {
        $auth = new $this->auth_login_provider_class($this->redirect_class, $this->entity_class);
        if(!empty($this->current_ct_id)) {
            $auth->validateAssign($this->id, $this->provider_name, $this->email, $this->name, $this->picture, $this->refresh_token, $this->token_expires, $this->current_ct_id);
        }else{
            $auth->validateLogin($this->id, $this->provider_name, $this->email, $this->name, $this->picture, $this->refresh_token, $this->token_expires);
        }
    }
}