<?php

class ControllerExtensionModuleFocCsv extends Controller {

  const CONFIG_PROFILES = 'fo_csv_profiles';

  public function install () {
    $this->load->model('extension/module/foc_csv_common');
    $this->load->model('extension/module/foc_csv');
    $this->load->model('extension/module/foc_csv_exporter');

    // Remove unnecessary template version
    $templatePath = DIR_APPLICATION . 'view/template/extension/module/';
    $viewFile = 'foc_csv.twig';

    if ($this->model_extension_module_foc_csv_common->isOpencart3()) {
      $viewFile = 'foc_csv.php';
    }

    if (is_file($templatePath . $viewFile)) {
      unlink($templatePath . $viewFile);
    }

    $this->model_extension_module_foc_csv->install();
    $this->model_extension_module_foc_csv_exporter->install();
  }

  private function sendOk ($message = '') {
    echo json_encode(array(
      'status' => 'ok',
      'message' => $message
    ), JSON_HEX_AMP);
    die;
  }

  private function sendFail ($message = '') {
    echo json_encode(array(
      'status' => 'fail',
      'message' => $message
    ));
    die;
  }

  private function getTokenName () {
    $this->load->model('extension/module/foc_csv_common');
    if ($this->model_extension_module_foc_csv_common->isOpencart3()) {
      return 'user_token';
    }
    return 'token';
  }

  private function getToken () {
    return $this->session->data[$this->getTokenName()];
  }

  private function createUrl ($path) {
    return $this->url->link($path, $this->getTokenName() . '=' . $this->getToken(), 'ssl');
  }

  public function index () {
    $data = array();

    $this->language->load('extension/module/foc_csv');
    $this->document->setTitle($this->language->get('heading_title'));
    $data['heading_title'] = $this->language->get('heading_title');

    $data['foc_app_preload_title'] = $this->language->get('foc_app_preload_title');
    $data['foc_app_preload_description'] = $this->language->get('foc_app_preload_description');

    $data['tokenName'] = $this->getTokenName();
    $data['token'] = $this->getToken();
    $data['baseRoute'] = 'extension/module/foc_csv';
    $data['baseUrl'] = $this->url->link('');

    $this->load->model('extension/module/foc_csv_common');
    $this->load->model('extension/module/foc_csv');
    $this->load->model('extension/module/foc_csv_exporter');
    $this->load->model('setting/store');
    $this->load->model('localisation/language');
    $this->load->model('localisation/stock_status');

    $data['language'] = $this->model_extension_module_foc_csv_common->getLanguageCode();

    $data['breadcrumbs'] = $this->breadcrumbs();

    $this->document->addStyle('view/javascript/foc_csv/css/app.css');
    $this->document->addScript('view/javascript/foc_csv/js/manifest.js', 'foc_csv_js');
    $this->document->addScript('view/javascript/foc_csv/js/vendor.js', 'foc_csv_js');
    $this->document->addScript('view/javascript/foc_csv/js/app.js', 'foc_csv_js');

    $data['header'] = $this->load->controller('common/header');
    $data['footer'] = $this->load->controller('common/footer');
    $data['column_left'] = $this->load->controller('common/column_left');

    $data['scripts'] = $this->document->getScripts('foc_csv_js');

    $common = array();
    $importer = array();
    $exporter = array();

    $data['foc_version'] = $this->model_extension_module_foc_csv->getVersion();

    $importer['profiles'] = $this->model_extension_module_foc_csv->loadProfiles();
    $importer['keyFields'] = $this->model_extension_module_foc_csv->getKeyFields();
    $common['encodings'] = array('UTF8', 'cp1251');
    $common['dbFields'] = $this->model_extension_module_foc_csv->getDbFields();

    $exporter['profiles'] = $this->model_extension_module_foc_csv_exporter->loadProfiles();

    /* stores */
    $common['stores'] = array();
    $common['stores'][] = array(
      'name' => $this->config->get('config_name'),
      'id'   => $this->config->get('config_store_id')
    );

    $stores = $this->model_setting_store->getStores();
    foreach ($stores as $store) {
      $common['stores'][] = array(
        'name' => $store['name'],
        'id'   => $store['store_id']
      );
    }

    /* available statuses */
    $common['stock_statuses'] = array();
    $statuses = $this->model_localisation_stock_status->getStockStatuses();

    foreach ($statuses as $status) {
      $common['stock_statuses'][] = array(
        'id' => $status['stock_status_id'],
        'name' => $status['name']
      );
    }

    $common['statuses'] = array(
      array(
        'id' => 0,
        'name' => $this->language->get('text_disabled')
      ),
      array(
        'id' => 1,
        'name' => $this->language->get('text_enabled')
      )
    );

    $common['languages'] = array();
    $languages = $this->model_localisation_language->getLanguages();
    foreach ($languages as $lang) {
      $common['languages'][] = array(
        'name' => $lang['name'],
        'id'   => $lang['language_id']
      );
    }

    $importer['attributeParsers'] = $this->model_extension_module_foc_csv->getAttributeParsers();

    $data['initial'] = json_encode(array(
      'importer' => $importer,
      'exporter' => $exporter,
      'common' => $common
    ));

    return $this->response->setOutput($this->load->view('extension/module/foc_csv', $data));
  }

