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
 * @Maatify   LoginWithThirdParty :: GoogleSignInCustomer
 */

namespace Maatify\ThirdPartyLogins\SignInProviders\Google;


use App\Assist\Encryptions\LoginProviderSecretEncryption;
use Maatify\ThirdPartyLogins\LoginProvider\LoginProvider;
use Maatify\ThirdPartyLogins\AuthLoginProviders;
use Maatify\ThirdPartyLogins\RedirectHandler;

class GoogleSignInCustomer extends GoogleSignIn
{
    protected string $clientId = '';
    protected string $clientSecret = '';
    protected string $redirectUri = '';
    protected string $redirect_class = RedirectHandler::class;

    protected string $provider_name = AuthLoginProviders::GOOGLE;

    public function __construct()
    {
        $row = LoginProvider::obj()->getByProvider($this->provider_name);
        if(empty($row)){
            (new $this->redirect_class())->LoginUrlServiceUnavailable();
        }
        $this->clientId = $row['client_id'];
        $this->clientSecret =  (new LoginProviderSecretEncryption())->DeHashed($row['client_secret']);
        $this->redirectUri = $row['redirect_url'];
        parent::__construct();
    }
}