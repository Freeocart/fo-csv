<?php
/*
  Model for FOC CSV Importer
*/

class ModelExtensionModuleFocCsv extends ModelExtensionModuleFocCsvCommon {

  private $csvImportFileName = 'import.csv';
  private $imagesZipImportFileName = 'images.zip';
  private $imagesZipExtractPath = 'images';
  private $imageSavePath = 'catalog/import';
  private $importMode = 'updateCreate';

  private $multicolumnData = array();
  private $attributeParsers = array();

  const MULTILANGUAGE_TABLES = array(
    'product_description'
  );

  public function __construct ($registry) {
    parent::__construct($registry, 'importer');

    if (!is_dir(DIR_IMAGE . $this->imageSavePath)) {
      $this->writeLog('Creating image save path [' . $this->imageSavePath . ']');
      mkdir(DIR_IMAGE . $this->imageSavePath, 0755, true);
    }

    $this->language->load('extension/module/foc_attribute_parsers');

    $this->attributeParsers['advantshop'] = array(
      'title' => $this->language->get('parser_advantshop'),
      'options' => array(
        'keyvalue_delimiter' => array(
          'title' => $this->language->get('parser_advantshop_keyvalue_delimiter'),
          'default' => ':'
        ),
        'entries_delimiter' => array(
          'title' => $this->language->get('parser_advantshop_entries_delimiter'),
          'default' => ';'
        )
      )
    );

    $this->attributeParsers['column'] = array(
      'title' => $this->language->get('parser_column'),
      'options' => array(
        'columns' => array(
          'title' => $this->language->get('parser_column_column'),
          'default' => array(),
          'type' => 'column'
        )
      )
    );

    /*
      Parser option "type" - is text by default
      use csvfield to get json with selected fields
    */

    /*
      use this section to describe your attribute parsers via vq/ocmod
      please see advantshop parser as reference
    */
    /* CUSTOM ATTRIBUTE PARSER DESCRIBE */

  }

