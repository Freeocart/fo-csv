<?php

class ModelExtensionModuleFocCsv extends Model {

  private $profiles_code = 'foc_csv';
  private $profiles_key = 'foc_csv_profiles';
  private $csvImportFileName = 'import.csv';
  private $imagesZipImportFileName = 'images.zip';
  private $imagesZipExtractPath = 'images';

  private $tableFieldDelimiter = ':';

  // import settings
  private $importMode = 'updateCreate';
  // public $keyFieldData = array(
  //   'table' => null,
  //   'field' => null
  // );

  public function __construct ($registry) {
    parent::__construct($registry);
    $this->log = new Log('foc_csv.txt');
  }

  public function install () {
    $this->load->model('setting/setting');
    $this->model_setting_setting->editSetting($this->profiles_code, array($this->profiles_key => array()));
    $this->saveProfiles($this->getDefaultProfiles());
  }

  /*
    Default profile data
  */
  public function getDefaultProfile () {
    return array(
      'encoding' => 'UTF8',
      'csvFieldDelimiter' => ';',
      'categoryDelimiter' => '/',
      'keyField' => 'product_id',
      'skipFirstLine' => true,
      'bindings' => new stdclass,
      'importMode' => 'updateCreate',
      'imagesImportMode' => 'add',
      'csvImageFieldDelimiter' => ';',
      'processAtStepNum' => 20
    );
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
    Key fields
  */
  public function getKeyFields () {
    return array(
      DB_PREFIX . 'product:product_id',
      DB_PREFIX . 'product:sku',
      DB_PREFIX . 'product:model',
      DB_PREFIX . 'product:name',
      DB_PREFIX . 'product:ean',
      DB_PREFIX . 'product:mpn',
      DB_PREFIX . 'product:jan',
      DB_PREFIX . 'product:isbn'
    );
  }

  /*
    Generate DB fields list
  */
  public function getDbFields () {
    $tables = array(
      DB_PREFIX . 'product',
      DB_PREFIX . 'product_description',
      DB_PREFIX . 'product_image',
      DB_PREFIX . 'manufacturer',
      DB_PREFIX . 'category',
      DB_PREFIX . 'category_description'
    );

    $result = array();

    foreach ($tables as $table) {
      $data = $this->db->query('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = "' . DB_DATABASE . '" AND TABLE_NAME = "' . $table . '"');
      $result[$table] = array_column($data->rows, 'COLUMN_NAME');
    }

    return $result;
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

    return $profiles;
  }

  /*
    Save profiles list
  */
  public function saveProfiles ($profiles) {
    $this->load->model('setting/setting');
    $this->model_setting_setting->editSettingValue($this->profiles_code, $this->profiles_key, $profiles);
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
    Save profile by name
  */
  public function setProfile ($name, $data) {
    $profiles = $this->loadProfiles();
    $profiles[$name] = $data;
    $this->saveProfiles($profiles);
  }

  public function getImportCsvPath ($key) {
    return DIR_CACHE . $this->profiles_code . '/' . $key . '/import/';
  }

  /*
    Returns import storage path for csv file
  */
  public function getImportCsvFilePath ($key) {
    $path = $this->getImportCsvPath($key);
    return $path . $this->csvImportFileName;
  }

  /*
    Returns import storage path for images zip file
  */
  public function getImportImagesZipPath ($key) {
    $path = $this->getImportCsvPath($key);
    return $path . $this->imagesZipImportFileName;
  }

  /*
    Returns images extract path
  */
  public function getImportImagesPath ($key) {
    $path = $this->getImportCsvPath($key);
    return $path . $this->imagesZipExtractPath;
  }

  /*
    Prepare import storage
    Returns storage key
  */
  public function prepareImportPath () {
    $key = md5(rand() . time());
    $path = $this->getImportCsvPath($key);

    if (is_dir($path)) {
      return $this->prepareImportPath();
    }

    mkdir($path, 0755, true);

    return $key;
  }

  /*
    Remove all unnecessary fields from csv
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

  /*
    Generating csv field => db field bindings
  */
  private function getCsvToDBFields ($bindings, $csv_row) {
    $cleared = $this->filterCsvToDBBindings($bindings);
    $data = array();

    foreach ($cleared as $csv_idx => $db_field) {
      if (isset($csv_row[$csv_idx])) {
        list($table, $field) = explode(':', $db_field);
        if (!isset($data[$table])) {
          $data[$table] = array();
        }

        $data[$table][$field] = $csv_row[$csv_idx];
      }
    }

    return $data;
  }

  public function toggleKeyField ($table, $key) {
    $this->keyFieldData = array(
      'table' => $table,
      'field'   => $key
    );
  }

  public function setImportMode ($mode) {
    // check by key field
    $this->checkBeforeInsert = true;
    // check result value
    $this->checkerValue = true;
    // update founded items
    $this->updateExisting = true;
    // insert new items if not found
    $this->insertNew = true;
    // delete founded items?
    $this->deleteMode = false;

    switch ($mode) {
      case 'onlyUpdate':
        $this->insertNew = false;
      break;
      case 'onlyAdd':
        $this->checkBeforeInsert = false;
      break;
      case 'updateCreate':
        // default settings...
      break;
      case 'addIfNotFound':
        $this->checkerValue = false;
        $this->updateExisting = false;
      break;
      case 'removeByList':
        $this->deleteMode = true;
      break;
      case 'removeOthers':
        $this->checkerValue = false;
        $this->deleteMode = true;
      break;
    }

    $this->importMode = $mode;
  }

  /*
    Import entry point
  */
  public function importProduct ($profile, $csv_row) {
    $bindings = $profile['bindings'];

    $tablesData = $this->getCsvToDBFields($bindings, $csv_row);
    $kfData = $this->keyFieldData;
    $key_value = $tablesData[$kfData['table']][$kfData['field']];

    if (empty($key_value)) {
      $this->log->write('[ERR] Empty key field [' . $kfData['field'] . '] value on [' . print_r($csv_row, true) . ']');
      return;
    }

    $manufacturer_id = $this->importManufacturer($tablesData[DB_PREFIX . 'manufacturer']);

    // set manufacturer id to product fields
    $tablesData[DB_PREFIX . 'product']['manufacturer_id'] = $manufacturer_id;

  }


  private function fieldsToSQL ($fields) {
    $keys = implode(',', array_keys($fields));
    $update = '';

    foreach ($fields as $key => $value) {
      if (is_numeric($value)) {
        $fields[$key] = $this->db->escape($value);
      }
      else {
        $fields[$key] = '"' . $this->db->escape($value) . '"';
      }
      $update .= $key . '=' . $fields[$key] . ' ';
    }
    return array(
      'keys' => $keys,
      'values' => implode(',', array_values($fields)),
      'update' => $update
    );
  }


  private function importProduct () {

  }

  /*
    Update existing manufacturer or create new
  */
  private function importManufacturer ($fields) {
    $fieldsSql = $this->fieldsToSQL($fields);

    $id = 0;

    if (isset($fields['name']) && !empty($fields['name'])) {
      $id = $this->db->query('SELECT IFNULL((SELECT manufacturer_id FROM '. DB_PREFIX .'manufacturer WHERE name LIKE "' . $fields['name'] . '" LIMIT 1), 0) AS `id`')->row['id'];
    }
    else {
      $this->log->write('[WARN] Manufacturer [' . $fields['name'] . '] has wrong name, so skipping...');
      return 0;
    }

    /*
      If need check before insert and checking successfull -> update or delete
    */
    if (!$this->checkBeforeInsert || $this->checkerValue === ($id > 0)) {
      if ($this->updateExisting) {
        $sql = 'UPDATE ' . DB_PREFIX . 'manufacturer SET ' . $fieldsSql['update'] . ' WHERE name LIKE "' . $fields['name'] . '"';
        $this->db->query($sql);

        return $id;
      }
      if ($this->deleteMode) {
        $sql = 'DELETE FROM ' . DB_PREFIX . 'manufacturer WHERE name LIKE "' . $fields['name'] . '"';
        $this->db->query($sql);

        return $id;
      }
    }

    /*
      Insert new manufacturer
    */
    if ($this->insertNew) {
      $sql = 'INSERT INTO `' . DB_PREFIX . 'manufacturer` (' . $fieldsSql['keys'] . ') VALUES (' . $fieldsSql['values'] . ')';

      $this->db->query($sql);
      return $this->db->getLastId();
    }

    return 0;
  }

  private function checkIsProductExist ($field, $value) {
    $sql = "SELECT COUNT({$field}) FROM oc_product WHERE ({$field} LIKE {$value})";
    return count($this->db->query($sql)->rows) > 0;
  }

  private function importDataToTable ($table, $data) {
    $sql = "INSERT INTO " . $table . "(";

    $sql .= ") values (";

  }

}