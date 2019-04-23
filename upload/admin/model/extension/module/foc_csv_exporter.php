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

  private $attributeEncoderMap = array();

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

    $this->attributeEncoders['column'] = array(
      'title' => $this->language->get('encoder_column'),
      'multicolumn' => true,
      'options' => array(
        'header_template' => array(
          'title' => $this->language->get('encoder_header_template'),
          'default' => '{{ group_name }}:{{ attribute_name }}'
        )
      )
    );

    /*
      Encoder option "type" - is text by default
      At the moment there are only textarea and text widgets present
    */

    /*
      use this section to describe your attribute encoders via vq/ocmod
      please see advantshop encoder as reference
    */
    /* CUSTOM ATTRIBUTE ENCODER DESCRIBE */
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

    $encoderEnabled = isset($profile['attributeEncoder']) && !is_null($profile['attributeEncoder']);

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

      // encode attributes and append to end
      if ($encoderEnabled) {
        $attributes = $this->encodeAttributes($profile, $dataItem['product_id']);

        // multicolumn mode
        if (is_array($attributes) && count($attributes) > 0) {
          if ($this->model_extension_module_foc_csv_exporter->isMulticolumnEncoder($profile['attributeEncoder'])) {
            $maxKey = max(array_keys($attributes));
            for ($i = count($csvLine); $i <= $maxKey; $i++) {
              if (!isset($attributes[$i])) {
                $csvLine[$i] = '';
                continue;
              }
              $csvLine[$i] = $attributes[$i];
            }
          }
        }
        else {
          $csvLine[] = $attributes;
        }
      }

      if (!empty($csvLine)) {
        $csvLines[] = $csvLine;
      }
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

  /*
    Returns attributes grouped by attribute_group_id
  */
  public function getProductAttributes ($primary) {
    $result = array();

    // Fetch attributes with attribute groups for product
    $sql = 'SELECT
              `pa`.attribute_id AS attribute_id,
              `pa`.text AS attribute_value,
              `ad`.name AS attribute_name,
              `agd`.attribute_group_id AS group_id,
              `agd`.name AS group_name,
              CONCAT(`agd`.attribute_group_id, \':\', `pa`.attribute_id) AS `key`
            FROM ' . DB_PREFIX . 'product_attribute `pa`
            LEFT JOIN ' . DB_PREFIX . 'attribute `attr`
              ON `attr`.attribute_id = `pa`.attribute_id
            LEFT JOIN ' . DB_PREFIX . 'attribute_description `ad`
              ON `ad`.attribute_id = `attr`.attribute_id AND `ad`.language_id = ' . (int) $this->language_id . '
            LEFT JOIN ' . DB_PREFIX . 'attribute_group_description `agd`
              ON `agd`.attribute_group_id = `attr`.attribute_group_id AND `agd`.language_id = ' . (int) $this->language_id . '
            WHERE `pa`.product_id = ' . (int)$primary . ' AND `pa`.language_id = ' . (int) $this->language_id;

    $query = $this->db->query($sql);

    if ($query->num_rows > 0) {
      foreach ($query->rows as $row) {
        if (!isset($result[$row['group_id']])) {
          $result[$row['group_id']] = array(
            $row
          );
          continue;
        }

        $result[$row['group_id']][] = $row;
      }
    }

    return $result;
  }


  /*
    Encoder list getter
  */
  public function getAttributeEncoders () {
    return $this->attributeEncoders;
  }

  /*
    Check if encoder is multicolumn (multicolumn config item - true)
  */
  public function isMulticolumnEncoder ($encoder) {
    return isset($this->attributeEncoders[$encoder]['multicolumn'])
            ? $this->attributeEncoders[$encoder]['multicolumn']
            : false;
  }

  /*
    Shortcut to create and check encoder method name
  */
  private function getEncoderMethodName ($name) {
    $method = 'encoder_' . $name;
    if (method_exists($this, $method)) {
      return $method;
    }

    return false;
  }

  /*
    Validate and fill with default values if need
    Returns array of 2 - valid and encoder
  */
  private function normalizeEncoderOptions ($encoder) {
    $encoderDescription = $this->attributeEncoders[$encoder['name']];
    $valid = false;

    if (!empty($encoderDescription['options'])
        && !empty($encoder['options'])
    ) {
      foreach ($encoderDescription['options'] as $name => $option) {
        if (isset($encoder['options'][$name])
            && !empty($encoder['options'][$name])
        ) {
          $valid = true;
          continue;
        }
        elseif (isset($option['default'])) {
          $encoder['options'][$name] = $option['default'];
          $valid = true;
        }
        else {
          $this->writeLog('[OPTION_ERROR] (' . $name . ') is not presented!', 'warn');

          $valid = false;
        }
      }
    }
    else {
      $valid = true;
    }

    return array($valid, $encoder);
  }

  /*
    Validate encoder and find encoder method (encode_<ENCODER_NAME> method)
  */
  private function normalizeEncoder ($encoder) {
    $valid = false;
    $encoderMethod = false;

    if (isset($encoder['name'])
        && isset($this->attributeEncoders[$encoder['name']])
    ) {
      $encoderMethod = $this->getEncoderMethodName($encoder['name']);

      if ($encoderMethod) {
        list ($valid, $encoder) = $this->normalizeEncoderOptions($encoder);
      }
    }

    return array($valid, $encoder, $encoderMethod);
  }

  /*
    There are two actions on exporting:
      1) export creates csv headers
      2) exportPart fill with data
    We storing mapped group_id:attr_id to csvIdx fields
    to keep correct order between calls
  */
  // save encoder map data to file
  public function saveEncoderMap () {
    $path = $this->model_extension_module_foc_csv_exporter->getUploadPath($this->uploadKey);
    file_put_contents($path . 'map.json', json_encode($this->attributeEncoderMap));
  }

  // load encoder map data from file
  public function loadEncoderMap () {
    static $loaded = false;

    if (!$loaded) {
      $loaded = true;
      $path = $this->model_extension_module_foc_csv_exporter->getUploadPath($this->uploadKey);
      $this->attributeEncodeMap = json_decode(file_get_contents($path . 'map.json'), true);
    }
  }

  // shortcut - loads encoder map from file if need and searching idx by key
  public function getAttributeColumnIdx ($key) {
    $this->loadEncoderMap();

    if (isset($this->attributeEncodeMap[$key])) {
      return $this->attributeEncodeMap[$key];
    }

    return null;
  }

  /*
    Create CSV attribute headers

    Function is bit of tricky.
    For single-column encoders - just returns column header, so if you creating
    singlecolumn encoder - just return any string in your `encoder_headers_ENCODER`

    But if encoder is multicolumn, then we create "special" array, which keys
    starting from $startIdx (which is first empty column index).

    Also, to keep columns idx consistency between requests (export/exportPart) - it's creating
    encoderMap, and store it to file.

    If you develop multicolumn encoder - your function must return array with correct format - {hash=>name}, where hash is any key you can restore (in column encoder used simple
    "group_id:attribute_id" scheme), and name is column header name)
  */
  public function encodeAttributeHeaders ($profile, $startIdx) {
    $result = array();

    $encoder = $profile['attributeEncoder'];
    $multicolumn_mode = $this->isMulticolumnEncoder($encoder);

    $encoderOptions = isset($profile['attributeEncoderData'][$encoder]) ? $profile['attributeEncoderData'][$encoder] : array();

    $encoderObj = array(
      'name' => $encoder,
      'options' => $encoderOptions
    );

    list ($valid, $encoder) = $this->normalizeEncoderOptions($encoderObj);
    $encoderMethod = $this->getEncoderMethodName('headers_' . $encoderObj['name']);

    if ($valid && $encoderMethod) {
      $headers = $this->{$encoderMethod}($encoder);

      // if multicolumn - method must return map as ["hash" => "name"]
      // else - just a string
      if ($multicolumn_mode && is_array($headers)) {
        foreach ($headers as $key => $header) {
          $this->attributeEncoderMap[$key] = $startIdx;
          $result[$startIdx] = $header;
          $startIdx++;
        }

        $this->saveEncoderMap();
      }
      else {
        return $headers;
      }
    }
    else {
      return 'attributes';
    }

    return $result;
  }

  /*
    Run attribute encoders on data
  */
  public function encodeAttributes ($profile, $primary) {
    $result = array();

    if (isset($profile['attributeEncoder'])) {
      $encoder = $profile['attributeEncoder'];
      $encoderOptions = isset($profile['attributeEncoderData'][$encoder]) ? $profile['attributeEncoderData'][$encoder] : array();

      $encoderObj = array(
        'name' => $encoder,
        'options' => $encoderOptions
      );

      list ($valid, $encoder, $encoderMethod) = $this->normalizeEncoder($encoderObj);

      if ($valid && $encoderMethod) {
        $atts = $this->getProductAttributes($primary);
        $result = $this->{$encoderMethod}($encoder, $atts);
      }
    }

    return $result;
  }

  /* ATTRIBUTE HEADERS GENERATOR METHODS */
  // advantshop header
  public function encoder_headers_advantshop ($encoder) {
    return $encoder['options']['header_template'];
  }

  // grouped advantshop header
  public function encoder_headers_advantshop_grouped ($encoder) {
    return $encoder['options']['header_template'];
  }

  /*
  ` Column-based headers generator
    Be aware using this if your shop has many attributes and goods,
    slow query.
  */
  public function encoder_headers_column ($encoder) {
    $result = array();

    // 1 - get all variations
    $sql = 'SELECT
              `pa`.attribute_id AS attribute_id,
              `ad`.name AS attribute_name,
              `agd`.attribute_group_id AS group_id,
              `agd`.name AS group_name,
              CONCAT(`agd`.attribute_group_id, \':\', `pa`.attribute_id) AS `key`
            FROM ' . DB_PREFIX . 'product_attribute `pa`
            LEFT JOIN ' . DB_PREFIX . 'attribute `attr`
              ON `attr`.attribute_id = `pa`.attribute_id
            LEFT JOIN ' . DB_PREFIX . 'attribute_description `ad`
              ON `ad`.attribute_id = `attr`.attribute_id AND `ad`.language_id = ' . (int) $this->language_id . '
            LEFT JOIN ' . DB_PREFIX . 'attribute_group_description `agd`
              ON `agd`.attribute_group_id = `attr`.attribute_group_id AND `agd`.language_id = ' . (int) $this->language_id . '
            WHERE `pa`.language_id = ' . (int) $this->language_id . '
            GROUP BY `key`';

    $query = $this->db->query($sql);

    if ($query->num_rows > 0) {
      $template = $encoder['options']['header_template'];

      foreach ($query->rows as $row) {
        $result[$row['key']] = FocSimpleTemplater::render($template, $row);
      }
    }

    return $result;
  }

  /*
    use this section to describe your attribute encoders via vq/ocmod
    please see advantshop encoder as reference
  */
  /* CUSTOM ATTRIBUTE HEADERS GENERATOR METHODS */

  /* ATTRIBUTE ENCODERS METHODS */
  /*
    Advantshop attributes format encoder
    Format:
      attr:val,attr:val
      delimiters are configurable.
  */
  private function encoder_advantshop ($encoder, $atts) {
    $result = '';

    if (isset($encoder['options'])
        && !empty($atts)
    ) {
      $options = $encoder['options'];

      $keyValueDelimiter = $options['keyvalue_delimiter'];
      $entriesDelimiter = $options['entries_delimiter'];

      $advantshop_string = '';

      foreach ($atts as $group_id => $attributes) {
        foreach ($attributes as $data) {
          $advantshop_string .= $data['attribute_name'] . $keyValueDelimiter . $data['attribute_value'] . $entriesDelimiter;
        }
      }

      $result = rtrim($advantshop_string, $entriesDelimiter);
    }

    return $result;
  }

  /*
    Advantshop attributes format encoder
    Format:
      group=>{attr:val},
      only { and } is required by protocol, you can change delimiters, for example:
        group^^^{attr###val}|||group...
  */
  private function encoder_advantshop_grouped ($encoder, $atts) {
    $result = '';

    if (isset($encoder['options'])
        && !empty($atts)
    ) {
      $options = $encoder['options'];

      $keyValueDelimiter = $options['keyvalue_delimiter'];
      $entriesDelimiter = $options['entries_delimiter'];
      $groupAttrsDelimiter = $options['groupattrs_delimiter'];
      $groupsDelimiter = $options['groups_delimiter'];

      $advantshop_string = '';

      foreach ($atts as $group_id => $attributes) {
        $group_name = '';
        $attributes_string = '';
        foreach ($attributes as $data) {
          $group_name = $data['group_name'];
          $attributes_string .= $data['attribute_name'] . $keyValueDelimiter . $data['attribute_value'] . $entriesDelimiter;
        }
        $advantshop_string .= $group_name . $groupAttrsDelimiter . '{' . rtrim($attributes_string, $entriesDelimiter) . '}' . $groupsDelimiter;
      }

      $result = rtrim($advantshop_string, $groupsDelimiter);
    }

    return $result;
  }

  /*
    Trivial column encoder implementation
    Seem `getAttributeColumnIdx` for more details
  */
  private function encoder_column ($encoder, $atts) {
    $result = array();

    if (isset($encoder['options'])
        && !empty($atts)
    ) {
      foreach ($atts as $group_id => $attributes) {
        foreach ($attributes as $data) {
          $idx = $this->getAttributeColumnIdx($data['key']);

          if ($idx !== false) {
            $result[$idx] = $data['attribute_value'];
          }
        }
      }
    }

    return $result;
  }

  /*
    use this section to describe your attribute encoders via vq/ocmod
    please see advantshop encoder as reference
  */
  /* CUSTOM ATTRIBUTE ENCODERS */

}