  public function install () {
    parent::install();
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
      'defaultAttributesGroup' => 'FOC',
      'keyField' => 'product_id',
      'skipLines' => 0,
      'bindings' => new stdclass,
      'importMode' => 'updateCreate',
      'imagesImportMode' => 'add',
      'csvImageFieldDelimiter' => ';',
      'previewFromGallery' => true,
      'processAtStepNum' => 20,
      'removeCharsFromCategory' => '[]{}',
      'removeManufacturersBeforeImport' => false,
      'csvWithoutHeaders' => false,
      'csvHeadersLineNumber' => 1,
      // 'storeId' => $this->config->get('config_store_id'),
      // 'languageId' => $this->config->get('language_id'),
      'statusRewrites' => array(),
      'stockStatusRewrites' => array(),
      'downloadImages' => false,
      'attributeParser' => null,
      'store' => $this->config->get('config_store_id'),
      'language' => $this->config->get('config_language_id'),
      'attributeParserData' => array(),
      'skipLineOnEmptyFields' => array(),
      'multicolumnFields' => array()
    );
  }

  /*
    DB Schema templates here
    This functions helping generate data for database
  */
  private function productDescriptionTemplate ($data = array()) {
    $tableData = array();
    $default = array(
      'description' => '',
      'meta_title' => '',
      'tag' => '',
      'name' => '',
      'meta_title' => '',
      'meta_description' => '',
      'meta_keyword' => ''//,
      // 'language_id' => $this->language_id,
      // 'product_id' => $this->product_id
    );

    if ($this->isOcstore()) {
      $default['meta_h1'] = '';
    }
    else {
      $default['meta_title'] = '';
    }

    foreach ($data as $language_id => $values) {
      $tableData[$language_id] = array_replace($default, $values);
    }

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
    OcStore compatibility
  */
  private function manufacturerTemplate ($data = array()) {
    if (!isset($data['name'])) {
      return array();
    }

    $defaultDescription = array();
    $defaultDescription[$this->language_id] = array();
    $defaultDescription[$this->language_id] = array_replace(array(
      'name' => '',
      'description' => '',
      'meta_title' => '',
      'meta_description' => '',
      'meta_h1' => '',
      'meta_keyword' => ''
    ), $data);

    $defaultStore = array($this->store_id);

    return array_replace_recursive(array(
      'name' => '',
      'sort_order' => 0,
      'image' => '',
      'manufacturer_description' => $defaultDescription,
      'manufacturer_store' => $defaultStore
    ), $data);
  }

  /* FILE MANIPULATION METHODS */

  /*
    Returns import storage path for csv file
  */
  public function getImportCsvFilePath ($key) {
    $path = $this->getUploadPath($key);
    return $path . $this->csvImportFileName;
  }

  /*
    Returns import storage path for images zip file
  */
  public function getImportImagesZipPath ($key) {
    $path = $this->getUploadPath($key);
    return $path . $this->imagesZipImportFileName;
  }

  /*
    Move uploaded images to site IMAGES dir
  */
  public function moveUploadedImages ($key) {
    $path = $this->getImportImagesPath($key);

    $dirIterator = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($dirIterator);

    foreach ($iterator as $filename) {
      if ($filename->isDir()) {
        continue;
      }

      $moveTo = DIR_IMAGE . ltrim(str_replace($path, '', $filename->getPathname()), '/');
      $moveToDir = DIR_IMAGE . ltrim(str_replace($path, '', $filename->getPath()));

      if (!is_dir($moveToDir)) {
        mkdir($moveToDir, 0755, true);
      }

      rename($filename->getPathname(), $moveTo);
    }
  }

  /*
    Returns images extract path
  */
  public function getImportImagesPath ($key) {
    $path = $this->getUploadPath($key);
    return $path . $this->imagesZipExtractPath;
  }

  /*
    Remove all unnecessary fields from csv
  */
  private function filterCsvToDBBindings ($bindings) {
    $cleared = array();

    foreach ($bindings as $db_field => $csv_idx) {
      if ($db_field === 'nothing' || trim($db_field) === '') {
        continue;
      }
      $cleared[$db_field] = $csv_idx;
    }

    return $cleared;
  }

  /*
    Generating csv field => db field bindings
  */
  private function getCsvToDBFields ($bindings, $csv_row) {
    $cleared = $this->filterCsvToDBBindings($bindings);
    $data = array();

    foreach ($cleared as $db_field => $csv_idx) {
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
      case 'removeOthers': // removeOthers is loike updateCreate but before this - we have empty db!
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
    }

    $this->importMode = $mode;
  }

  public function processMulticolumnField ($config, $bindings, $csv_row) {
    $variables = array(
      'multicolumn' => array()
    );
    foreach ($config['csvFields'] as $group) {
      $fields = $group['fields'];//json_decode($group['fields'], true);

      foreach ($fields as $field) {
        $variable = array();

        if (is_array($field)) {
          foreach ($field as $field_key => $field_value) {
            if ($field_key == 'field') {
              $variable[$field_key] = $csv_row[$field_value];
            }
            else {
              $variable[$field_key] = $field_value;
            }
          }
          $variables[$group['variable']][] = $variable;
        }

      }
    }

    return FocSimpleTemplater::render($config['valueTemplate'], $variables);
  }

  /*
    Check if table expect multilanguage data structure
  */
  function isMultilangTable ($table) {
    return in_array($table, self::MULTILANGUAGE_TABLES);
  }

  function processMulticolumnFields ($fields) {
    foreach ($this->multicolumnData as $table => $field) {
      foreach ($field as $fieldName => $data) {
        $srcTable_link = null;
        $fieldValue = '';

        if (!isset($fields[$table])) {
          $fields[$table] = array();
        }

        if ($this->isMultilangTable($table) && !isset($fields[$table][$this->language_id])) {
          $fields[$table][$this->language_id] = array();
          $fields[$table][$this->language_id][$fieldName] = '';
        }

        if (!$this->isMultilangTable($table)) {
          if (!isset($fields[$table][$fieldName])) {
            $fields[$table][$fieldName] = '';
          }
          $srcTable_link =& $fields[$table];
        }
        else {
          $srcTable_link =& $fields[$table][$this->language_id];
        }

        if (is_null($srcTable_link)) {
          $this->writeLog('Cannot link to multicolumn field:(');
          continue;
        }

        switch ($data['mode']) {
          case 'after':
            $fieldValue = $srcTable_link[$fieldName] . $data['value'];
            break;
          case 'before':
            $fieldValue = $data['value'] . $srcTable_link[$fieldName];
            break;
          default:
            $fieldValue = $data['value'];
            break;
        }
        $srcTable_link[$fieldName] = $fieldValue;
      }
    }

    return $fields;
  }
  /*
    Import entry point
  */
  public function import ($profile, $csv_row, $csv_row_num = 0) {
    $this->csv_row_num = $csv_row_num;

    // skip csv line conditions
    $skipOnEmpty = isset($profile['skipLineOnEmptyFields']) ? $profile['skipLineOnEmptyFields'] : array();

    if (!is_null($skipOnEmpty) && count($skipOnEmpty) > 0) {
      foreach ($skipOnEmpty as $item) {
        if (!isset($csv_row[$item['idx']]) || trim($csv_row[$item['idx']]) == '') {
          $this->writeLog('Skip empty {' . $item['name'] . '} on ' . $csv_row_num);
          return false;
        }
      }
    }

    $bindings = $profile['bindings'];

    $tablesData = $this->getCsvToDBFields($bindings, $csv_row);

    $this->multicolumnData = array();
    // multicolumn fields processing
    foreach ($profile['multicolumnFields'] as $mc_field) {
      $mc_db_field_raw = $mc_field['dbField'];
      $mc_mode = $mc_field['mode'];
      if (!$mc_db_field_raw) {
        continue;
      }
      list($mc_table, $mc_table_field) = explode(':', $mc_db_field_raw);

      if (!isset($this->multicolumnData[$mc_table])) {
        $this->multicolumnData[$mc_table] = array();
      }
      if (!isset($this->multicolumnData[$mc_table][$mc_table_field])) {
        $this->multicolumnData[$mc_table][$mc_table_field] = '';
      }

      $processed = $this->processMulticolumnField($mc_field, $bindings, $csv_row);
      $this->multicolumnData[$mc_table][$mc_table_field] = array(
        'value' => $processed,
        'mode' => $mc_mode
      );
    }

    $this->language_id = (int) $this->config->get('config_language_id');
    if (isset($profile['languageId'])) {
      $this->language_id = (int) $profile['languageId'];
    }

    $this->store_id = (int) $this->config->get('config_store_id');
    if (isset($profile['storeId'])) {
      $this->store_id = (int) $profile['storeId'];
    }

    // inport manufacturers
    $manufacturer_id = 0;
    if (isset($tablesData['manufacturer'])) {
      $manufacturerData = $this->manufacturerTemplate($tablesData['manufacturer']);
      $manufacturer_id = $this->importManufacturer($manufacturerData);
      // set manufacturer id to product fields
      $tablesData['product']['manufacturer_id'] = $manufacturer_id;
    }

    /* IMPORT ATTRIBUTES */
    if (!isset($profile['defaultAttributesGroup'])) {
      $profile['defaultAttributesGroup'] = 'FOC';
    }

    $attributes = null;
    $parserEnabled = isset($profile['attributeParser']) && !is_null($profile['attributeParser']);

    if ($parserEnabled) {
      $attributes = $this->parseAttributes($profile, $csv_row);
    }

    /* IMPORT PRODUCTS */

    // set default status if not presented
    if (!isset($tablesData['product']['status'])
        && isset($profile['defaultStatus'])
    ) {
      $tablesData['product']['status'] = $profile['defaultStatus'];
    }

    // fill product data from csv
    $productData = isset($tablesData['product']) ? $tablesData['product'] : array(); //$this->productTemplate($tablesData['product']);
    $productData['manufacturer_id'] = $manufacturer_id;

    // fill product description from csv
    $productData['product_description'] = array();

    if (isset($tablesData['product_description'])) {
      $productData['product_description'][$this->language_id] = $tablesData['product_description'];
    }

    // process attributes
    // set attributes to product bindings
    if ($attributes) {
      $productData['product_attribute'] = array();

      foreach ($attributes as $attribute) {
        $productAttribute = array(
          'attribute_id' => $attribute['attribute_id'],
          'product_attribute_description' => array()
        );

        $productAttribute['product_attribute_description'][$this->language_id] = array(
          'text' => $attribute['value']
        );
        $productData['product_attribute'][] = $productAttribute;
      }
    }

    // status rewrites processing
    if (isset($profile['statusRewrites'])
        && isset($productData['status'])
        && in_array($productData['status'], $profile['statusRewrites'])
    ) {
      $productData['status'] = array_search($productData['status'], $profile['statusRewrites']);
    }

    if (isset($profile['defaultStockStatus'])) {
      $productData['stock_status_id'] = $profile['defaultStockStatus'];
    }

    // stock_status rewrites processing
    if (isset($profile['stockStatusRewrites'])
        && isset($productData['stock_status_id'])
        && in_array($productData['stock_status_id'], $profile['stockStatusRewrites'])
    ) {
      $productData['stock_status_id'] = array_search($productData['stock_status_id'], $profile['stockStatusRewrites']);
    }

    $productData['product_store'] = $this->productToStoreTemplate();

    /*
      Import the product data
    */
    $product_id = $this->importProduct($productData);

    if (!$product_id) {
      return false;
    }

    if (!$this->deleteMode && isset($tablesData['product_image'])) {
      /* IMPORT IMAGES */
      $setPreviewFromGallery = false;
      $image = $this->db->query('SELECT `image` FROM '.DB_PREFIX.'product WHERE product_id = ' . (int)$product_id)->row['image'];

      if (isset($profile['previewFromGallery'])
          && $profile['previewFromGallery']
          && empty($image)
      ) {
        $setPreviewFromGallery = true;
      }

      $imagesImportMode = isset($profile['imagesImportMode']) ? $profile['imagesImportMode'] : 'add';
      $skipImportGallery = false;

      if ($imagesImportMode === 'skip') {
        $skipImportGallery = $this->productGalleryNotEmpty($product_id);
      }

      if (!$skipImportGallery) {
        if (isset($profile['clearGalleryBeforeImport'])
            && $profile['clearGalleryBeforeImport']
        ) {
          $this->db->query('DELETE FROM ' . DB_PREFIX . 'product_image WHERE product_id=' . (int)$product_id);
        }

        $this->importGallery($tablesData['product_image'], $profile['csvImageFieldDelimiter'], $profile['downloadImages'], $setPreviewFromGallery, $product_id);
      }
    }

    /* IMPORT CATEGORIES */
    if (isset($tablesData['category_description'])) {

      $cleanCategoryNames = isset($profile['removeCharsFromCategory']) ? $profile['removeCharsFromCategory'] : '';

      $category_ids = $this->importProductCategories(
        $tablesData['category_description'],
        $profile['categoryDelimiter'],
        $profile['categoryLevelDelimiter'],
        $cleanCategoryNames,
        $this->language_id,
        $this->store_id
      );

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
    }

    return true;
  }

  /* CATEGORIES CODE */
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

          $this->db->query("DELETE FROM " . DB_PREFIX . "category_to_store WHERE category_id = '" . (int)$prev_id . "'");
          $this->db->query('INSERT INTO ' . DB_PREFIX . 'category_to_store (category_id, store_id) VALUES (' . (int)$prev_id . ', ' . (int) $this->store_id . ')');

          $result[$categoryPath][] = $prev_id;
        }
      }
    }
    $this->model_catalog_category->repairCategories();

    $this->cache->delete('category');
    return $result;
  }

  /* IMAGES IMPORT CODE */

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
    Simple image downloader
  */
  private function downloadImage ($url) {
    if ($this->isUrl($url)) {
      $file_name = md5($url);
      $save_dir = DIR_IMAGE . $this->imageSavePath . '/' . $this->uploadKey;
      $save_path = $save_dir . $file_name;

      if (!is_dir($save_dir)) {
        mkdir($save_dir, 0755, true);
      }

      file_put_contents($save_path, file_get_contents($url));

      $image = new Image($save_path);

      if (!empty($image->getMime())) {
        $image->save($save_path . '.png');
        return $this->imageSavePath . '/' . $this->uploadKey . $file_name . '.png';
      }

      unlink($save_path);
      return null;
    }
    // already unpacked/uploaded image
    else if (is_file(DIR_IMAGE . $url)) {
      return $url;
    }
    // previosly imported
    else if (is_file(DIR_IMAGE . $this->imageSavePath . '/' . $this->uploadKey . $url)) {
      return $this->imageSavePath . '/' . $this->uploadKey . $url;
    }
    else if (is_file($this->getImportImagesPath($this->uploadKey) . '/' . $url)) {
      $save_dir = dirname(DIR_IMAGE . $this->imageSavePath . '/' . $this->uploadKey . $url);
      rename($this->getImportImagesPath($this->uploadKey) . '/' . $url, $save_dir . $url);
      return $this->imageSavePath . '/' . $this->uploadKey . $url;
    }
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
      $this->writeLog('Empty key field [' . $key_field . '] value on [' . $this->csv_row_num . ']', 'error');
      return null;
    }

    $id = 0;

    if ($this->checkBeforeInsert && !empty($key_value)) {
      @$id = $this->db->query('SELECT IFNULL((SELECT product_id FROM ' . DB_PREFIX . $key_table . ' WHERE ' . $key_field . ' LIKE "' . $this->db->escape($key_value).'"), 0) AS `id`')->row['id'];
    }
    else {
      $this->writeLog('Product has empty key field [' . $key_field . '] on [' . $this->csv_row_num . '] !', 'error');
    }

    if (!$this->checkBeforeInsert || $this->checkerValue === ($id > 0)) {
      if ($this->updateExisting) {
        $this->safeEditProduct($id, $fields);
        return $id;
      }
      if ($this->deleteMode) {
        $this->model_catalog_product->deleteProduct($id);
        return $id;
      }
    }

    /*
      Insert new product
    */
    if ($this->insertNew) {
      return $this->safeAddProduct($fields);
    }
  }

  /*
    Fill data object with default values
    before add
  */
  public function safeAddProduct ($data) {
    $data = $this->productTemplate($data);
    $data['product_description'] = $this->productDescriptionTemplate($data['product_description']);
    $data = $this->processMulticolumnFields($data);

    return $this->model_catalog_product->addProduct($data);
  }

  /*
    Opencart default product models delete all data before check user input
    Also many modules can modify default model, so we want only update our existing fields
    Unfortunatenally we need to duplicate all this code here
    Only FO CSV supported fields being updated!
  */
  public function safeEditProduct ($product_id, $data) {
    // we do not want to lose product data before import, so use old data as basement
    // $product_data = $this->model_catalog_product->getProduct($product_id);
    $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "product p WHERE p.product_id = '" . (int)$product_id . "'");

    if ($query->num_rows) {
      $product_data = $query->row;
      $product_data['product_description'] = $this->model_catalog_product->getProductDescriptions($product_id);
      $data = array_replace_recursive($product_data, $data);
      $data = $this->processMulticolumnFields($data);
    }
    else {
      $this->writeLog('Product [' . $product_id . '] not found!');
      return false;
    }

    // update product table
    $this->db->query("UPDATE " . DB_PREFIX . "product SET model = '" . $this->db->escape($data['model']) . "', sku = '" . $this->db->escape($data['sku']) . "', upc = '" . $this->db->escape($data['upc']) . "', ean = '" . $this->db->escape($data['ean']) . "', jan = '" . $this->db->escape($data['jan']) . "', isbn = '" . $this->db->escape($data['isbn']) . "', mpn = '" . $this->db->escape($data['mpn']) . "', location = '" . $this->db->escape($data['location']) . "', quantity = '" . (int)$data['quantity'] . "', minimum = '" . (int)$data['minimum'] . "', subtract = '" . (int)$data['subtract'] . "', stock_status_id = '" . (int)$data['stock_status_id'] . "', date_available = '" . $this->db->escape($data['date_available']) . "', manufacturer_id = '" . (int)$data['manufacturer_id'] . "', shipping = '" . (int)$data['shipping'] . "', price = '" . (float)$data['price'] . "', points = '" . (int)$data['points'] . "', weight = '" . (float)$data['weight'] . "', weight_class_id = '" . (int)$data['weight_class_id'] . "', length = '" . (float)$data['length'] . "', width = '" . (float)$data['width'] . "', height = '" . (float)$data['height'] . "', length_class_id = '" . (int)$data['length_class_id'] . "', status = '" . (int)$data['status'] . "', tax_class_id = '" . (int)$data['tax_class_id'] . "', sort_order = '" . (int)$data['sort_order'] . "', date_modified = NOW() WHERE product_id = '" . (int)$product_id . "'");

    if (isset($data['image'])) {
      $this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($data['image']) . "' WHERE product_id = '" . (int)$product_id . "'");
    }

    // description
    if (isset($data['product_description'])
        && !empty($data['product_description'])
    ) {
      $this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'");

      foreach ($data['product_description'] as $language_id => $value) {
        $insert_string = "product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "',";

        foreach ($value as $name => $value) {
          $insert_string .= $name . " = '" . $this->db->escape($value) . "',";
        }

        $this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET " . rtrim($insert_string, ','));
      }
    }

    // product_store update
    if (isset($data['product_store'])
        && !empty($data['product_store'])
    ) {
      $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int)$product_id . "'");

      if (isset($data['product_store'])) {
        foreach ($data['product_store'] as $store_id) {
          $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "'");
        }
      }
    }

    // attributes
    if (isset($data['product_attribute'])
        && !empty($data['product_attribute'])
    ) {
      $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "'");

      if (!empty($data['product_attribute'])) {
        foreach ($data['product_attribute'] as $product_attribute) {
          if ($product_attribute['attribute_id']) {
            // Removes duplicates
            $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "'");

            foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
              $this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$product_attribute['attribute_id'] . "', language_id = '" . (int)$language_id . "', text = '" .  $this->db->escape($product_attribute_description['text']) . "'");
            }
          }
        }
      }
    }

    // image
    if (isset($data['product_image'])
        && !empty($data['product_image'])
    ) {
      $this->db->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "'");

      foreach ($data['product_image'] as $product_image) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = '" . $this->db->escape($product_image['image']) . "', sort_order = '" . (int)$product_image['sort_order'] . "'");
      }
    }

    // product categories
    if (isset($data['product_category'])
        && !empty($data['product_category'])
    ) {
      $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

      foreach ($data['product_category'] as $category_id) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
      }
    }

    // main category
    if(isset($data['main_category_id'])
      && $data['main_category_id'] > 0
    ) {
      $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "' AND category_id = '" . (int)$data['main_category_id'] . "'");
      $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$data['main_category_id'] . "', main_category = 1");
    }
    elseif (isset($data['product_category'][0])) {
      $this->db->query("UPDATE " . DB_PREFIX . "product_to_category SET main_category = 1 WHERE product_id = '" . (int)$product_id . "' AND category_id = '" . (int)$data['product_category'][0] . "'");
    }

    // seo url
    // oc3 uses its own `product_seo_url`, we use keyword for backward compatibility
    if (isset($data['keyword'])) {
      $seo_table = 'url_alias';

      if ($this->isOpencart3()) {
        $seo_table = 'seo_url';
      }

      $this->db->query("DELETE FROM " . DB_PREFIX . $seo_table . " WHERE query = 'product_id=" . (int)$product_id . "'");

      if ($data['keyword']) {
        $this->db->query("INSERT INTO " . DB_PREFIX . $seo_table . " SET query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape($data['keyword']) . "'");
      }
    }

    $this->cache->delete('product');
  }

  /*
    Remove all products and related data
  */
  public function clearProducts () {
    $this->db->query("DELETE FROM " . DB_PREFIX . "product");
    $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute");
    $this->db->query("DELETE FROM " . DB_PREFIX . "product_description");
    $this->db->query("DELETE FROM " . DB_PREFIX . "product_discount");
    $this->db->query("DELETE FROM " . DB_PREFIX . "product_filter");
    $this->db->query("DELETE FROM " . DB_PREFIX . "product_image");
    $this->db->query("DELETE FROM " . DB_PREFIX . "product_option");
    $this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value");
    $this->db->query("DELETE FROM " . DB_PREFIX . "product_related");
    $this->db->query("DELETE FROM " . DB_PREFIX . "product_reward");
    $this->db->query("DELETE FROM " . DB_PREFIX . "product_special");
    $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category");
    $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_download");
    $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_layout");
    $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store");
    $this->db->query("DELETE FROM " . DB_PREFIX . "product_recurring");
    $this->db->query("DELETE FROM " . DB_PREFIX . "review");

    if ($this->isOpencart3()) {
      $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url");
    }
    else {
      $this->db->query("DELETE FROM " . DB_PREFIX . "url_alias");
    }

    $this->db->query("DELETE FROM " . DB_PREFIX . "coupon_product");
  }

  /* MANUFACTURER METHODS */

  /*
    Update existing manufacturer or create new
  */
  private function importManufacturer ($fields) {
    $this->load->model('catalog/manufacturer');

    $id = 0;

    if (isset($fields['name']) && !empty($fields['name'])) {
      $id = $this->db->query('SELECT IFNULL((SELECT manufacturer_id FROM '. DB_PREFIX .'manufacturer WHERE name LIKE "' . $this->db->escape($fields['name']) . '" LIMIT 1), 0) AS `id`')->row['id'];
    }
    else {
      $this->writeLog('Manufacturer [' . $fields['name'] . '] has wrong name on [' . $this->csv_row_num . '], so skipping...', 'warn');
      return 0;
    }

    /*
      If need check before insert and checking successfull -> update or delete
    */
    if (!$this->checkBeforeInsert
        || $this->checkerValue === ($id > 0)
    ) {
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
    $seo_url_table = 'url_alias';

    if ($this->isOpencart3()) {
      $seo_url_table = 'seo_url';
    }

    $this->db->query("DELETE FROM " . DB_PREFIX . "manufacturer");
    $this->db->query("DELETE FROM " . DB_PREFIX . "manufacturer_to_store");
    $this->db->query("DELETE FROM " . DB_PREFIX . $seo_url_table . " WHERE query LIKE 'manufacturer_id=%'")
    ;
  }

  /* ATTRIBUTE PARSERS METHODS */

  /*
    Parser list getter
  */
  public function getAttributeParsers () {
    return $this->attributeParsers;
  }

  /*
    Shortcut to create and check parser method name
  */
  private function getParserMethodName ($name) {
    $method = 'parser_' . $name;
    if (method_exists($this, $method)) {
      return $method;
    }

    return false;
  }

  /*
    Insert or update and return attribute_group id by name
  */
  private function createOrUpdateAttributeGroup ($name) {
    $this->load->model('catalog/attribute_group');

    $id = $this->db->query('SELECT IFNULL((SELECT attribute_group_id FROM ' . DB_PREFIX . 'attribute_group_description WHERE `name` LIKE "' . $this->db->escape($name) . '" LIMIT 1), 0) AS id')->row['id'];

    $data = array(
      'sort_order' => 0,
      'attribute_group_description' => array()
    );
    $data['attribute_group_description'][$this->language_id] = array(
      'name' => $name
    );

    if (!$id) {
      $id = $this->model_catalog_attribute_group->addAttributeGroup($data);
    }
    else {
      $this->model_catalog_attribute_group->editAttributeGroup($id, $data);
    }

    return $id;
  }

  /*
    Insert or update attribute and return id by attribute obj
    [
      group => ATTRIBUTE_GROUP_ID,
      name  => ATTRIBUTE_NAME
    ]
  */
  private function createOrUpdateAttribute ($attribute) {
    $this->load->model('catalog/attribute');

    $id = $this->db->query('SELECT IFNULL((SELECT attribute_id FROM ' . DB_PREFIX . 'attribute_description WHERE `name` LIKE "' . $this->db->escape($attribute['name']) . '" LIMIT 1), 0) AS id')->row['id'];

    $data = array(
      'attribute_group_id' => $attribute['group'],
      'sort_order' => 0,
      'attribute_description' => array()
    );

    $data['attribute_description'][$this->language_id] = array(
      'name' => $attribute['name']
    );

    if (!$id) {
      $id = $this->model_catalog_attribute->addAttribute($data);
    }
    else {
      $this->model_catalog_attribute->editAttribute($id, $data);
    }

    return $id;
  }

  /*
    Create options list, fill default values and validate
  */
  private function normalizeParser ($parser) {
    $valid = false;
    $parserMethod = false;

    if (isset($parser['name'])
        && isset($parser['defaultGroup'])
        && isset($this->attributeParsers[$parser['name']])
    ) {
      $parserMethod = $this->getParserMethodName($parser['name']);

      if ($parserMethod) {
        $parserDescription = $this->attributeParsers[$parser['name']];

        if (!empty($parserDescription['options'])
            && !empty($parser['options'])
        ) {
          foreach ($parserDescription['options'] as $name => $option) {
            if (isset($parser['options'][$name])
                && !empty($parser['options'][$name])
            ) {
              $valid = true;
              continue;
            }
            elseif (isset($option['default'])) {
              $parser['options'][$name] = $option['default'];
              $valid = true;
            }
            else {
              $this->writeLog('[OPTION_ERROR] (' . $name . ') is not presented on [' . $this->csv_row_num . ']!', 'warn');

              $valid = false;
            }
          }
        }
        else {
          $parser['options'] = array();
        }
      }
    }

    return array(
      $valid,
      $parser,
      $parserMethod
    );
  }

  /*
    Call selected attribute parser and prepare data
  */
  public function parseAttributes ($profile, $atts) {
    $result = array();

    if (isset($profile['attributeParser'])) {

      $parser = $profile['attributeParser'];
      $parserOptions = isset($profile['attributeParserData'][$parser]) ? $profile['attributeParserData'][$parser] : array();

      $parserObj = array(
        'name' => $parser,
        'options' => $parserOptions,
        'defaultGroup'=> $profile['defaultAttributesGroup'],
        'CSVFieldIdx' => isset($profile['attributesCSVField']) ? $profile['attributesCSVField'] : null
      );

      list ($valid, $parser, $parserMethod) = $this->normalizeParser($parserObj);

      if ($valid && $parserMethod) {
        $attributes = $this->{$parserMethod}($parser, $atts);
        foreach ($attributes as $key => $attribute) {
          $attributes[$key]['attribute_id'] = $this->createOrUpdateAttribute($attribute);
        }
        return $attributes;
      }
    }

    return $result;
  }

  private function parser_column ($parser, $atts) {
    $result = array();

    try {
      $options = $parser['options']['columns'];
      $group_id = $this->createOrUpdateAttributeGroup($parser['defaultGroup']);

      foreach ($options as $option) {
        $csvIdx = $option['field'];
        $name = $option['name'];

        if (!isset($atts[$csvIdx]) || trim($atts[$csvIdx]) === '') {
          continue;
        }

        $result[] = array(
          'name' => $name,
          'value'=> $atts[$csvIdx],
          'group'=> $group_id
        );
      }
    }
    catch (Exception $e) {
      $this->writeLog('Column parser error [' . $e->getMessage() . '] on [' . $this->csv_row_num . ']', 'error');
    }

    return $result;
  }

  /*
    Advantshop attributes format parser
  */
  private function parser_advantshop ($parser, $atts) {
    $result = array();
    $attributesIdx = $parser['CSVFieldIdx'];

    if (isset($parser['options'])
        && !empty($parser['options'])
        && isset($parser['defaultGroup'])
        && isset($atts[$attributesIdx])
    ) {
      $options = $parser['options'];

      $keyValueDelimiter = $options['keyvalue_delimiter'];
      $entriesDelimiter = $options['entries_delimiter'];

      $entries = array_filter(explode($entriesDelimiter, $atts[$attributesIdx]));

      $group_id = $this->createOrUpdateAttributeGroup($parser['defaultGroup']);

      $result = array();

      foreach ($entries as $entry) {
        list ($key, $value) = explode($keyValueDelimiter, $entry);
        $result[] = array(
          'name' => $key,
          'value' => $value,
          'group' => $group_id
        );
      }
    }

    return $result;
  }
  /* CUSTOM ATTRIBUTE PARSERS */

  /* END CUSTOM ATTRIBUTE PARSERS */

  /*
    Find attribute groups by name (fuzzy)
  */
  public function findGroups ($name) {
    $sql = "SELECT * FROM " . DB_PREFIX . "attribute_group ag LEFT JOIN " . DB_PREFIX . "attribute_group_description agd ON (ag.attribute_group_id = agd.attribute_group_id) WHERE agd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND agd.name LIKE '" . $this->db->escape($name) . "%' LIMIT 10";

    $query = $this->db->query($sql);

    return $query->rows;

  }

}