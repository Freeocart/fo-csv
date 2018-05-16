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

  private $attributeParsers = array();

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
          'default' => '',
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
      'skipLines' => 1,
      'bindings' => new stdclass,
      'importMode' => 'updateCreate',
      'imagesImportMode' => 'add',
      'csvImageFieldDelimiter' => ';',
      'previewFromGallery' => true,
      'processAtStepNum' => 20,
      'removeCharsFromCategory' => '[]{}',
      'removeManufacturersBeforeImport' => false,
      // 'storeId' => $this->config->get('config_store_id'),
      // 'languageId' => $this->config->get('language_id'),
      'statusRewrites' => array(),
      'stockStatusRewrites' => array(),
      'downloadImages' => false,
      'attributeParser' => null,
      'store' => $this->config->get('config_store_id'),
      'language' => $this->config->get('config_language_id'),
      'attributeParserData' => array(),
      'skipLineOnEmptyFields' => array()
    );
  }

  /*
    DB Schema templates here
    This functions helping generate data for database
  */
  private function productDescriptionTemplate ($data = array()) {
    $tableData = array();
    $tableData[$this->language_id] = array_replace(array(
      'description' => '',
      'meta_title' => '',
      'tag' => '',
      'name' => '',
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

  /*
    Import entry point
  */
  public function import ($profile, $csv_row, $csv_row_num = 0) {

    $this->csv_row_num = $csv_row_num;

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

    $this->language_id = (int) $this->config->get('config_language_id');
    if (isset($profile['languageId'])) {
      $this->language_id = (int) $profile['languageId'];
    }

    $this->store_id = (int) $this->config->get('config_store_id');
    if (isset($profile['storeId'])) {
      $this->store_id = (int) $profile['storeId'];
    }

    $manufacturer_id = 0;
    if (isset($tablesData['manufacturer'])) {
      $manufacturer_id = $this->importManufacturer($tablesData['manufacturer']);

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

    $productData = $this->productTemplate($tablesData['product']);
    $productData['manufacturer_id'] = $manufacturer_id;

    if (!isset($tablesData['product_description'])) {
      $tablesData['product_description'] = $this->productDescriptionTemplate();
    }

    $productData['product_description'] = $this->productDescriptionTemplate($tablesData['product_description']);

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
    if (isset($profile['statusRewrites']) && in_array($productData['status'], $profile['statusRewrites'])) {
      $productData['status'] = array_search($productData['status'], $profile['statusRewrites']);
    }

    if (isset($profile['defaultStockStatus'])) {
      $productData['stock_status_id'] = $profile['defaultStockStatus'];
    }
    // stock_status rewrites processing
    if (isset($profile['stockStatusRewrites']) && in_array($productData['stock_status_id'], $profile['stockStatusRewrites'])) {
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

        $this->importGallery($tablesData['product_image'], $profile['csvImageFieldDelimiter'], $profile['downloadImages'], $setPreviewFromGallery, $product_id);
      }
    }

    /* IMPORT CATEGORIES */
    if (isset($tablesData['category_description'])) {

      $cleanCategoryNames = isset($profile['removeCharsFromCategory']) ? $profile['removeCharsFromCategory'] : '';

      $category_ids = $this->importProductCategories($tablesData['category_description'], $profile['categoryDelimiter'], $profile['categoryLevelDelimiter'], $cleanCategoryNames, $this->language_id, $this->store_id);

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
        $this->model_catalog_product->editProduct($id, $fields);
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
      return $this->model_catalog_product->addProduct($fields);
    }
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
		$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias");
		$this->db->query("DELETE FROM " . DB_PREFIX . "coupon_product");
  }

  /* MANUFACTURER METHODS */

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
      $this->writeLog('Manufacturer [' . $fields['name'] . '] has wrong name on [' . $this->csv_row_num . '], so skipping...', 'warn');
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

        if (!empty($parserDescription['options']) && !empty($parser['options'])) {
          foreach ($parserDescription['options'] as $name => $option) {
            if (isset($parser['options'][$name]) && !empty($parser['options'][$name])) {
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
      $options = json_decode($parser['options']['columns'], true);
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