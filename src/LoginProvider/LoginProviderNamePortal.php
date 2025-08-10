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
 * @Maatify   LoginWithThirdParty :: LoginProviderNamePortal
 */

namespace Maatify\ThirdPartyLogins\LoginProvider;

use Maatify\LanguagePortalHandler\DBHandler\SubClassLanguageHandler;
use Maatify\PostValidatorV2\ValidatorConstantsTypes;

class LoginProviderNamePortal extends SubClassLanguageHandler
{
    public const TABLE_NAME                 = LoginProviderName::TABLE_NAME;
    public const TABLE_ALIAS                = LoginProviderName::TABLE_ALIAS;
    public const IDENTIFY_TABLE_ID_COL_NAME = LoginProviderName::IDENTIFY_TABLE_ID_COL_NAME;
    public const LOGGER_TYPE                = LoginProviderName::LOGGER_TYPE;
    public const LOGGER_SUB_TYPE            = LoginProviderName::LOGGER_SUB_TYPE;
    public const COLS                       = LoginProviderName::COLS;
    public const IMAGE_FOLDER               = LoginProviderName::TABLE_NAME;

    protected string $tableName = self::TABLE_NAME;
    protected string $tableAlias = self::TABLE_ALIAS;
    protected string $identify_table_id_col_name = self::IDENTIFY_TABLE_ID_COL_NAME;
    protected string $logger_type = self::LOGGER_TYPE;
    protected string $logger_sub_type = self::LOGGER_SUB_TYPE;
    protected string $image_folder = self::IMAGE_FOLDER;

    protected array $cols = self::COLS;

    protected array $cols_to_add = [
        [ValidatorConstantsTypes::Name, ValidatorConstantsTypes::Name, ''],
    ];

    protected string $parent_class = LoginProviderPortal::class;

    private static self $instance;

    public static function obj(): self
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}

