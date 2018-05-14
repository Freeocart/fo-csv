<?php

class ControllerExtensionModuleFocCsv extends Controller {

  const CONFIG_PROFILES = 'fo_csv_profiles';

  public function install () {
    // Remove unnecessary template version
    $templatePath = DIR_APPLICATION . 'view/template/extension/module/';
    $viewFile = 'foc_csv.twig';

    if ($this->isOpencart3()) {
      $viewFile = 'foc_csv.php';
    }

    if (is_file($templatePath . $viewFile)) {
      unlink($templatePath . $viewFile);
    }

    $this->load->model('extension/module/foc_csv');
    $this->model_extension_module_foc_csv->install();
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
    $this->load->model('extension/module/foc_csv');

    if ($this->model_extension_module_foc_csv->isOpencart3()) {
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

    $data['tokenName'] = $this->getTokenName();
    $data['token'] = $this->getToken();
    $data['baseRoute'] = 'extension/module/foc_csv';
    $data['baseUrl'] = $this->url->link('');
    $data['language'] = $this->language->get('code');

    $this->load->model('extension/module/foc_csv');
    $this->load->model('setting/store');
    $this->load->model('localisation/language');
    $this->load->model('localisation/stock_status');

    $data['breadcrumbs'] = $this->breadcrumbs();

    $this->document->addStyle('view/javascript/foc_csv/css/app.css');
    $this->document->addScript('view/javascript/foc_csv/js/manifest.js', 'foc_csv_js');
    $this->document->addScript('view/javascript/foc_csv/js/vendor.js', 'foc_csv_js');
    $this->document->addScript('view/javascript/foc_csv/js/app.js', 'foc_csv_js');

    $data['header'] = $this->load->controller('common/header');
    $data['footer'] = $this->load->controller('common/footer');
    $data['column_left'] = $this->load->controller('common/column_left');

    $data['scripts'] = $this->document->getScripts('foc_csv_js');

    $initial = array();
    $initial['profiles'] = $this->model_extension_module_foc_csv->loadProfiles();
    $initial['keyFields'] = $this->model_extension_module_foc_csv->getKeyFields();
    $initial['encodings'] = array('UTF8', 'cp1251');
    $initial['dbFields'] = $this->model_extension_module_foc_csv->getDbFields();

    /* stores */
    $initial['stores'] = array();
    $initial['stores'][] = array(
      'name' => $this->config->get('config_name'),
      'id'   => $this->config->get('config_store_id'),
      'current' => true
    );

    $stores = $this->model_setting_store->getStores();
    foreach ($stores as $store) {
      $initial['stores'][] = array(
        'name' => $store['name'],
        'id'   => $store['store_id']
      );
    }

    /* available statuses */
    $initial['stock_statuses'] = array();
    $statuses = $this->model_localisation_stock_status->getStockStatuses();

    foreach ($statuses as $status) {
      $initial['stock_statuses'][] = array(
        'id' => $status['stock_status_id'],
        'name' => $status['name']
      );
    }

    $initial['statuses'] = array(
      array(
        'id' => 0,
        'name' => $this->language->get('text_disabled')
      ),
      array(
        'id' => 1,
        'name' => $this->language->get('text_enabled')
      )
    );

    $initial['languages'] = array();
    $languages = $this->model_localisation_language->getLanguages();
    foreach ($languages as $lang) {
      $initial['languages'][] = array(
        'name' => $lang['name'],
        'id'   => $lang['language_id']
      );
    }

    $initial['attributeParsers'] = $this->model_extension_module_foc_csv->getAttributeParsers();

    $data['initial'] = json_encode($initial);

    return $this->response->setOutput($this->load->view('extension/module/foc_csv', $data));
  }

  /*
    Load profile settings from DB
  */
  public function loadProfile () {
    $this->load->model('extension/module/foc_csv');

    $name = 'default';

    if (isset($this->request->get['profile'])) {
      $name = $this->request->get['profile'];
    }

    $profile = $this->extension_module_foc_csv->loadProfile($name);

    echo json_encode($profile);
    die;
  }

  /*
    Save profile to DB
  */
  public function saveProfile () {
    if ($this->request->server['REQUEST_METHOD'] == 'POST') {
      $json = json_decode(file_get_contents('php://input'), true);

      if (isset($json['name']) && isset($json['profile'])) {
        $this->load->model('extension/module/foc_csv');
        $name = $json['name'];
        $profile = $json['profile'];

        $this->model_extension_module_foc_csv->setProfile($name, $profile);
        $this->sendOk();
      }

      $this->sendFail();
    }
  }

  public function saveProfiles () {
    if ($this->request->server['REQUEST_METHOD'] == 'POST') {
      $json = json_decode(file_get_contents('php://input'), true);

      if (isset($json['profiles'])) {
        $this->load->model('extension/module/foc_csv');
        $this->model_extension_module_foc_csv->saveProfiles($json['profiles']);
      }

      $profiles = $this->model_extension_module_foc_csv->loadProfiles();

      $this->sendOk(json_encode($profiles));
    }

    $this->sendFail();
  }

  /*
    Upload files and starting import
  */
  public function import () {
    if (!empty($_FILES) && isset($_POST['profile-json'])) {
      $this->load->model('extension/module/foc_csv');

      // первый вызов и заливка файлов - генерим ключ
      $key = $this->model_extension_module_foc_csv->prepareImportPath();
      $importFile = $this->model_extension_module_foc_csv->getImportCsvFilePath($key);
      $imagesFile = $this->model_extension_module_foc_csv->getImportImagesZipPath($key);

      $profile = json_decode($_POST['profile-json'], true);

      // цсвэшник - просто перемещаем
      if (isset($_FILES['csv-file'])) {

        $charset = strtolower($this->model_extension_module_foc_csv->getDBCharset());
        $encoding = strtolower($profile['encoding']);

        // try change file encoding
        if ($charset !== $encoding) {
          if (!function_exists('iconv')) {
            $this->sendFail('Please install iconv or convert csv file to [' . $charset . ']');
          }

          $src = fopen($_FILES['csv-file']['tmp_name'], 'r');
          $out = fopen($importFile, 'w');

          while ($line = fgets($src)) {
            fwrite($out, iconv($encoding, $charset, $line));
          }

          fclose($src);
          fclose($out);
        }
        else {
          move_uploaded_file($_FILES['csv-file']['tmp_name'], $importFile);
        }
      }
      // архив картинок - перемещаем и распаковываем
      if (isset($_FILES['images-zip'])) {
        move_uploaded_file($_FILES['images-zip']['tmp_name'], $imagesFile);

        // unzip...unset
        // todo: add zip check content before unzipping!
        $zip = new ZipArchive();
        $can_open = $zip->open($imagesFile);

        if ($can_open) {
          $zip->extractTo($this->model_extension_module_foc_csv->getImportImagesPath($key));
          $zip->close();
        }
      }

      // читаем количество строк в csv
      // тут нужно будет что-то поумнее фигануть))
      // $csv_total = count(file($importFile,FILE_SKIP_EMPTY_LINES));
      $csv_file = new SplFileObject($importFile, 'r');
      // $csv_file->setFlags();
      $csv_file->seek(PHP_INT_MAX);
      $csv_total = $csv_file->ftell();

      $import_url = $this->createUrl('extension/module/foc_csv/importPart');
      //$this->url->link('extension/module/foc_csv/importPart', 'token=' . $this->session->data['token'], 'ssl');

      // remove manufacturers if necessary
      if (isset($profile['removeManufacturersBeforeImport']) && $profile['removeManufacturersBeforeImport']) {
        $this->model_extension_module_foc_csv->clearManufacturers();
      }

      // urlencode()
      // возвращаем данные на клиент и ожидаем запросы
      $this->sendOk(array(
        'csvTotal' => $csv_total,
        'key' => $key,
        'importUrl' => html_entity_decode($import_url),
        'position' => 0 // позиция на которой было закончено чтение в прошлой сессии (0 поскольку чтение еще не начато)
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

        $this->load->model('extension/module/foc_csv');

        if (isset($json['position']) && isset($json['key']) && isset($json['profile'])) {
          $import_key = $json['key'];
          $position = $json['position'];
          $lines = isset($json['lines']) ? $json['lines'] : 0;
          $profile = $json['profile'];

          $profile = $this->model_extension_module_foc_csv->fillProfileEmptyValues($profile);

          $this->model_extension_module_foc_csv->setImportKey($import_key);

          $skipLines = $profile['skipLines'];
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
              $this->model_extension_module_foc_csv->writeLog('[SKIP_LINE] Line:' . ($lines+$i) . ' To skip:' . $skipLines);
              continue;
            }
            // import stuff..
            try {
              $this->model_extension_module_foc_csv->import($profile, $line);
            }
            catch (Exception $e) {
              $this->sendFail($e->getMessage());
            }
          }

          $position = ftell($csv_fid);
          fclose($csv_fid);

          $this->sendOk(array(
            'key' => $import_key,
            'position' => $position,
            'lines' => $i + $lines
          ));
        }
      }
    }

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
			'href'      => $this->createUrl('common/home'),//url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => false
    );
    $breadcrumbs[] = array(
      'text'      => $this->language->get('text_extension'),
      'href'      => $this->createUrl('extension/extension'),//url->link('extension/extension', 'token=' . $this->session->data['token'], 'SSL'),
      'separator' => ' :: '
    );
		$breadcrumbs[] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->createUrl('extension/module/foc_csv'), //url->link('extension/module/foc_csv', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
    );

    return $breadcrumbs;
  }

}