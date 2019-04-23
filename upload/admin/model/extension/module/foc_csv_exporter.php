<?php
/*
  Model for FOC CSV Exporter
*/
class ModelExtensionModuleFocCsvExporter extends ModelExtensionModuleFocCsvCommon {

  protected $exportPath = '';
  protected $csvExportFileName = 'export.csv';
  protected $imagesZipExportFileName = 'images.zip';

  protected $ocDefaultNestingDelimiter = '>';

  private $attributeEncoders = array();

  private static $foundImages = array();

  public function __construct ($registry) {
    parent::__construct($registry, 'exporter');

    $this->language->load('extension/module/foc_csv');
    $this->language->load('extension/module/foc_attribute_encoders');

    $this->attributeEncoders['advantshop'] = array(
      'title' => $this->language->get('encoder_advantshop'),
      'options' => array(
        'header_template' => array(
          'title' => $this->language->get('encoder_header_template'),
          'default' => $this->language->get('encoder_header_template_value')
        ),
        'keyvalue_delimiter' => array(
          'title' => $this->language->get('encoder_advantshop_keyvalue_delimiter'),
          'default' => ':'
        ),
        'entries_delimiter' => array(
          'title' => $this->language->get('encoder_advantshop_entries_delimiter'),
          'default' => ';'
        )
      )
    );

    $this->attributeEncoders['advantshop_grouped'] = array(
      'title' => $this->language->get('encoder_advantshop_grouped'),
      'options' => array(
        'header_template' => array(
          'title' => $this->language->get('encoder_header_template'),
          'default' => $this->language->get('encoder_header_template_value')
        ),
        'groupattrs_delimiter' => array(
          'title' => $this->language->get('encoder_advantshop_grouped_groupattr_delimiter'),
          'default' => '=>'
        ),
        'groups_delimiter' => array(
          'title' => $this->language->get('encoder_advantshop_grouped_groups_delimiter'),
          'default' => ','
        ),
        'keyvalue_delimiter' => array(
          'title' => $this->language->get('encoder_advantshop_keyvalue_delimiter'),
          'default' => ':'
        ),
        'entries_delimiter' => array(
          'title' => $this->language->get('encoder_advantshop_entries_delimiter'),
          'default' => ';'
        )
      )
    );
  }

  public function install () {
    parent::install();
  }

  /*
    FILE MANIPULATION METHODS
  */
  public function getExportCsvFilePath ($key) {
    $path = $this->getUploadPath($key);
    return $path . $this->csvExportFileName;
  }

  public function getExportImagesZipFilePath ($key) {
    $path = $this->getUploadPath($key);
    return $path . $this->imagesZipExportFileName;
  }

  public function getDefaultProfile () {
    return array(
      'entriesPerQuery' => 10,
      'encoding' => 'UTF8',
      'dumpParentCategories' => false,
      'categoriesNestingDelimiter' => '|',
      'categoriesDelimiter' => '\n',
      'exportImagesMode' => null,
      'createImagesZIP' => false,
      'csvFieldDelimiter' => ';',
      'csvHeader' => true,
      'galleryImagesDelimiter' => ';',
      'store' => $this->config->get('config_store_id'),
      'language' => $this->config->get('config_language_id'),
      'bindings' => array(),
      'exportWithStatus' => -1,
      'attributeEncoder' => null,
      'attributeEncoderData' => array(),
    );
  }

  /*
    convert table:field to table => [field, field]
  */
  public function exportSchema ($bindings) {
    $result = array();
    foreach ($bindings as $binding) {
      if (is_null($binding['dbField'])) {
        continue;
      }

      list ($table, $field) = explode(':', $binding['dbField']);

      if (!isset($result[$table])) {
        $result[$table] = array();
      }

      $result[$table][] = $field;
    }

    return $result;
  }

  /*
    Make csv lines by profile config and return
  */
  public function export ($profile, $offset = 0, $limit = 10) {

    $productIds = array_column($this->getProducts($profile, $offset, $limit), 'product_id');
    $preparedItems = array();
    $csvLines = array();

    foreach ($productIds as $id) {
      $preparedItems[] = $this->prepareData($id, $profile);
    }

    foreach ($preparedItems as $dataItem) {
      $csvLine = array();
      foreach ($profile['bindings'] as $idx => $binding) {
        if (is_null($binding['dbField'])) {
          continue;
        }
        list ($table, $field) = explode(':', $binding['dbField']);
        $separator = '';

        // categories separator
        if ($table === 'category_description' || $table === 'category') {
          $separator = $profile['categoriesDelimiter'];
        }
        // images separator
        if ($table === 'product_image') {
          $separator = $profile['galleryImagesDelimiter'];
        }

        if (isset($dataItem[$table][$field])) {
          $csvLine[$idx] = stripcslashes($dataItem[$table][$field]);
        }
        // multiple values line
        else if (isset($dataItem[$table]) && is_array($dataItem[$table])) {
          $csvLine[$idx] = '';
          foreach ($dataItem[$table] as $item) {
            if (isset($item[$field])) {
              $csvLine[$idx] .= $item[$field] . $separator;
            }
          }
          $csvLine[$idx] = stripcslashes(rtrim($csvLine[$idx], $separator));
        }
        else {
          $csvLine[$idx] = '';
        }
      }

      $csvLines[] = $csvLine;
    }

    return $csvLines;
  }

