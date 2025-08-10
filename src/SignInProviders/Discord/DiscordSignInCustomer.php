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
 * @Maatify   LoginWithThirdParty :: DiscordSignInCustomer
 */

namespace Maatify\ThirdPartyLogins\SignInProviders\Discord;

use App\Assist\Encryptions\LoginProviderSecretEncryption;
use Maatify\ThirdPartyLogins\LoginProvider\LoginProvider;
use Maatify\ThirdPartyLogins\AuthLoginProviders;
use Maatify\ThirdPartyLogins\RedirectHandler;

class DiscordSignInCustomer extends DiscordSignIn
{

    protected string  $clientId = '';
    protected string  $clientSecret = '';
    protected string  $redirectUri = '';
    protected string  $scope = 'identify email';
    protected string $redirect_class = RedirectHandler::class;

    protected string $provider_name = AuthLoginProviders::DISCORD;

    public function __construct()
    {
        $row = LoginProvider::obj()->getByProvider($this->provider_name);
        if(empty($row)){
            (new $this->redirect_class())->loginUrlServiceUnavailable();
        }
        $this->clientId = $row['client_id'];
        $this->clientSecret =  (new LoginProviderSecretEncryption())->DeHashed($row['client_secret']);
        $this->redirectUri = $row['redirect_url'];
    }
}