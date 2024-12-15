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
 * @Maatify   LoginWithThirdParty :: LoginProviderName
 */

namespace Maatify\ThirdPartyLogins\LoginProvider;

use App\DB\DBS\DbConnector;

class LoginProviderName extends DbConnector
{
    public const TABLE_NAME                 = 'login_provider_ct_name';
    public const TABLE_ALIAS                = 'provider';
    public const IDENTIFY_TABLE_ID_COL_NAME = LoginProvider::IDENTIFY_TABLE_ID_COL_NAME;
    public const LOGGER_TYPE                = self::TABLE_NAME;
    public const LOGGER_SUB_TYPE            = 'name';
    public const COLS                       =
        [
            self::IDENTIFY_TABLE_ID_COL_NAME          => 1,
            self::LANGUAGE_IDENTIFY_TABLE_ID_COL_NAME => 1,
            'name'                                    => 0,
        ];
    public const IMAGE_FOLDER               = self::TABLE_NAME;

    protected string $tableName = self::TABLE_NAME;
    protected string $tableAlias = self::TABLE_ALIAS;
    protected string $identify_table_id_col_name = self::IDENTIFY_TABLE_ID_COL_NAME;
    protected string $logger_sub_type = self::LOGGER_SUB_TYPE;
    protected array $cols = self::COLS;
    protected string $image_folder = self::IMAGE_FOLDER;

    private static self $instance;

    public static function obj(): self
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}