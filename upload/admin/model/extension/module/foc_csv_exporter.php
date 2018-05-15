<?php
/*
  Model for FOC CSV Exporter
*/
class ModelExtensionModuleFocCsvExporter extends ModelExtensionModuleFocCsvCommon {

  protected $exportPath = '';
  protected $csvExportFileName = 'export.csv';
  protected $imagesZipImportFileName = 'images.zip';

  protected $ocDefaultNestingDelimiter = '>';

  public function __construct ($registry) {
    parent::__construct($registry, 'exporter');
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
      'galleryImagesDelimiter' => ',',
      'store' => $this->config->get('config_store_id'),
      'language' => $this->config->get('config_language_id'),
      'bindings' => array()
    );
  }

  /*
    convert table:field to table => [field, field]
  */
  public function exportSchema ($bindings) {
    $result = array();
    foreach ($bindings as $binding) {
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
      'product' => array()
    );

    // product data
    if (isset($schema['product']) && !empty($schema['product'])) {
      $result['product'] = array_intersect_key($product, array_flip($schema['product']));
      unset($schema['product']);
    }

    // product description data
    if (isset($schema['product_description']) && !empty($schema['product_description'])) {
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
          foreach ($images as $image) {
            $result[$table][] = array_intersect_key($image, array_flip($fields));
          }
          continue;
        }
      }
    }

    return $result;
  }

  /*
    Select and return product ids by offset and limit
  */
  public function getProducts ($offset, $limit) {
    $sql = 'SELECT product_id FROM ' . DB_PREFIX . 'product LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
    return $this->db->query($sql)->rows;
  }

  /*
    return total products count
  */
  public function getProductTotal () {
    $this->load->model('catalog/product');
    return $this->model_catalog_product->getTotalProducts();
  }

}