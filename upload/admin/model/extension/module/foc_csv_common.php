<?php
/*
  FOC CSV common methods both for exporter/importer
*/
class ModelExtensionModuleFocCsvCommon extends Model {

  const VERSION = '0.0.98';

  public function getVersion () {
    return self::VERSION;
  }

  protected $unwantedTableFields = array(
    'common' => array(
      'sort_order',
      'language_id',
      'date_added',
      'date_modified'
    ),
    'product' => array(
      'location',
      'manufacturer_id',
      'shipping',
      'points',
      'tax_class_id',
      'weight_class_id',
      'length_class_id',
      'subtract',
      'minimum'
    ),
    'product_description' => array(
      'product_id'
    ),
    'product_image' => array(
      'product_id',
      'product_image_id'
    ),
    'category_description' => array(
      'category_id'
    ),
    'category' => array(
      'parent_id',
      'top',
      'column'
    )
  );

  public function __construct ($registry, $type = 'importer') {
    parent::__construct($registry);
    $this->log = new Log('foc_csv_' . $type . '.txt');
    $this->type = $type;
    $this->profiles_code = 'foc_csv_' . $type;
    $this->profiles_key = 'foc_csv_' . $type . '_profiles';

    $this->load->library('FocSimpleTemplater');
  }

  public function install () {
    $this->load->model('setting/setting');
    $this->model_setting_setting->editSetting($this->profiles_code, array($this->profiles_key => array()));
    $this->saveProfiles($this->getDefaultProfiles());
  }

  /*

  */
  public function writeLog ($msg, $group = 'info') {
    switch ($group) {
      case 'error': $msg = '[ERROR] ' . $msg;
                    break;
      case 'warn' : $msg = '[WARN] ' . $msg;
                    break;
      default     : $msg = '[INFO] ' . $msg;
                    break;
    }

    $this->log->write($msg);
  }

  /*
    Key fields
  */
  public function getKeyFields () {
    return array(
      'product:product_id',
      'product:sku',
      'product:model',
      'product_description:name',
      'product:ean',
      'product:mpn',
      'product:jan',
      'product:isbn'
    );
  }

  /*
    Remove unwanted fields from select
  */
  public function filterTableFields ($table, array $fields) {
    if (isset($this->unwantedTableFields[$table])) {
      $fields = array_diff($fields, $this->unwantedTableFields[$table]);
    }

    return array_diff($fields, $this->unwantedTableFields['common']);
  }

  /*
    Generate DB fields list
  */
  public function getDbFields () {
    $tables = array(
      'product',
      'product_description',
      'product_image',
      'manufacturer',
      'category',
      'category_description'
    );

    $result = array();

    foreach ($tables as $table) {
      $data = $this->db->query('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = "' . DB_DATABASE . '" AND TABLE_NAME = "' . DB_PREFIX . $table . '"');
      $result[$table] = array_column($data->rows, 'COLUMN_NAME');
      $result[$table] = $this->filterTableFields($table, $result[$table]);
    }

    return $result;
  }

  public function fillProfileEmptyValues ($profile) {
    return array_replace_recursive($this->getDefaultProfile(), $profile);
  }

  public function getDefaultProfile () {
    return array();
  }

  /*
    Default profiles list
  */
  public function getDefaultProfiles () {
    return array(
      'default' => $this->getDefaultProfile()
    );
  }

  /*
    Load all profiles
  */
  public function loadProfiles () {
    $this->load->model('setting/setting');

    $profiles = json_decode($this->model_setting_setting->getSettingValue($this->profiles_key), true);

    if (count($profiles) === 0) {
      $profiles = $this->getDefaultProfiles();
    }
    else {
      foreach ($profiles as $key => $profile) {
        $profiles[$key] = $this->fillProfileEmptyValues($profile);
      }
    }

    return $profiles;
  }

  /*
    Load profile by name
  */
  public function loadProfile ($name) {
    $profiles = $this->loadProfiles();

    if (isset($profiles[$name])) {
      return $profiles[$name];
    }

    return null;
  }

  /*
    Save profiles list
  */
  public function saveProfiles ($profiles) {
    $this->load->model('setting/setting');

    foreach ($profiles as $key => $profile) {
      $profiles[$key] = $this->fillProfileEmptyValues($profile);
    }

    $this->model_setting_setting->editSettingValue(
      $this->profiles_code,
      $this->profiles_key,
      $profiles
    );
  }

  /*
    Save profile by name
  */
  public function setProfile ($name, $data) {
    $profiles = $this->loadProfiles();
    $profiles[$name] = $data;
    $this->saveProfiles($profiles);
  }

  /*
    FILE MANIPULATION METHODS
  */

  /*
    Return path to file by key
  */
  public function getUploadPath ($key) {
    return DIR_CACHE . $this->profiles_code . DIRECTORY_SEPARATOR . $key . DIRECTORY_SEPARATOR . $this->type . DIRECTORY_SEPARATOR;
  }

  /*
    Prepare import storage
    Returns storage key
  */
  public function prepareUploadPath () {
    $key = md5(rand() . time());
    $path = $this->getUploadPath($key);

    if (is_dir($path)) {
      return $this->prepareUploadPath();
    }

    $this->writeLog('Trying to create import path [' . $path . ']');
    mkdir($path, 0755, true);

    $this->setUploadKey($key);

    return $key;
  }

  public function setUploadKey ($key) {
    $this->uploadKey = $key;
  }

  /* UTILS */

  /*
    Set state data from frontend profile
  */
  public function applyProfile ($profile) {
    $this->language_id = (int) $this->config->get('config_language_id');
    if (isset($profile['languageId'])) {
      $this->language_id = (int) $profile['languageId'];
    }

    $this->store_id = (int) $this->config->get('config_store_id');
    if (isset($profile['storeId'])) {
      $this->store_id = (int) $profile['storeId'];
    }
  }
  /*
    Convert FS path to URL
  */
  public function pathToUrl ($path) {
    return str_replace(DIR_CACHE, HTTPS_CATALOG . 'system/storage/cache/', $path);
  }

  public function basename ($path) {
    $parts = explode(DIRECTORY_SEPARATOR, $path);
    return end($parts);
  }

  /*
    Check if url is url:)
  */
  public function isUrl ($url) {
    return preg_match('/^https?\:\/\//', $url);
  }

  /*
    Get database charset
  */
  public function getDBCharset () {
    return $this->db->query('SELECT @@character_set_database AS `charset`')->row['charset'];
  }

  public function getLanguageCode () {
    $lang = $this->language->get('code');
    return strtolower(substr($lang, 0, 2));
  }

  /*
    Just OC version checker used to provide forward/backward compatibility
  */
  public function isOpencart3 () {
    $digits = explode('.', VERSION);
    // check only first digit - ocStore uses 5 digits versioning system
    $major_version = (int)array_shift($digits);
    return $major_version > 2;
  }

  /*
    Check is this a ocStore
  */
  public function isOcstore () {
    return count(explode('.', VERSION)) > 4;
  }

}