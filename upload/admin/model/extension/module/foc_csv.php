<?php

class ModelExtensionModuleFocCsv extends Model {

  private $profiles_code = 'foc_csv';
  private $profiles_key = 'foc_csv_profiles';
  private $csvImportFileName = 'import.csv';
  private $imagesZipImportFileName = 'images.zip';
  private $imagesZipExtractPath = 'images';
  private $imageSavePath = 'catalog/import';
  private $tableFieldDelimiter = ':';
  private $importMode = 'updateCreate';

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
      'categoryLevelDelimiter' => '>>',
      'keyField' => 'product_id',
      'skipFirstLine' => true,
      'bindings' => new stdclass,
      'importMode' => 'updateCreate',
      'imagesImportMode' => 'add',
      'csvImageFieldDelimiter' => ';',
      'previewFromGallery' => true,
      'processAtStepNum' => 20,
      'removeCharsFromCategory' => '[]{}',
      'removeManufacturersBeforeImport' => false,
      'storeId' => $this->config->get('config_store_id'),
      'languageId' => $this->config->get('language_id'),
      'statusRewrites' => array(),
      'stockStatusRewrites' => array()
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
      DB_PREFIX . 'product_description:name',
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
    DB Schema templates here
    This functions helping generate data for database
  */
  private function productDescriptionTemplate ($data = array()) {
    $tableData = array();
    $tableData[$this->language_id] = array_replace(array(
      'meta_title' => '',
      'tag' => '',
      'meta_description' => '',
      'meta_keyword' => '',
      'language_id' => $this->language_id,
      'product_id' => $this->product_id
    ), $data);

    return $tableData;
  }

  private function productToStoreTemplate () {
    return array($this->store_id);
  }

  private function productTemplate ($data = array()) {
    return array_replace(array(
      'model' => '',
      'sku' => '',
      'upc' => '',
      'ean' => '',
      'jan' => '',
      'isbn' => '',
      'mpn' => '',
      'location' => '',
      'quantity' => 0,
      'minimum' => 0,
      'subtract' => 0,
      'stock_status_id' => 0,
      'date_available' => date('Y-m-d'),
      'manufacturer_id' => 0,
      'shipping' => 0,
      'price' => 0,
      'points' => 0,
      'weight' => 0,
      'weight_class_id' => 0,
      'length_class_id' => 0,
      'length' => 0,
      'width' => 0,
      'height' => 0,
      'status' => 0,
      'tax_class_id' => 0,
      'sort_order' => 0,
      'keyword' => false // in product model there is no isset check!
    ), $data);
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
      'field'   => $key,
      'attribute' => str_replace(DB_PREFIX, '', $table)
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
        $this->updateExisting = false;
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
        $this->insertNew = false;
        $this->updateExisting = false;
      break;
      case 'removeOthers':
        $this->checkerValue = false;
        $this->deleteMode = true;
        $this->insertNew = false;
        $this->updateExisting = false;
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

    $this->language_id = (int) $this->config->get('config_language_id');
    if (isset($profile['languageId'])) {
      $this->language_id = (int) $profile['languageId'];
    }

    $this->store_id = (int) $this->config->get('config_store_id');
    if (isset($profile['storeId'])) {
      $this->store_id = (int) $profile['storeId'];
    }

    $manufacturer_id = $this->importManufacturer($tablesData[DB_PREFIX . 'manufacturer']);

    // set manufacturer id to product fields
    $tablesData[DB_PREFIX . 'product']['manufacturer_id'] = $manufacturer_id;

    /* IMPORT PRODUCTS */

    $productData = $this->productTemplate($tablesData[DB_PREFIX.'product']);
    $productData['manufacturer_id'] = $manufacturer_id;
    $productData['product_description'] = $this->productDescriptionTemplate($tablesData[DB_PREFIX.'product_description']);

    if (isset($profile['statusRewrites']) && in_array($productData['status'], $profile['statusRewrites'])) {
      $productData['status'] = array_search($productData['status'], $profile['statusRewrites']);
    }

    if (isset($profile['stockStatusRewrites']) && in_array($productData['stock_status_id'], $profile['stockStatusRewrites'])) {
      $productData['stock_status_id'] = array_search($productData['stock_status_id'], $profile['stockStatusRewrites']);
    }

    $productData['product_store'] = $this->productToStoreTemplate();
    $product_id = $this->importProduct($productData);

    if (!$product_id) {
      return false;
    }

    if (!$this->deleteMode) {
      /* IMPORT IMAGES */
      $setPreviewFromGallery = false;
      $image = $this->db->query('SELECT `image` FROM '.DB_PREFIX.'product WHERE product_id = ' . (int)$product_id)->row['image'];

      if (isset($profile['previewFromGallery']) && $profile['previewFromGallery'] && empty($image)) {
        $setPreviewFromGallery = true;
      }

      $imagesImportMode = isset($profile['imagesImportMode']) ? $profile['imagesImportMode'] : 'add';
      $skipImportGallery = false;

      if ($imagesImportMode === 'skip') {
        $skipImportGallery = $this->productGalleryNotEmpty($product_id);
      }

      if (!$skipImportGallery) {
        if (isset($profile['clearGalleryBeforeImport']) && $profile['clearGalleryBeforeImport']) {
          $this->db->query('DELETE FROM ' . DB_PREFIX . 'product_image WHERE product_id=' . (int)$product_id);
        }

        $this->importGallery($tablesData[DB_PREFIX . 'product_image'], $profile['csvImageFieldDelimiter'], $profile['downloadImages'], $setPreviewFromGallery, $product_id);
      }
    }

    /* IMPORT CATEGORIES */
    $cleanCategoryNames = isset($profile['removeCharsFromCategory']) ? $profile['removeCharsFromCategory'] : '';

    $category_ids = $this->importProductCategories($tablesData[DB_PREFIX . 'category_description'], $profile['categoryDelimiter'], $profile['categoryLevelDelimiter'], $cleanCategoryNames, $this->language_id, $this->store_id);

    $fillParentCategories = isset($profile['fillParentCategories']) ? $profile['fillParentCategories'] : false;
    $clearCategoriesBeforeImport = isset($profile['clearCategoriesBeforeImport']) ? $profile['clearCategoriesBeforeImport'] : false;

    if ($clearCategoriesBeforeImport) {
      $this->db->query('DELETE FROM ' . DB_PREFIX . 'product_to_category WHERE product_id = ' . (int)$product_id);
    }

    if (!$this->deleteMode) {
      foreach ($category_ids as $path => $ids) {
        if ($fillParentCategories) {
          foreach ($ids as $category_id) {
            $this->bindProductToCategory($product_id, $category_id);
          }
        }
        else {
          $category_id = array_pop($ids);
          $this->bindProductToCategory($product_id, $category_id);
        }
      }
    }

    return true;
  }

