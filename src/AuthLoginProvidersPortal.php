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
 * @Maatify   LoginWithThirdParty :: AuthLoginProvidersPortal
 */

namespace Maatify\ThirdPartyLogins;

use Maatify\Json\Json;
use Maatify\Portal\DbHandler\ParentClassHandler;
use Maatify\PostValidatorV2\ValidatorConstantsTypes;
use Maatify\PostValidatorV2\ValidatorConstantsValidators;

class AuthLoginProvidersPortal extends ParentClassHandler
{
    public const IDENTIFY_TABLE_ID_COL_NAME = AuthLoginProviders::IDENTIFY_TABLE_ID_COL_NAME;
    public const ENTITY_COL_NAME            = AuthLoginProviders::ENTITY_COL_NAME;
    public const TABLE_NAME                 = AuthLoginProviders::TABLE_NAME;
    public const TABLE_ALIAS                = AuthLoginProviders::TABLE_ALIAS;
    public const LOGGER_TYPE                = AuthLoginProviders::LOGGER_TYPE;
    public const LOGGER_SUB_TYPE            = AuthLoginProviders::LOGGER_SUB_TYPE;
    public const COLS                       = AuthLoginProviders::COLS;
    public const IMAGE_FOLDER               = self::TABLE_NAME;

    protected string $identify_table_id_col_name = self::IDENTIFY_TABLE_ID_COL_NAME;
    protected string $entity_name_col_name = self::ENTITY_COL_NAME;
    protected string $tableName = self::TABLE_NAME;
    protected string $tableAlias = self::TABLE_ALIAS;
    protected string $logger_type = self::LOGGER_TYPE;
    protected string $logger_sub_type = self::LOGGER_SUB_TYPE;
    protected array $cols = self::COLS;
    protected string $image_folder = self::IMAGE_FOLDER;

    // to use in list of AllPaginationThisTableFilter()
    protected array $inner_language_tables = [];

    // to use in list of source and destination rows with names
    protected string $inner_language_name_class = '';

    protected array $cols_to_filter = [
        [self::IDENTIFY_TABLE_ID_COL_NAME, ValidatorConstantsTypes::Int, ValidatorConstantsValidators::Optional],
        [self::ENTITY_COL_NAME, ValidatorConstantsTypes::Int, ValidatorConstantsValidators::Optional],
    ];

    // to use in add if child classes no have language_id
    protected array $child_classes = [];

    // to use in add if child classes have language_id
    protected array $child_classe_languages = [];
    private static self $instance;

    public static function obj(): self
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

//    public function CustomerProviders(): void
//    {
//        (new Customers())->ValidatePostedTableId();
//        $this->AllPaginationThisTableFilter();
//    }

    public function AllPaginationThisTableFilter(string $order_with_asc_desc = ''): void
    {
        $cols = "`$this->identify_table_id_col_name`, `$this->entity_name_col_name`, `provider`, `provider_user_id`, `email`, `profile_picture`, `linked_time`";
        Json::Success(
            $this->ArrayPaginationThisTableFilter($this->tableName, $cols, order_with_asc_desc: $order_with_asc_desc),
            line: $this->class_name . __LINE__
        );
    }
}