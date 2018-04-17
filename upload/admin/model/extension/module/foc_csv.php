<?php

class ModelExtensionModuleFocCsv extends Model {

  private $profiles_code = 'foc_csv';
  private $profiles_key = 'foc_csv_profiles';
  private $csvImportFileName = 'import.csv';
  private $imagesZipImportFileName = 'images.zip';
  private $imagesZipExtractPath = 'images';
  private $imageSavePath = 'catalog/import';

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

    if (!is_dir(DIR_IMAGE . $this->imageSavePath)) {
      mkdir(DIR_IMAGE . $this->imageSavePath, 0755, true);
    }
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
      'previewFromGallery' => true,
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

  public function setImportKey ($key) {
    $this->importKey = $key;
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
    Helper to create SQL queries
  */
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
      $update .= $key . '=' . $fields[$key] . ',';
    }
    return array(
      'keys' => $keys,
      'values' => implode(',', array_values($fields)),
      'update' => rtrim($update, ',')
    );
  }

  /*
    Import entry point
  */
  public function import ($profile, $csv_row) {
    $bindings = $profile['bindings'];

    $tablesData = $this->getCsvToDBFields($bindings, $csv_row);

    $manufacturer_id = $this->importManufacturer($tablesData[DB_PREFIX . 'manufacturer']);

    // set manufacturer id to product fields
    $tablesData[DB_PREFIX . 'product']['manufacturer_id'] = $manufacturer_id;

    $product_id = $this->importProduct($tablesData[DB_PREFIX . 'product']);

    $product_description_table = DB_PREFIX . 'product_description';
    if (isset($tablesData[$product_description_table])) {
      $this->importProductSubtable('product_description', $tablesData[$product_description_table], $product_id);
    }

    $setPreviewFromGallery = false;
    $image = $this->db->query('SELECT `image` FROM '.DB_PREFIX.'product WHERE product_id = ' . (int)$product_id)->row['image'];

    if (isset($profile['previewFromGallery']) && $profile['previewFromGallery'] && empty($image)) {
      $setPreviewFromGallery = true;
    }

    if (isset($profile['clearGalleryBeforeImport']) && $profile['clearGalleryBeforeImport']) {
      $this->db->query('DELETE FROM ' . DB_PREFIX . 'product_image WHERE product_id=' . (int)$product_id);
    }

    // upload gallery
    $this->importGallery($tablesData[DB_PREFIX . 'product_image'], $profile['csvImageFieldDelimiter'], $profile['downloadImages'], $setPreviewFromGallery, $product_id);

  }

  private function importGallery ($fields, $separator, $download = false, $setPreview = false, $product_id) {
    if (isset($fields['image']) && !empty($fields['image'])) {
      $images = explode($separator, $fields['image']);

      if (count($images) > 0) {
        $imageCounter = 0;

        foreach ($images as $image) {
          $url = $this->downloadImage($image);

          if (!$url) {
            continue;
          }

          if ($imageCounter++ == 0 && $setPreview) {
            $this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($url) . "' WHERE product_id = '" . (int)$product_id . "'");
          }
          else {
            $this->db->query('INSERT INTO ' . DB_PREFIX . 'product_image (product_id, image, sort_order) VALUES (' . (int)$product_id . ',"' . $this->db->escape($url) . '",' . (int)$imageCounter . ')');
          }
        }
      }
    }
  }

  private function isUrl ($url) {
    return preg_match('/^https?\:\/\//', $url);
  }

  private function downloadImage ($url) {
    if ($this->isUrl($url)) {
      $file_name = md5($url);
      $save_path = DIR_IMAGE . $this->imageSavePath . '/' . $this->importKey . $file_name;

      file_put_contents($save_path, file_get_contents($url));

      $image = new Image($save_path);

      if (!empty($image->getMime())) {
        $image->save($save_path . '.png');
        return $this->imageSavePath . '/' . $this->importKey . $file_name . '.png';
      }

      unlink($save_path);
      return null;
    }
    else if (is_file($this->getImportImagesPath($this->importKey) . '/' . $url)) {
      rename($this->getImportImagesPath($this->importKey) . '/' . $url, DIR_IMAGE . $this->imageSavePath . '/' . $this->importKey . $url);
      return $this->imageSavePath . '/' . $this->importKey . $url;
    }
    else if (is_file(DIR_IMAGE . $this->imageSavePath . '/' . $this->importKey . $url)) {
      return $this->imageSavePath . '/' . $this->importKey . $url;
    }
  }

  private function importProductSubtable ($table, $fields, $product_id) {
    $fieldsSql = $this->fieldsToSQL($fields);

    $id = $this->db->query('SELECT IFNULL((SELECT product_id FROM ' . DB_PREFIX . $table . ' WHERE product_id LIKE "'.$this->db->escape($product_id).'"), 0) AS `id`')->row['id'];

    if ($id > 0) {
      $sql = 'UPDATE ' . DB_PREFIX . $table . ' SET ' . $fieldsSql['update'] . ' WHERE product_id LIKE ' . $this->db->escape($product_id);
      return $id;
    }
    else {
      $sql = 'INSERT INTO ' . DB_PREFIX . $table . ' VALUES ('.$fieldsSql['values'].')';
      return $this->db->getLastId();
    }
  }

  private function importProduct ($fields) {

    if (!empty($fields['image'])) {
      $fields['image'] = $this->downloadImage($fields['image']);
    }

    $fieldsSql = $this->fieldsToSQL($fields);

    $kfData = $this->keyFieldData;
    $key_value = $fields[$kfData['field']];

    if (empty($key_value)) {
      $this->log->write('[ERR] Empty key field [' . $kfData['field'] . '] value on [' . print_r($csv_row, true) . ']');
      return;
    }

    $id = 0;

    if (!empty($key_value)) {
      $id = $this->db->query('SELECT IFNULL((SELECT product_id FROM ' . DB_PREFIX . 'product WHERE '.$kfData['field'].' LIKE "'.$this->db->escape($key_value).'"), 0) AS `id`')->row['id'];
    }
    else {
      $this->log->write('[ERR] Product has empty key field [' . $kfData['field'] . ']!');
      return 0;
    }

    if (!$this->checkBeforeInsert || $this->checkerValue === ($id > 0)) {
      if ($this->updateExisting) {
        $sql = 'UPDATE ' . DB_PREFIX . 'product SET ' . $fieldsSql['update'] . ' WHERE '.$kfData['field'].' LIKE "' . $this->db->escape($key_value) . '"';
        $this->db->query($sql);

        return $id;
      }
      if ($this->deleteMode) {
        $sql = 'DELETE FROM ' . DB_PREFIX . 'product WHERE ' . $fieldsSql['update'] . ' LIKE "' . $this->db->escape($key_value) . '"';
        $this->db->query($sql);

        return $id;
      }
    }

    /*
      Insert new manufacturer
    */
    if ($this->insertNew) {
      $sql = 'INSERT INTO `' . DB_PREFIX . 'product` (' . $fieldsSql['keys'] . ') VALUES (' . $fieldsSql['values'] . ')';

      $this->db->query($sql);
      return $this->db->getLastId();
    }

    var_dump($id);
    die;
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