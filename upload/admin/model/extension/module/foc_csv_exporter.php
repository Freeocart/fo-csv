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

  public function export () {
    die('test');
  }

}