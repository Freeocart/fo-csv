<?php
/*
  FOC CSV common methods both for exporter/importer
*/
class ModelExtensionModuleFocCsvCommon extends Model {

  public function __construct ($registry, $type = 'importer') {
    parent::__construct($registry);
    $this->log = new Log('foc_csv_' . $type . '.txt');
    $this->type = $type;
    $this->profiles_key = 'foc_csv_' . $type . '_profiles';
    $this->profiles_code = 'foc_csv';
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

    $this->model_setting_setting->editSettingValue($this->profiles_code, $this->profiles_key, $profiles);
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
    return DIR_CACHE . $this->profiles_code . '/' . $key . '/' . $this->type . '/';
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

    return $key;
  }

  public function setUploadKey ($key) {
    $this->uploadKey = $key;
  }



  /* UTILS */

  /*
    Check if url is url:)
  */
  private function isUrl ($url) {
    return preg_match('/^https?\:\/\//', $url);
  }

  /*
    Get database charset
  */
  public function getDBCharset () {
    return $this->db->query('SELECT @@character_set_database AS `charset`')->row['charset'];
  }

  /*
    Just OC version checker used to provide forward/backward compatibility
  */
  public function isOpencart3 () {
    $version = (int)preg_replace('/\./', '', VERSION);
    return $version > 2999;
  }

}