  /*
    Bind product to category
  */
  private function bindProductToCategory ($product_id, $category_id) {
    $exist = $this->db->query('SELECT IFNULL((SELECT product_id FROM ' . DB_PREFIX . 'product_to_category WHERE product_id = ' . (int)$product_id . ' AND category_id = ' . (int) $category_id . ' LIMIT 1), 0) AS id')->row['id'];

    if (!$exist) {
      $this->db->query('INSERT INTO ' . DB_PREFIX . 'product_to_category (product_id, category_id) VALUES (' . (int)$product_id . ',' . (int)$category_id . ')');
      return $this->db->getLastId();
    }
  }

  /*
    Lite category import (names only)
  */
  private function importProductCategories ($fields, $delimiter, $levelDelimiter, $cleanCategoryNames = '') {
    $result = array();

    if (isset($fields['name']) && !empty($fields['name'])) {
      $category_raw = str_replace(str_split($cleanCategoryNames), '', $fields['name']);
      $categories = explode($delimiter, $category_raw);

      $this->load->model('catalog/category');

      foreach ($categories as $categoryPath) {
        $categoryParts = array_map('trim', explode($levelDelimiter, $categoryPath));

        $prev_id = 0;

        foreach ($categoryParts as $categoryName) {
          $id = (int)$this->db->query("SELECT IFNULL((SELECT category_id FROM " . DB_PREFIX . 'category_description WHERE name LIKE "' . $this->db->escape($categoryName) . '" AND language_id = '.(int)$this->language_id.' LIMIT 1), 0) AS `id`')->row['id'];

          $category = (int)$this->db->query('SELECT IFNULL((SELECT category_id FROM ' . DB_PREFIX . 'category WHERE category_id = ' . (int)$id . '), 0) AS `id`')->row['id'];

          if (!$category && !$id) {
            $this->db->query('INSERT INTO ' . DB_PREFIX . 'category (parent_id, status) VALUES (' . (int)$prev_id . ', 1)');
            $prev_id = $this->db->getLastId();
          }
          else if (!$category && $id) {
            $this->db->query('INSERT INTO ' . DB_PREFIX . 'category (category_id, parent_id, status) VALUES (' . (int)$id . ',' . $prev_id . ', 1)');
          }

          if ($id === 0) {
            $this->db->query('INSERT INTO ' . DB_PREFIX . 'category_description (category_id, name, language_id) VALUES ('.(int)$prev_id.',"' . $this->db->escape($categoryName) . '", ' . (int)$this->language_id . ')');
          }
          else {
            $this->db->query('UPDATE ' . DB_PREFIX . 'category_description SET name = "' . $this->db->escape($categoryName) . '", language_id = ' . (int)$this->language_id . ' WHERE category_id = ' . (int)$id);
            $prev_id = $id;
          }

          $result[$categoryPath][] = $prev_id;
        }
      }
    }
    $this->model_catalog_category->repairCategories();

    $this->cache->delete('category');
    return $result;
  }