  /*
    Prepare structured data
  */
  public function prepareData ($primary, $profile) {

    $this->load->model('catalog/product');
    $this->load->model('catalog/category');
    $this->load->model('catalog/manufacturer');

    $schema = $this->exportSchema($profile['bindings']);

    $dumpParentCategories = $profile['dumpParentCategories'];
    $categoriesNestingDelimiter = $profile['categoriesNestingDelimiter'];

    $product = $this->model_catalog_product->getProduct($primary);
    $result = array(
      'product_id' => $primary,
      'product' => array()
    );

    // product data
    if (isset($schema['product']) && !empty($schema['product'])) {
      $result['product'] = array_intersect_key($product, array_flip($schema['product']));

      if (in_array('image', $schema['product'])
          && !is_null($result['product']['image'])
          && trim($result['product']['image']) != ''
      ) {
        $this->addCollectedImage($result['product']['image']);
      }

      unset($schema['product']);
    }

    // product description data
    if (isset($schema['product_description'])
        && !empty($schema['product_description'])
    ) {
      $descriptions = $this->model_catalog_product->getProductDescriptions($primary);
      $result['product_description'] = array_intersect_key($product, array_flip($schema['product_description']));
    }

    // prefetch categories
    $categoriesCache = array();

    if (isset($schema['category']) || isset($schema['category_description'])) {
      $cats = $this->model_catalog_product->getProductCategories($primary);

      foreach ($cats as $id) {
        $categoriesCache[] = $this->model_catalog_category->getCategory($id);
      }
    }

    // fill other schemas
    foreach ($schema as $table => $fields) {
      if (count($fields) > 0) {
        // export categories data
        if ($table === 'category' || $table === 'category_description') {

          if ($dumpParentCategories && !in_array('path', $fields)) {
            $fields[] = 'path';
          }

          if (!in_array('name', $fields)) {
            $fields[] = 'name';
          }

          $result[$table] = array();
          $categoriesData = array();

          foreach ($categoriesCache as $category) {
            $categoryItem = array_intersect_key($category, array_flip($fields));

            if ($dumpParentCategories && isset($categoryItem['path'])) {
              $path = str_replace('&nbsp;', '', $categoryItem['path']);

              $parents = array_map('trim', explode($this->ocDefaultNestingDelimiter, html_entity_decode($path)));
              // implode nesting values
              $name = implode($categoriesNestingDelimiter, $parents);
              $name .= $categoriesNestingDelimiter . $categoryItem['name'];

              $categoryItem['name'] = $name;
            }

            $categoriesData[] = $categoryItem;
          }

          $result[$table] = $categoriesData;

          continue;
        }
        // export manufacturer items
        if ($table === 'manufacturer') {
          $manufacturer = $this->model_catalog_manufacturer->getManufacturer($product['manufacturer_id']);
          $result[$table] = array_intersect_key($manufacturer, array_flip($fields));
          continue;
        }
        // export image items
        if ($table === 'product_image') {
          $images = $this->model_catalog_product->getProductImages($primary);
          $result[$table] = array();

          foreach ($images as $image) {
            $result[$table][] = array_intersect_key($image, array_flip($fields));

            if (in_array('image', $fields) && !empty($image['image'])) {
              $this->addCollectedImage($image['image']);
            }
          }

          continue;
        }
      }
    }

    return $result;
  }

  /*
    Images collector
    this stuff accumulates images that will be packed after csv lines processed and putted
  */
  public function addCollectedImage ($path) {
    self::$foundImages[] = $path;
  }

  public function hasCollectedImages () {
    return $this->getCollectedImagesCount() > 0;
  }

  public function getCollectedImagesCount () {
    return count(self::$foundImages);
  }

  public function getCollectedImages () {
    if ($this->hasCollectedImages()) {
      return self::$foundImages;
    }
    return array();
  }

  /*
    Select and return product ids by offset and limit
  */
  public function getProducts ($profile, $offset, $limit) {
    $sql = 'SELECT product_id FROM ' . DB_PREFIX . 'product ';

    if ((int)$profile['exportWithStatus'] !== -1) {
      $sql .= ' WHERE status = ' . (int)$profile['exportWithStatus'];
    }

    $sql .= ' LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;

    return $this->db->query($sql)->rows;
  }

  /*
    return total products count
  */
  public function getProductTotal ($profile) {
    $this->load->model('catalog/product');

    $filter = array();

    if (is_numeric($profile['exportWithStatus'])
        && in_array((int)$profile['exportWithStatus'], array(0, 1))
    ) {
      $filter['filter_status'] = (int)$profile['exportWithStatus'];
    }

    return $this->model_catalog_product->getTotalProducts($filter);
  }

  /* ATTRIBUTE ENCODERS METHODS */

  /*
    Encoder list getter
  */
  public function getAttributeEncoders () {
    return $this->attributeEncoders;
  }

}