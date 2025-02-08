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
 * @Maatify   LoginWithThirdParty :: LoginProviderTypes
 */

namespace Maatify\ThirdPartyLogins;

use App\DB\DBS\DbConnector;
use Maatify\Json\Json;
use Maatify\PostValidatorV2\ValidatorConstantsTypes;

class LoginProviderTypes extends DbConnector
{
    public const TABLE_NAME                 = 'login_provider_types';
    public const TABLE_ALIAS                = '';
    public const IDENTIFY_TABLE_ID_COL_NAME = 'types_id';
    public const LOGGER_TYPE                = self::TABLE_NAME;
    public const LOGGER_SUB_TYPE            = '';
    public const COLS                       = [
        self::IDENTIFY_TABLE_ID_COL_NAME => 1,
        'provider'                       => 0,
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

    public function jsonProviders(): void
    {
        $providers = $this->RowsThisTable('provider');

        // Extracting the 'provider' values into a simple array
        $providers = array_map(function ($item) {
            return $item['provider'];
        }, $providers);

        Json::Success(
            $providers
        );
    }

    public function jsonValidatePostProvider(): string
    {
        $provider = $this->postValidator->Require('provider', ValidatorConstantsTypes::Col_Name, $this->class_name . __LINE__);
        if (! $this->RowIsExistThisTable("`provider` = ? ", [strtolower($provider)])) {
            Json::Incorrect("provider", 'Provider Is Not Exist', $this->class_name . __LINE__);
        }

        return $provider;
    }

}