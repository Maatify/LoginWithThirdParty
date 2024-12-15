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
 * @Maatify   LoginWithThirdParty :: RedirectHandler
 */

namespace Maatify\ThirdPartyLogins;

use App\Assist\AppFunctions;
use JetBrains\PhpStorm\NoReturn;

class RedirectHandler
{
    protected static ?self $instance = null;
    protected string $login_url;
    protected string $assign_url;

    private function __construct()
    {
        $dashboard_url = AppFunctions::SiteUrl();
        $this->login_url = $dashboard_url . 'en/redirect-login';
        $this->assign_url = $dashboard_url . 'en/redirect-profile';
    }

    public static function obj(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    #[NoReturn] private function redirectWithCode(string $url, int $code): void
    {
        header("Location: " . $url . '?code=' . $code);
        exit();
    }

    #[NoReturn] private function loginRedirect(int $code): void
    {
        $this->redirectWithCode($this->login_url, $code);
    }

    #[NoReturn] private function assignRedirect(int $code): void
    {
        $this->redirectWithCode($this->assign_url, $code);
    }

    #[NoReturn] public function LoginUrlSuccess(): void
    {
        $this->loginRedirect(200);
    }

    #[NoReturn] public function LoginUrlReLogin(): void
    {
        $this->loginRedirect(405000);
    }

    #[NoReturn] public function LoginUrlNotFound(): void
    {
        $this->loginRedirect(404);
    }

    #[NoReturn] public function LoginUrlBlockIp(): void
    {
        $this->loginRedirect(403);
    }

    #[NoReturn] public function LoginUrlNotAllowed(): void
    {
        $this->loginRedirect(405);
    }

    #[NoReturn] public function LoginUrlSuspendedAccount(): void
    {
        $this->loginRedirect(403022);
    }

    #[NoReturn] public function LoginUrlUnauthorized(): void
    {
        $this->loginRedirect(401);
    }

    #[NoReturn] public function LoginUrlServiceUnavailable(): void
    {
        $this->loginRedirect(503);
    }

    #[NoReturn] public function AssignUrlSuccess(): void
    {
        $this->assignRedirect(200);
    }

    #[NoReturn] public function AssignUrlEmailUsed(): void
    {
        $this->assignRedirect(8000);
    }

    #[NoReturn] public function AssignUrlProviderInUse(): void
    {
        $this->assignRedirect(3000);
    }

    #[NoReturn] public function AssignUrlServiceUnavailable(): void
    {
        $this->assignRedirect(503);
    }
}