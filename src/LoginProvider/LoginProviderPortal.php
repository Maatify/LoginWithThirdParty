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
 * @Maatify   LoginWithThirdParty :: LoginProviderPortal
 */

namespace Maatify\ThirdPartyLogins\LoginProvider;

use App\Assist\Encryptions\LoginProviderSecretEncryption;
use Exception;
use Maatify\Json\Json;
use Maatify\LanguagePortalHandler\DBHandler\ParentClassHandler;
use Maatify\Logger\Logger;
use Maatify\PostValidatorV2\ValidatorConstantsTypes;
use Maatify\PostValidatorV2\ValidatorConstantsValidators;
use Maatify\ThirdPartyLogins\LoginProviderTypes;

class LoginProviderPortal extends ParentClassHandler
{
    public const IDENTIFY_TABLE_ID_COL_NAME = LoginProvider::IDENTIFY_TABLE_ID_COL_NAME;
    public const TABLE_NAME                 = LoginProvider::TABLE_NAME;
    public const TABLE_ALIAS                = LoginProvider::TABLE_ALIAS;
    public const LOGGER_TYPE                = LoginProvider::LOGGER_TYPE;
    public const LOGGER_SUB_TYPE            = LoginProvider::LOGGER_SUB_TYPE;
    public const COLS                       = LoginProvider::COLS;
    public const IMAGE_FOLDER               = self::TABLE_NAME;

    protected string $identify_table_id_col_name = self::IDENTIFY_TABLE_ID_COL_NAME;
    protected string $tableName = self::TABLE_NAME;
    protected string $tableAlias = self::TABLE_ALIAS;
    protected string $logger_type = self::LOGGER_TYPE;
    protected string $logger_sub_type = self::LOGGER_SUB_TYPE;
    protected array $cols = self::COLS;
    protected string $image_folder = self::IMAGE_FOLDER;

    // to use in list of AllPaginationThisTableFilter()
    protected array $inner_language_tables = [LoginProviderNamePortal::class];

    // to use in list of source and destination rows with names
    protected string $inner_language_name_class = '';

    protected array $cols_to_add = [
        ['provider', ValidatorConstantsTypes::Col_Name, ValidatorConstantsValidators::Require],
        ['status', ValidatorConstantsTypes::Bool, ValidatorConstantsValidators::Optional],
        ['client_id', ValidatorConstantsTypes::String, ValidatorConstantsValidators::Require],
        ['client_secret', ValidatorConstantsTypes::DeviceId, ValidatorConstantsValidators::Require],
        ['redirect_url', ValidatorConstantsTypes::String, ValidatorConstantsValidators::Require],
        ['login_url', ValidatorConstantsTypes::String, ValidatorConstantsValidators::Require],
        ['assign_url', ValidatorConstantsTypes::String, ValidatorConstantsValidators::Require],
    ];

    protected array $cols_to_edit = [

        ['provider', ValidatorConstantsTypes::Col_Name, ValidatorConstantsValidators::Optional],
        ['status', ValidatorConstantsTypes::Bool, ValidatorConstantsValidators::Optional],
        ['client_id', ValidatorConstantsTypes::String, ValidatorConstantsValidators::Optional],
        //        ['client_secret', ValidatorConstantsTypes::String, ValidatorConstantsValidators::Optional],
        ['redirect_url', ValidatorConstantsTypes::String, ValidatorConstantsValidators::Optional],
        ['login_url', ValidatorConstantsTypes::String, ValidatorConstantsValidators::Optional],
        ['assign_url', ValidatorConstantsTypes::String, ValidatorConstantsValidators::Optional],
    ];

    protected array $cols_to_filter = [
        [self::IDENTIFY_TABLE_ID_COL_NAME, ValidatorConstantsTypes::Int, ValidatorConstantsValidators::Optional],
        ['provider', ValidatorConstantsTypes::Col_Name, ValidatorConstantsValidators::Optional],
        ['status', ValidatorConstantsTypes::Status, ValidatorConstantsValidators::Optional],
    ];

    // to use in add if child classes no have language_id
    protected array $child_classes = [];

    // to use in add if child classes have language_id
    protected array $child_classe_languages = [LoginProviderNamePortal::class];
    private static self $instance;

    public static function obj(): self
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function allPaginationThisTableFilter(string $order_with_asc_desc = ''): void
    {
        if (! empty($_POST['provider'])) {
            LoginProviderTypes::obj()->jsonValidatePostProvider();
        }

        [$tables, $cols] = $this->HandleThisTableJoins();
        $result = $this->ArrayPaginationThisTableFilter($tables, $cols, order_with_asc_desc: $order_with_asc_desc);
        if (! empty($result['data'])) {
            $result['data'] = array_map(function ($item) {
                if (! empty($item['client_secret'])) {
                    $item['client_secret'] = '{{Hidden For Security}}';
                } else {
                    $item['client_secret'] = '';
                }

                return $item;
            }, $result['data']);
        }

        Json::Success(
            $result,
            line: $this->class_name . __LINE__
        );

        parent::AllPaginationThisTableFilter($order_with_asc_desc);
    }

    public function record(): void
    {
        LoginProviderTypes::obj()->jsonValidatePostProvider();
        $this->providerExistsJson();
        if (! empty($_POST['client_secret'])) {
            try {
                $_POST['client_secret'] = (new LoginProviderSecretEncryption())->Hash($_POST['client_secret']);
            } catch (Exception $e) {
                Logger::RecordLog($e, 'LoginProviderSecretEncryption_Exception');
                Json::TryAgain($this->class_name . __LINE__);
            }
        }
        parent::Record();
    }

    private function providerExistsJson(): void
    {
        $provider = $this->postValidator->Require('provider', ValidatorConstantsTypes::Col_Name, $this->class_name . __LINE__);
        if ($this->RowIsExistThisTable('`provider` = ? ', [$provider])) {
            Json::Exist('provider', 'Provider already exists', $this->class_name . __LINE__);
        }
    }

    public function updateByPostedId(): void
    {
        $this->ValidatePostedTableId();
        if (! empty($_POST['provider']) && $_POST['provider'] != $this->current_row['provider']) {
            LoginProviderTypes::obj()->jsonValidatePostProvider();
            $this->providerExistsJson();
        }
        if (! empty($_POST['client_secret'])) {
            $client_secret = $this->postValidator->Require('client_secret', ValidatorConstantsTypes::DeviceId, $this->class_name . __LINE__);
            try {
                $_POST['client_secret'] = (new LoginProviderSecretEncryption())->Hash($client_secret);
            } catch (Exception $e) {
                Logger::RecordLog($e, 'LoginProviderSecretEncryption_Exception');
                Json::TryAgain($this->class_name . __LINE__);
            }
        }
        parent::UpdateByPostedId();
    }

    public function updateClientSecret(): void
    {
        $this->ValidatePostedTableId();
        $client_secret = $this->postValidator->Require('client_secret', ValidatorConstantsTypes::DeviceId, $this->class_name . __LINE__);
        try {
            $client_secret = (new LoginProviderSecretEncryption())->Hash($client_secret);
        } catch (Exception $e) {
            Logger::RecordLog($e, 'LoginProviderSecretEncryption_Exception');
            Json::TryAgain($this->class_name . __LINE__);
        }

        $this->Edit(
            [
                'client_secret' => $client_secret,
            ], "`$this->identify_table_id_col_name` = ? ", [$this->row_id]
        );
        Json::Success(line: $this->class_name . __LINE__);
    }
}