  /*
    Imports gallery to DB
  */
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

  /*
    Check if product has images
  */
  private function productGalleryNotEmpty ($product_id) {
    $count = $this->db->query('SELECT COUNT(product_id) AS `count` FROM ' . DB_PREFIX . 'product_image WHERE product_id = ' . (int)$product_id)->row['count'];

    return $count > 0;
  }


  /*
    Check if url is url:)
  */
  private function isUrl ($url) {
    return preg_match('/^https?\:\/\//', $url);
  }

  /*
    Simple image downloader
  */
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

  /*
    Product import helper
  */
  private function importProductSubtable ($table, $fields, $product_id = null) {
    die('deprecated importProductSubtable');
    // if ($product_id) {
    //   $fields['product_id'] = $product_id;
    // }

    // $fieldsSql = $this->fieldsToSQL($fields);

    // $id = (int)$this->db->query('SELECT IFNULL((SELECT product_id FROM ' . DB_PREFIX . $table . ' WHERE product_id LIKE "'.$this->db->escape($product_id).'"), 0) AS `id`')->row['id'];

    // if ($id > 0) {
    //   $this->db->query('UPDATE ' . DB_PREFIX . $table . ' SET ' . $fieldsSql['update'] . ' WHERE product_id LIKE ' . $this->db->escape($product_id));
    //   return $id;
    // }
    // else {
    //   $this->db->query('INSERT INTO ' . DB_PREFIX . $table . ' (' . $fieldsSql['keys'] . ') VALUES ('.$fieldsSql['values'].')');
    //   return $this->db->getLastId();
    // }
  }

  /*
    Import product fields
  */
  private function importProduct ($fields) {
    $this->load->model('catalog/product');

    if (!empty($fields['image'])) {
      $fields['image'] = $this->downloadImage($fields['image']);
    }

    $key_table = $this->keyFieldData['table'];
    $key_field = $this->keyFieldData['field'];
    $key_attribute = $this->keyFieldData['attribute'];
    $key_value = null;

    // key_field is in product table
    if ($key_attribute === 'product') {
      $key_value = $fields[$key_field];
    }
    // product_description for example
    elseif (isset($fields[$key_attribute])) {
      $key_value = $fields[$key_attribute][$this->language_id][$key_field];
    }

    if (is_null($key_value) || empty($key_value)) {
      $this->log->write('[ERR] Empty key field [' . $key_field . '] value on [' . print_r($fields, true) . ']');
      return null;
    }

    $id = 0;

    if ($this->checkBeforeInsert && !empty($key_value)) {
      @$id = $this->db->query('SELECT IFNULL((SELECT product_id FROM ' . $key_table . ' WHERE ' . $key_field . ' LIKE "' . $this->db->escape($key_value).'"), 0) AS `id`')->row['id'];
    }
    else {
      $this->log->write('[ERR] Product has empty key field [' . $key_field . ']!');
    }

    if (!$this->checkBeforeInsert || $this->checkerValue === ($id > 0)) {
      if ($this->updateExisting) {
        $this->model_catalog_product->editProduct($id, $fields);
        return $id;
      }
      if ($this->deleteMode) {
        $this->model_catalog_product->deleteProduct($id);
        return $id;
      }
    }

    /*
      Insert new manufacturer
    */
    if ($this->insertNew) {
      return $this->model_catalog_product->addProduct($fields);
    }
  }

  /*
    Update existing manufacturer or create new
  */
  private function importManufacturer ($fields) {
    $this->load->model('catalog/manufacturer');

    $fields['manufacturer_store'] = array($this->store_id);
    $fields['sort_order'] = 0;

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
        // fix for editManufacturer
        if (!isset($fields['keyword'])) {
          $fields['keyword'] = false;
        }
        $this->model_catalog_manufacturer->editManufacturer($id, $fields);
        return $id;
      }
      if ($this->deleteMode) {
        $this->model_catalog_manufacturer->deleteManufacturer($id);
        return $id;
      }
    }

    /*
      Insert new manufacturer
    */
    if ($this->insertNew) {
      return $this->model_catalog_manufacturer->addManufacturer($fields);
    }

    return 0;
  }

  /*
    Remove all manufacturers
  */
  public function clearManufacturers () {
    $this->db->query("DELETE FROM " . DB_PREFIX . "manufacturer");
		$this->db->query("DELETE FROM " . DB_PREFIX . "manufacturer_to_store");
		$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query LIKE 'manufacturer_id=%'");
  }

}