  /*
    Load profile settings from DB
  */
  public function loadProfile () {
    $type = null;

    if (isset($this->request->get['type'])) {
      $type = $this->request->get['type'];
    }
    else {
      $this->sendFail('Profile type not specified!');
    }

    $this->load->model('extension/module/foc_csv_common');

    $name = 'default';

    if (isset($this->request->get['profile'])) {
      $name = $this->request->get['profile'];
    }

    $profile = null;

    if ($type === 'importer') {
      $this->load->model('extension/module/foc_csv');
      $profile = $this->extension_module_foc_csv->loadProfile($name);
    }
    else {
      $this->load->model('extension/module/foc_csv_exporter');
      $profile = $this->extension_module_foc_csv_exporter->loadProfile($name);
    }

    if (is_null($profile)) {
      $this->sendFail('Profile [' . $name . '] not found!');
    }

    echo json_encode($profile);
    die;
  }

  /*
    Save profile to DB
  */
  public function saveProfile () {
    $type = null;
    if (isset($this->request->get['type'])) {
      $type = $this->request->get['type'];
    }
    else {
      $this->sendFail('Profile type not specified!');
    }

    if ($this->request->server['REQUEST_METHOD'] == 'POST') {
      $json = json_decode(file_get_contents('php://input'), true);

      if (isset($json['name']) && isset($json['profile'])) {
        $this->load->model('extension/module/foc_csv_common');

        $name = $json['name'];
        $profile = $json['profile'];

        if ($type == 'importer') {
          $this->load->model('extension/module/foc_csv');
          $this->model_extension_module_foc_csv->setProfile($name, $profile);
        }
        else {
          $this->load->model('extension/module/foc_csv_exporter');
          $this->model_extension_module_foc_csv_exporter->setProfile($name, $profile);
        }

        $this->sendOk();
      }

      $this->sendFail('No required data present!');
    }
  }

  public function saveProfiles () {
    $type = null;
    if (isset($this->request->get['type'])) {
      $type = $this->request->get['type'];
    }
    else {
      $this->sendFail('Profile type not specified!');
    }

    if ($this->request->server['REQUEST_METHOD'] == 'POST') {
      $json = json_decode(file_get_contents('php://input'), true);

      if (isset($json['profiles'])) {
        $this->load->model('extension/module/foc_csv_common');
      }
      else {
        $this->sendFail('Profiles to save is not presented!');
      }

      $profiles = array();

      if ($type == 'importer') {
        $this->load->model('extension/module/foc_csv');
        $this->model_extension_module_foc_csv->saveProfiles($json['profiles']);
        $profiles = $this->model_extension_module_foc_csv->loadProfiles();
      }
      else {
        $this->load->model('extension/module/foc_csv_exporter');
        $this->model_extension_module_foc_csv_exporter->saveProfiles($json['profiles']);
        $profiles = $this->model_extension_module_foc_csv_exporter->loadProfiles();
      }

      $this->sendOk(json_encode($profiles));
    }

    $this->sendFail();
  }

