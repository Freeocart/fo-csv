<?php

class ModelExtensionModuleFocCsv extends Model {

  private $profiles_code = 'foc_csv';
  private $profiles_key = 'foc_csv_profiles';
  private $csvImportFileName = 'import.csv';

  public function install () {
    $this->load->model('setting/setting');
    $this->model_setting_setting->editSetting($this->profiles_code, array($this->profiles_key => array()));
    $this->saveProfiles($this->getDefaultProfiles());
  }

  public function getDefaultProfile () {
    return array(
      "encoding" => "UTF8",
      "csvFieldDelimiter" => ";",
      "categoryDelimiter" => "/",
      "keyField" => "product_id",
      "skipFirstLine" => true,
      "bindings" => array(),
      "importMode" => "updateCreate",
      "csvImageFieldDelimiter" => ";",
      "processAtStepNum" => 20
    );
  }

  public function getDefaultProfiles () {
    return array(
      'default' => $this->getDefaultProfile()
    );
  }

  public function getKeyFields () {
    return array(
      'product_id',
      'sku',
      'model',
      'name',
      'ean',
      'mpn',
      'jan',
      'isbn'
    );
  }

  public function getDbFields () {
    $tables = array(
      DB_PREFIX . 'product',
      DB_PREFIX . 'product_description'
    );

    $result = array();

    foreach ($tables as $table) {
      $data = $this->db->query('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = "' . DB_DATABASE . '" AND TABLE_NAME = "' . $table . '"');
      $result[$table] = array_column($data->rows, 'COLUMN_NAME');
    }

    return $result;
  }

  // загружает все профили
  public function loadProfiles () {
    $this->load->model('setting/setting');

    $profiles = json_decode($this->model_setting_setting->getSettingValue($this->profiles_key), true);

    if (count($profiles) === 0) {
      $profiles = $this->getDefaultProfiles();
    }

    return $profiles;
  }

  // сохраняет все профили
  public function saveProfiles ($profiles) {
    $this->load->model('setting/setting');
    $this->model_setting_setting->editSettingValue($this->profiles_code, $this->profiles_key, $profiles);
  }

  // загружает профиль по имени
  public function loadProfile ($name) {
    $profiles = $this->loadProfiles();

    if (isset($profiles[$name])) {
      return $profiles[$name];
    }

    return null;
  }

  // сохраняет профиль по имени
  public function setProfile ($name, $data) {
    $profiles = $this->loadProfiles();
    // var_dump($profiles);
    $profiles[$name] = $data;
    $this->saveProfiles($profiles);
  }

  public function getImportCsvPath ($key) {
    return DIR_CACHE . $this->profiles_code . '/' . $key . '/import/';
  }

  public function getImportCsvFilePath ($key) {
    $path = $this->getImportCsvPath($key);
    return $path . $this->csvImportFileName;
  }

  public function getImportImagesZipPath ($key) {
    $path = $this->getImportCsvPath($key);
    return $path . 'images.zip';
  }

  public function getImportImagesPath ($key) {
    $path = $this->getImportCsvPath($key);
    return $path . 'images/';
  }

  public function prepareImportPath () {
    $key = md5(rand() . time());
    $path = $this->getImportCsvPath($key);

    if (is_dir($path)) {
      return $this->prepareImportPath();
    }

    mkdir($path, '0755', true);
    // папка для изображений
    // mkdir($path . '/images', '0755', true);

    return $key;
  }

  /*
    Убирает все лишние/пустые поля
  */
  private function filterCsvToDBBindings ($bindings) {
    $cleared = array();

    foreach ($bindings as $csv_idx => $db_field) {
      if ($db_field === 'nothing' || trim($db_field) === '') {
        continue;
      }
      $cleared[$csv_idx] = $db_field;
    }

    return $cleared;
  }

  private function getCsvToDBFields ($bindings, $csv_row) {
    $cleared = $this->filterCsvToDBBindings($bindings);
    $data = array();

    foreach ($cleared as $csv_idx => $db_field) {
      if (isset($csv_row[$csv_idx])) {
        $data[$db_field] = $csv_row[$csv_idx];
      }
    }

    return $data;
  }

  public function importProduct ($profile, $csv_row) {
    $bindings = $profile['bindings'];

    $csvToDB = $this->getCsvToDBFields($bindings, $csv_row);
  }

}