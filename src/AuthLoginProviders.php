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
 * @Maatify   LoginWithThirdParty :: AuthLoginProviders
 */

namespace Maatify\ThirdPartyLogins;

use App\Assist\AppFunctions;
use App\DB\DBS\DbConnector;
use JetBrains\PhpStorm\NoReturn;
use Maatify\Functions\GeneralFunctions;
use Maatify\Json\Json;
use Maatify\ThirdPartyLogins\LoginProvider\LoginProvider;

class AuthLoginProviders extends DbConnector
{
    public const TABLE_NAME                 = 'ct_auth_providers';
    public const TABLE_ALIAS                = '';
    public const IDENTIFY_TABLE_ID_COL_NAME = 'auth_id';
    public const ENTITY_COL_NAME            = 'ct_id';
    public const LOGGER_TYPE                = self::TABLE_NAME;
    public const LOGGER_SUB_TYPE            = '';
    public const COLS                       = [
        self::IDENTIFY_TABLE_ID_COL_NAME => 1,
        self::ENTITY_COL_NAME            => 1,
        'provider'                       => 0,
        'provider_user_id '              => 0,
        'email'                          => 0,
        'profile_picture'                => 0,
        'linked_time'                    => 0,
        'refresh_token'                  => 0,
        'expires_at'                     => 0,
        'token_expires'                  => 0,
    ];

    protected string $tableName = self::TABLE_NAME;
    protected string $tableAlias = self::TABLE_ALIAS;
    protected string $identify_table_id_col_name = self::IDENTIFY_TABLE_ID_COL_NAME;
    protected string $logger_type = self::LOGGER_TYPE;
    protected string $logger_sub_type = self::LOGGER_SUB_TYPE;
    protected array $cols = self::COLS;

    const GOOGLE   = 'google';
    const DISCORD  = 'discord';
    const FACEBOOK = 'facebook';
    const TWITTER  = 'twitter';
    protected string $redirect_class = RedirectHandler::class;

    // this can be admin, customer etc
    protected string $entity_class;
    const PROVIDER_LIST = [
        self::GOOGLE,
        self::DISCORD,
        self::FACEBOOK,
        self::TWITTER,
    ];

    private string $entity_col_name = self::ENTITY_COL_NAME;

    public function __construct(string $redirect_class, string $entity_class)
    {
        parent::__construct();
        $this->redirect_class = $redirect_class;
        $this->entity_class = $entity_class;
    }

    #[NoReturn] public function validateLogin(string $provider_user_id, string $provider, string $email, string $name, string $picture, string $refresh_token, string $token_expires): void
    {
        $entity_class_controller = new $this->entity_class();
        $exist = $this->RowThisTable("`$this->identify_table_id_col_name`, `$this->entity_col_name`, `profile_picture`",
            "`provider` = ? AND `provider_user_id` = ? GROUP BY `$this->identify_table_id_col_name`, `$this->entity_col_name`, `profile_picture` ",
            [$provider, $provider_user_id]);

        if ($exist) {
            $ct_id = $exist[$this->entity_col_name];
            if (empty($exist['profile_picture']) && ! empty($picture)) {
                $this->Edit([
                    'profile_picture' => $picture,
                ], "`$this->identify_table_id_col_name` = ?", [$exist[$this->identify_table_id_col_name]]);
            }
        } else {
            $ct_id = 0;
            if ($entity_class_controller->emailIsExist($email)) {
                (new $this->redirect_class())->LoginUrlNotAllowed();
            } else {
                $ct_id = $entity_class_controller->RegisterByProvider($provider, $email, $name, $_SESSION['referrer'] ?? '');
                $this->Add(
                    [
                        $this->entity_col_name => $ct_id,
                        'provider'             => $provider,
                        'provider_user_id'     => $provider_user_id,
                        'email'                => $email,
                        'profile_picture'      => $picture,
                        'linked_time'          => AppFunctions::CurrentDateTime(),
                        'refresh_token'        => $refresh_token,
                        'expires_at'           => date('Y-m-d H:i:s', $token_expires),
                        'token_expires'        => $token_expires,
                    ]
                );
            }
        }

        $_SESSION['login_provider_email'] = $email;
        $entity_class_controller->LoginByProvider($ct_id, $provider);
    }

    #[NoReturn] public function validateAssign(string $provider_user_id, string $provider, string $email, string $name, string $picture, string $refresh_token, string $token_expires, int $current_ct_id): void
    {
        if (empty($current_ct_id)) {
            (new $this->redirect_class())->LoginUrlReLogin();
        } else {
            $this->isCustomerProviderExistPage($current_ct_id, $provider);
            $email_in_use = $this->ColThisTable($this->entity_col_name, "`email` = ? AND `provider` = ?", [$email, $provider]);
            if ($email_in_use) {
                (new $this->redirect_class())->AssignUrlEmailUsed();
            } else {
                $this->Add(
                    [
                        $this->entity_col_name => $current_ct_id,
                        'provider'             => $provider,
                        'provider_user_id'     => $provider_user_id,
                        'email'                => $email,
                        'profile_picture'      => $picture,
                        'linked_time'          => AppFunctions::CurrentDateTime(),
                        'refresh_token'        => $refresh_token,
                        'expires_at'           => date('Y-m-d H:i:s', $token_expires),
                        'token_expires'        => $token_expires,
                    ]
                );
                (new $this->redirect_class())->AssignUrlSuccess();
            }
        }
    }

    public function isCustomerProviderExistPage(int $current_ct_id, string $provider): void
    {
        if ($this->isEntityProviderExist($current_ct_id, $provider)) {
            (new $this->redirect_class())->AssignUrlProviderInUse();
        }
    }

    public function isCustomerProviderExistJson(int $current_ct_id, string $provider): void
    {
        if ($this->isEntityProviderExist($current_ct_id, $provider)) {
            Json::Exist('provider', 'provider already exist with your account', $this->class_name . __LINE__);
        }
    }

    private function isEntityProviderExist(int $current_ct_id, string $provider): int
    {
        return (int)$this->ColThisTable($this->entity_col_name, "`$this->entity_col_name` = ? AND `provider` = ?", [$current_ct_id, $provider]);
    }

    public function myList(int $entity_id): void
    {
        $dataList = $this->RowsThisTable(
            "`provider`, `provider_user_id`, `email`, `profile_picture`, `linked_time`",
            "`$this->entity_col_name` = ? ",
            [$entity_id]);
        // Result array
        $indexedProviders = [];
        $provider_list = LoginProvider::obj()->listActiveProviders();

        // Check if dataList is empty
        if (empty($dataList)) {
            // Assign all providers as empty arrays if dataList is empty
            foreach ($provider_list as $provider) {
                $indexedProviders[$provider] = [];
            }
        } else {
            // Populate the $indexedProviders array
            foreach ($provider_list as $provider) {
                $found = false;
                foreach ($dataList as $index => $data) {
                    if ($data['provider'] === $provider) {
                        // Mask the email address
                        $data['email'] = GeneralFunctions::maskEmail($data['email']);
                        $indexedProviders[$provider] = $data;
                        $found = true;
                        break; // Stop searching once a match is found
                    }
                }
                // If provider is not found, assign an empty array
                if (! $found) {
                    $indexedProviders[$provider] = [];
                }
            }
        }

        Json::Success(
            $indexedProviders
        );
    }

}