  public function export () {
    if (isset($_POST['profile-json'])) {
      $this->load->model('extension/module/foc_csv_common');
      $this->load->model('extension/module/foc_csv_exporter');

      $profile = $this->model_extension_module_foc_csv_exporter->fillProfileEmptyValues(json_decode($_POST['profile-json'], true));

      $key = $this->model_extension_module_foc_csv_exporter->prepareUploadPath();
      $exportFile = $this->model_extension_module_foc_csv_exporter->getExportCsvFilePath($key);
      $exportImagesFile = '';

      if ($profile['createImagesZIP']) {
        $exportImagesFile = $this->model_extension_module_foc_csv_exporter->getExportImagesZipFilePath($key);
      }

      // put csv headers if need
      if ($profile['csvHeader']) {
        $csv_fid = fopen($exportFile, 'w');
        $headers = array();
        foreach ($profile['bindings'] as $binding) {
          $headers[] = $binding['header'];
        }

        fputcsv($csv_fid, $headers, $profile['csvFieldDelimiter']);
        fclose($csv_fid);
      }

      $total = $this->model_extension_module_foc_csv_exporter->getProductTotal($profile);

      $export_url = $this->createUrl('extension/module/foc_csv/exportPart');

      $this->sendOk(array(
        'total' => $total,
        'key' => $key,
        'exportUrl' => html_entity_decode($export_url),
        'csvFileUrl' => $this->model_extension_module_foc_csv_exporter->pathToUrl($exportFile),
        'imagesZipUrl' => $this->model_extension_module_foc_csv_exporter->pathToUrl($exportImagesFile),
        'position' => 0
      ));
    }
  }

  public function exportPart () {
    if ($this->request->server['REQUEST_METHOD'] == 'POST') {
      $json = json_decode(file_get_contents('php://input'), true);

      $this->load->model('extension/module/foc_csv_common');
      $this->load->model('extension/module/foc_csv_exporter');

      if (isset($json['position']) && isset($json['key']) && isset($json['profile'])) {
        $offset = $json['position'];
        $profile = $json['profile'];
        $key = $json['key'];
        $errors = isset($json['errors']) ? intval($json['errors']) : 0;
        $limit = $profile['entriesPerQuery'];

        $exportFile = $this->model_extension_module_foc_csv_exporter->getExportCsvFilePath($key);

        $exportable = $this->model_extension_module_foc_csv_exporter->export($profile, $offset, $limit);

        $csv_fid = fopen($exportFile, 'a');

        $charset = strtolower($this->model_extension_module_foc_csv_exporter->getDBCharset());
        $encoding = strtolower($profile['encoding']);

        foreach ($exportable as $csvLine) {
          // try change file encoding
          if ($charset !== $encoding) {
            $this->model_extension_module_foc_csv_exporter->writeLog('Trying to convert character encoding from [' . $encoding . '] to [' . $charset . ']');

            if (!function_exists('iconv')) {
              $this->model_extension_module_foc_csv_exporter->writeLog('Please install iconv or convert csv file to [' . $charset . ']', 'error');
              $this->sendFail('Please install iconv or convert csv file to [' . $charset . ']');
            }

            foreach ($csvLine as $part_idx => $part) {
              $csvLine[$part_idx] = iconv($charset, $encoding . '//TRANSLIT//IGNORE', $part);
            }
          }

          fputcsv($csv_fid, $csvLine, $profile['csvFieldDelimiter']);
        }
        fclose($csv_fid);

        if ($profile['createImagesZIP'] && $this->model_extension_module_foc_csv_exporter->hasCollectedImages()) {
          $exportImagesFile = $this->model_extension_module_foc_csv_exporter->getExportImagesZipFilePath($key);
          $zip = new ZipArchive();
          $zip->open($exportImagesFile, ZipArchive::CREATE);

          foreach($this->model_extension_module_foc_csv_exporter->getCollectedImages() as $image) {
            $path = DIR_IMAGE . $image;
            $zip->addFile($path, $image);
          }

          $zip->close();
        }

        $position = $offset + $limit;

        $this->sendOk(array(
          'key' => $key,
          'position' => $position,
          'errors' => $errors
        ));
      }
    }
  }

