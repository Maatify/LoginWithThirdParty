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
 * @Maatify   LoginWithThirdParty :: LoginProvider
 */

namespace Maatify\ThirdPartyLogins\LoginProvider;

use App\DB\DBS\DbConnector;
use Maatify\Json\Json;

class LoginProvider extends DbConnector
{
    public const TABLE_NAME                 = 'login_provider_ct';
    public const TABLE_ALIAS                = '';
    public const IDENTIFY_TABLE_ID_COL_NAME = 'provider_id';
    public const LOGGER_TYPE                = self::TABLE_NAME;
    public const LOGGER_SUB_TYPE            = '';
    public const COLS                       = [
        self::IDENTIFY_TABLE_ID_COL_NAME => 1,
        'provider'                       => 0,
        'status'                         => 1,
        'icon'                           => 0,
        'client_id'                      => 0,
        'client_secret'                  => 0,
        'redirect_url'                   => 0,
        'login_url'                      => 0,
        'assign_url'                     => 0,
    ];

    protected string $tableName = self::TABLE_NAME;
    protected string $tableAlias = self::TABLE_ALIAS;
    protected string $identify_table_id_col_name = self::IDENTIFY_TABLE_ID_COL_NAME;
    protected string $logger_type = self::LOGGER_TYPE;
    protected string $logger_sub_type = self::LOGGER_SUB_TYPE;
    protected array $cols = self::COLS;

    private static self $instance;

    public static function obj(): self
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getByProvider(string $provider): array
    {
        return $this->RowThisTable('*', 'provider = ? AND `status` = ? ', [$provider, 1]);
    }

    private array $providers = [];
    public function listActiveProviders(): array
    {
        if(empty($this->providers)) {
            $providers = $this->RowsThisTable('provider', '`status` = ?', [1]);

            // Extracting the 'provider' values into a simple array
            $this->providers = array_map(function ($item) {
                return $item['provider'];
            }, $providers);
        }
        return $this->providers;
    }

    public function listActiveProvidersJson(): void
    {
        $this->listActiveProviders();
        Json::Success(
            $this->providers
        );
    }

    public function signInActiveProviderUrl(string $provider): string
    {
        return $this->ColThisTable('login_url', '`provider` = ? AND `status` = ?', [$provider, 1]);
    }

    public function assignActiveProviderUrl(string $provider): string
    {
        return $this->ColThisTable('assign_url', '`provider` = ? AND `status` = ?', [$provider, 1]);
    }

}