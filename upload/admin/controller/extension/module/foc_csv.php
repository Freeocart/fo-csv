<?php

class ControllerExtensionModuleFocCsv extends Controller {

  const CONFIG_PROFILES = 'fo_csv_profiles';

  public function install () {
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

  public function index () {
    $data = array();

    $this->language->load('extension/module/foc_csv');
    $this->document->setTitle($this->language->get('heading_title'));
    $data['heading_title'] = $this->language->get('heading_title');

    $data['token'] = $this->session->data['token'];
    $data['baseRoute'] = 'extension/module/foc_csv';
    $data['baseUrl'] = $this->url->link('');

    $this->load->model('extension/module/foc_csv');

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
    $initial['profiles'] = $this->model_extension_module_foc_csv->loadProfiles(); //$this->config->get(self::CONFIG_PROFILES);
    $initial['keyFields'] = $this->model_extension_module_foc_csv->getKeyFields();
    $initial['encodings'] = array('UTF8', 'cp1251');
    $initial['dbFields'] = $this->model_extension_module_foc_csv->getDbFields();

    $data['initial'] = json_encode($initial);

    return $this->response->setOutput($this->load->view('extension/module/foc_csv', $data));
  }

  /*
    Загружает настройки профиля
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
    Аплоадим CSV файл и отдаём его идентификатор
  */
  public function uploadCsv () {
    if ($this->request->server['REQUEST_METHOD'] == 'POST') {

      var_dump($_FILES);
      die;
    }
  }

  /*
    Читает инфу о CSV
  */
  public function csvFileInfo () {

  }

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

    // var_dump($this->request->post);
  }

  /*
    Выполняет импорт
  */
  public function import () {
    if (!empty($_FILES) && isset($_POST['profile-json'])) {
      $this->load->model('extension/module/foc_csv');

      // первый вызов и заливка файлов - генерим ключ
      $key = $this->model_extension_module_foc_csv->prepareImportPath();
      $importFile = $this->model_extension_module_foc_csv->getImportCsvFilePath($key);
      $imagesFile = $this->model_extension_module_foc_csv->getImportImagesZipPath($key);

      // цсвэшник - просто перемещаем
      if (isset($_FILES['csv-file'])) {
        move_uploaded_file($_FILES['csv-file']['tmp_name'], $importFile);
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

      $import_url = $this->url->link('extension/module/foc_csv/importPart', 'token=' . $this->session->data['token'], 'ssl');

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
    Импортирует часть позиций
  */
  public function importPart ($key = null, $position = null, $profile = null) {
    if ($key === null && $position === null && $profile === null) {
      if ($this->request->server['REQUEST_METHOD'] == 'POST') {
        $json = json_decode(file_get_contents('php://input'), true);

        $this->load->model('extension/module/foc_csv');

        if (isset($json['position']) && isset($json['key']) && isset($json['profile'])) {
          $import_key = $json['key'];
          $position = $json['position'];
          $profile = $json['profile'];

          $skipFirstLine = $position === 0 ? $profile['skipFirstLine'] : false;
          $delimiter = empty($profile['csvFieldDelimiter']) ? ';' : $profile['csvFieldDelimiter'];
          $importAtOnce = empty($profile['importAtOnce']) ? 10 : $profile['importAtOnce'];

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

          while (($line = fgetcsv($csv_fid, 0, $delimiter)) !== false && $i < $importAtOnce) {
            if ($i++ === 0 && $skipFirstLine) {
              continue;
            }
            // import stuff..
            $this->model_extension_module_foc_csv->importProduct($profile, $line);
          }

          $position = ftell($csv_fid);
          fclose($csv_fid);

          $this->sendOk(array(
            'key' => $import_key,
            'position' => $position,
            'lines' => $i
          ));
        }
      }
    }

    $this->sendFail();
  }

  private function breadcrumbs () {
    $breadcrumbs = array();

    $breadcrumbs[] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => false
		);
		$breadcrumbs[] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('extension/module/foc_csv', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
    );

    return $breadcrumbs;
  }

}