  /*
    Upload files and starting import
  */
  public function import () {
    if (!empty($_FILES) && isset($_POST['profile-json'])) {
      $this->load->model('extension/module/foc_csv_common');
      $this->load->model('extension/module/foc_csv');

      // first call - generate upload key
      $key = $this->model_extension_module_foc_csv->prepareUploadPath();
      $importFile = $this->model_extension_module_foc_csv->getImportCsvFilePath($key);
      $imagesFile = $this->model_extension_module_foc_csv->getImportImagesZipPath($key);

      $profile = json_decode($_POST['profile-json'], true);

      // csv file operations
      if (isset($_FILES['csv-file'])) {

        $this->model_extension_module_foc_csv->writeLog('CSV file uploaded');

        $charset = strtolower($this->model_extension_module_foc_csv->getDBCharset());
        $encoding = strtolower($profile['encoding']);

        // try change file encoding
        if ($charset !== $encoding) {
          $this->model_extension_module_foc_csv->writeLog('Trying to convert character encoding from [' . $encoding . '] to [' . $charset . ']');

          if (!function_exists('iconv')) {
            $this->model_extension_module_foc_csv->writeLog('Please install iconv or convert csv file to [' . $charset . ']', 'error');
            $this->sendFail('Please install iconv or convert csv file to [' . $charset . ']');
          }

          $src = fopen($_FILES['csv-file']['tmp_name'], 'r');
          $out = fopen($importFile, 'w');

          while ($line = fgets($src)) {
            fwrite($out, iconv($encoding, $charset . '//TRANSLIT//IGNORE', $line));
          }

          fclose($src);
          fclose($out);

          $this->model_extension_module_foc_csv->writeLog('File [' . $src . '] encoded successfully!');
        }
        else {
          move_uploaded_file($_FILES['csv-file']['tmp_name'], $importFile);
        }
      }
      // images zip - save and unpack
      if (isset($_FILES['images-zip'])) {
        $this->model_extension_module_foc_csv->writeLog('Images ZIP file uploaded');

        move_uploaded_file($_FILES['images-zip']['tmp_name'], $imagesFile);

        $zip = new ZipArchive();
        $can_open = $zip->open($imagesFile);

        if ($can_open === true) {
          $zip->extractTo($this->model_extension_module_foc_csv->getImportImagesPath($key));
          $this->model_extension_module_foc_csv->moveUploadedImages($key);
          $zip->close();
        }
      }

      // read csv bytes count
      $csv_file = new SplFileObject($importFile, 'r');
      $csv_file->seek(PHP_INT_MAX);
      $csv_total = $csv_file->ftell();

      $import_url = $this->createUrl('extension/module/foc_csv/importPart');

      // remove manufacturers if necessary
      if (isset($profile['removeManufacturersBeforeImport']) && $profile['removeManufacturersBeforeImport']) {
        $this->model_extension_module_foc_csv->writeLog('Clearing manufacturers table');
        $this->model_extension_module_foc_csv->clearManufacturers();
      }

      // removeOthers importMode handler:
      // before importData we remove all products from database
      // on importPart this mode === updateCreate mode
      if ($profile['importMode'] == 'removeOthers') {
        $this->model_extension_module_foc_csv->clearProducts();
      }

      $this->sendOk(array(
        'csvTotal' => $csv_total,
        'key' => $key,
        'importUrl' => html_entity_decode($import_url),
        // position - last ftell file position
        'position' => 0
      ));
    }
  }

