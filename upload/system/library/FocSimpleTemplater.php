<?php
/*
  Simple template system
  This class provides simple syntax to interpolation variables and loops support

  It's not really compatible with Opencart library autoload, because of all functions are static,
  but you can use the trick:
    $this->load->library('FocSimpleTemplater');
    // after that, you can call FinSimpleTemplater directly:
    FinSimpleTemplater::render($tpl, array())


  Templates language:

  Variable interpolation:
    {{ variable_interpolation }}

  Loops:
    [@each (value, index) <= source]
      loop content here
      {{ loop.index }} or {{ index }} <- current iteration index (starting from 1)
      {{ value.name }}
    [@endeach]

    [@each value <= source]
      {{ value }}
    [@endeach]

  Functions (see $enabled_functions to list available):
    [@fn FUNCTION_NAME | ARGUMENT]

    [@date | 'Y-m-d'] - date('Y-m-d')
    [@md5 | @variable] - md5($variables['variable'])
*/
class FocSimpleTemplater {

  // available functions
  protected static $enabled_functions = array(
    'nl2br',
    'date',
    'htmlspecialchars',
    'htmlentities',
    'html_entity_decode',
    'md5',
    'lcfirst',
    'ucfirst',
    'strtolower',
    'strtoupper',
    'ucwords',
    'trim',
    'time',
    'microtime',
    'money_format',
    'number_format'
  );

  // multiline to single line
  protected static function normalize ($template) {
    return trim(preg_replace("/\r?\n/", "", preg_replace("/[^\S\t]+/", " ", $template)));
  }

  // make arguments array from string
  protected static function process_function_args ($args_raw, $vars = array()) {
    $args = array_map('trim', preg_split('/,/', $args_raw, -1, PREG_SPLIT_NO_EMPTY));
    $result = array();

    foreach ($args as $arg) {
      if (preg_match("/^\@(.*)/", $arg, $matches)) {
        $result[] = isset($vars[$matches[1]]) ? $vars[$matches[1]] : null;
      }
      else {
        $result[] = trim($arg, "\"\'");
      }
    }

    return $result;
  }

  // parse and execute function code
  protected static function execute_function_code ($code, $vars = array()) {
    $result = '';

    $f_name = null;
    $f_args_raw = '';

    $parts = array_map('trim', explode('|', $code));

    if (count($parts) > 1) {
      list ($f_name, $f_args_raw) = $parts;
    }
    else {
      $f_name = $parts[0];
    }

    $f_args = self::process_function_args($f_args_raw, $vars);

    if (in_array($f_name, self::$enabled_functions)) {
      $result = call_user_func_array($f_name, $f_args);
    }

    return $result;
  }

  /*
    render functions:
    [@fn <function_name> | <argument>]
  */
  public static function render_functions ($template, $vars = array()) {
    return preg_replace_callback(
      "/\[\@fn ([^\]]+)\]/ium",
      function ($matches) use ($vars) {
        if (count($matches) === 2) {
          $fn = $matches[1];
          return self::execute_function_code($fn, $vars);
        }
        return '';
      }, $template);
  }

  // replace variables with values
  public static function render_vars ($template, $vars = array()) {
    $result = $template;

    foreach ($vars as $name => $value) {
      $replacement = $value;
      // we do not support nested loops, so just replace with json string
      if (is_array($replacement)) {
        $replacement = '[' . json_encode($replacement) . ']';
      }

      $result = preg_replace('/{{ ' . preg_quote($name) . ' }}/', $replacement, $result);
    }
    return $result;
  }

  // render loop
  public static function render_loop ($loop_cond, $loop_body, $data = array()) {
    $result = '';
    list($condition, $source_name) = explode('<=', $loop_cond);
    $loop_vars = explode(',', str_replace(array('(', ')', ' '), '', $condition));

    if (count($loop_vars) > 1) {
      list($l_value, $l_key) = $loop_vars;
    }
    else {
      $l_value = $loop_vars[0];
      $l_key = 'loop.index';
    }

    $source_name = trim($source_name);

    if (!isset($data[$source_name]) || empty($data[$source_name])) {
      return $result;
    }

    $index = 1;
    foreach ($data[$source_name] as $key => $value) {
      $local_vars = $data;
      $local_vars[$l_key] = $index++;

      $local_vars[$l_value] = $value;

      if (!is_numeric($key)) {
        $local_vars[$l_value . '.' . $key] = $value;
      }
      else {
        if (is_array($value)) {
          foreach ($value as $attrName => $attrValue) {
            $local_vars[$l_value . '.' . $attrName] = $attrValue;
          }
        }
      }
      $result .= self::render_vars($loop_body, $local_vars);
    }

    return self::render_functions($result, $local_vars);
  }

  // render template
  public static function render ($template, $vars = array()) {
    /*
      <table>
      [@each (field,iter) <= source]
      values
      [@endeach]
      something other

      becomes single line
    */
    $normalized_template = self::normalize($template);
    $loops = array_filter(explode('[@endeach]', $normalized_template));

    $result = '';

    /*
      <table>[@each (field,iter) <= source]values
      processed as:
      0: whole match
      1: pre: <table>
      2: loop_cond: (field,iter) <= source
      3: loop_body: values
    */
    foreach ($loops as $loop) {
      if (preg_match("/((?!\[\@each).*)?\[\@each ([^\]]+)\]\s*(.*)/iu", $loop, $matches)) {
        if (count($matches) === 4) {
          $pre = $matches[1];
          $loop_cond = $matches[2];
          $loop_body = $matches[3];
          $result .= trim(self::render_vars($pre, $vars));
          $result .= trim(self::render_loop($loop_cond, $loop_body, $vars));
        }
      }
      else {
        $fns_executed = self::render_functions($loop, $vars);
        $result .= trim(self::render_vars($fns_executed, $vars));
      }
    }
    return $result;
  }
}