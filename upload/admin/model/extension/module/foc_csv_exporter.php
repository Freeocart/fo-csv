<?php
/*
  Model for FOC CSV Exporter
*/
class ModelExtensionModuleFocCsvExporter extends ModelExtensionModuleFocCsvCommon {

  protected $exportPath = '';
  protected $csvExportFileName = 'export.csv';
  protected $imagesZipImportFileName = 'images.zip';

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