  /*
    Import partition from CSV
  */
  public function importPart ($key = null, $position = null, $profile = null) {
    if ($key === null && $position === null && $profile === null) {
      if ($this->request->server['REQUEST_METHOD'] == 'POST') {
        $json = json_decode(file_get_contents('php://input'), true);

        $this->load->model('extension/module/foc_csv_common');
        $this->load->model('extension/module/foc_csv');

        if (isset($json['position']) && isset($json['key']) && isset($json['profile'])) {
          $import_key = $json['key'];
          $position = $json['position'];
          $errors = isset($json['errors']) ? intval($json['errors']) : 0;
          $lines = isset($json['lines']) ? $json['lines'] : 0;
          $profile = $json['profile'];

          $this->model_extension_module_foc_csv->writeLog('Import part start [' . $lines . ']');

          $profile = $this->model_extension_module_foc_csv->fillProfileEmptyValues($profile);

          $this->model_extension_module_foc_csv->setUploadKey($import_key);

          $skipLines = $profile['skipLines'];

          if (!$profile['csvWithoutHeaders'] && $profile['csvHeadersLineNumber'] > $skipLines) {
            $skipLines = $profile['csvHeadersLineNumber'];
          }

          $delimiter = empty($profile['csvFieldDelimiter']) ? ';' : $profile['csvFieldDelimiter'];
          $importAtOnce = empty($profile['processAtStepNum']) ? 10 : $profile['processAtStepNum'];

          $mode = $profile['importMode'];
          $this->model_extension_module_foc_csv->setImportMode($mode);

          $key_field = $profile['keyField'];
          list($table, $key) = explode(':', $key_field);
          $this->model_extension_module_foc_csv->toggleKeyField($table, $key);

          $path = $this->model_extension_module_foc_csv->getImportCsvFilePath($import_key);
          $csv_fid = fopen($path, 'r');

          if ($position > 0) {
            fseek($csv_fid, $position);
          }

          $i = 0;

          while ($i < $importAtOnce && ($line = fgetcsv($csv_fid, 0, $delimiter)) !== false) {
            $i++;

            if (($lines + $i) <= $skipLines) {
              $this->model_extension_module_foc_csv->writeLog('Skip line:' . ($lines + $i) . ', lines to skip:' . $skipLines);
              continue;
            }
            // import stuff..
            try {
              if (!$this->model_extension_module_foc_csv->import($profile, $line, $lines + $i)) {
                $errors++;
              }
            }
            catch (Exception $e) {
              $this->model_extension_module_foc_csv->writeLog('Error on import at [' . ($lines + $i) . '] (' . $e->getMessage() . ')', 'error');
              $this->sendFail($e->getMessage());
            }
          }

          $position = ftell($csv_fid);
          fclose($csv_fid);

          $this->model_extension_module_foc_csv->writeLog('Import part end at [' . ($lines + $i) . ']');

          $this->sendOk(array(
            'key' => $import_key,
            'position' => $position,
            'errors' => $errors,
            'lines' => $i + $lines
          ));
        }
      }
    }

    $this->model_extension_module_foc_csv->writeLog('Missing required fields! Fields are $key: [' . $key .'], $position: [' . $position . '] and $profile: [' . print_r($profile, true) . ']', 'error');
    $this->sendFail();
  }

  /*
    Autocomplete to choose attributes default group
  */
  public function attributesGroupAutocomplete () {
    $this->load->model('catalog/attribute_group');
    $groups = $this->model_catalog_attribute_group->getAttributeGroups();
    $response = array();
    foreach ($groups as $group) {
      $response[] = array(
        'name' => $group['name']
      );
    }

    echo json_encode($response);
    die;
  }

  private function breadcrumbs () {
    $breadcrumbs = array();

    $breadcrumbs[] = array(
      'text'      => $this->language->get('text_home'),
      'href'      => $this->createUrl('common/home'),
      'separator' => false
    );
    $breadcrumbs[] = array(
      'text'      => $this->language->get('text_extension'),
      'href'      => $this->createUrl('extension/extension'),
      'separator' => ' :: '
    );
    $breadcrumbs[] = array(
      'text'      => $this->language->get('heading_title'),
      'href'      => $this->createUrl('extension/module/foc_csv'),
      'separator' => ' :: '
    );

    return $breadcrumbs;
  }

}