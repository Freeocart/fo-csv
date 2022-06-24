<?php
/*
  FOC CSV common methods both for exporter/importer
*/
class ModelExtensionModuleFocCsvCommon extends Model {

  const VERSION = '1.0.4';

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
    'product_special' => array(
      'product_special_id',
      'customer_group_id',
      'product_id',
      'priority'
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


  // db encoding -> iconv encoding
  protected $charsetMap = array(
    'armscii8' => 'ARMSCII-8',
    'ascii' => 'ASCII',
    'big5' => 'BIG-5',
    'binary'=> null, // Not sure what is it
    'cp1250'=> 'CP1250',
    'cp1251'=> 'CP1251',
    'cp1256'=> 'CP1256',
    'cp1257'=> 'CP1257',
    'cp850'=> 'CP850',
    'cp852'=> 'CP852',
    'cp866'=> 'CP866',
    'cp932'=> 'CP932',
    'dec8'=> 'ISO-8859-1',
    'eucjpms'=> 'EUC-JISX0213',
    'euckr'=> 'EUC-KR',
    'gb18030'=> 'GB18030',
    'gb2312'=> 'GB_2312-80',
    'gbk'=> 'GBK',
    'geostd8'=> 'GEORGIAN-PS',
    'greek'=> 'GREEK',
    'hebrew'=> 'HEBREW',
    'hp8'=> 'R8',
    'keybcs2'=> null, // Not sure here
    'koi8r'=> 'KOI8-R',
    'koi8u'=> 'KOI8-U',
    'latin1'=> 'LATIN1',
    'latin2'=> 'LATIN2',
    'latin5'=> 'LATIN5',
    'latin7'=> 'LATIN7',
    'macce'=> 'MACCENTRALEUROPE',
    'macroman'=> 'MACROMANIA',
    'sjis'=> 'SJIS',
    'swe7'=> null, // Not sure here, please make PR if there is error
    'tis620'=> 'TIS-620',
    'ucs2'=> 'UCS-2',
    'ujis'=> 'EUC-JP',
    'utf16'=> 'UTF-16',
    'utf16le'=> 'UTF-16LE',
    'utf32'=> 'UTF-32',
    'utf8mb3'=> 'UTF-8',
    'utf8mb4'=> 'UTF-8',
  );

  public function __construct ($registry) {
    parent::__construct($registry);
    $this->scanOpencartVersion();
    $this->log = new Log('foc_csv_' . $this->type . '.txt');
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
      'product_special',
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
    if (isset($profile['language'])) {
      $this->language_id = (int) $profile['language'];
    }

    $this->store_id = $this->config->get('config_store_id');
    if (isset($profile['store'])) {
      $this->store_id = $profile['store'];
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

  public function dbToIconvCharset ($charset) {
    if (isset($this->charsetMap[$charset])) {
      return $this->charsetMap[$charset];
    }
    return $charset;
  }

  public function getLanguageCode () {
    $lang = $this->language->get('code');
    return strtolower(substr($lang, 0, 2));
  }

  /*
    Scan version
  */
  public function scanOpencartVersion () {
    $digits = explode('.', VERSION);

    // ocstore uses 5-digits versions
    if (count($digits) > 4) {
      $this->is_ocstore = true;
      array_pop($digits);
    }

    $this->normalized_version = intval(implode('', $digits));
  }

  /*
    Version checkers
  */
  public function getOpencartMajorVersion () {
    return floor($this->normalized_version / 1000);
  }
  public function isOpencart15 () {
    return $this->normalized_version < 2000;
  }

  public function isOpencart2 () {
    return $this->normalized_version >= 2000 && $this->normalized_version < 2300;
  }
  public function isOpencart23 () {
    return $this->normalized_version >= 2300 && $this->normalized_version < 3000;
  }

  public function isOpencart3 () {
    return $this->normalized_version >= 3000;
  }

  /*
    Check is this a ocStore
  */
  public function isOcstore () {
    return $this->is_ocstore;
  }

}
