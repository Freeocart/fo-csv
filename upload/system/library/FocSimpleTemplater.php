<?php
/*
  Simple template system
  This class provides simple syntax to interpolation variables and loops support

  Templates:
  {{ variable_interpolation }}

  [@each (value, index) <= source]
    loop content here
    {{ loop.index }} or {{ index }} <- current iteration index (starting from 1)
    {{ value.name }}
  [@endeach]

  [@each value <= source]
    {{ value }}
  [@endeach]
*/
class FocSimpleTemplater {
  // multiline to single line
  protected static function normalize ($template) {
    return trim(preg_replace("/\r?\n/", "", preg_replace("/[^\S\t]+/", " ", $template)));
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

    return $result;
  }

  // render template
  public static function render ($template, $vars = array()) {
    $loops = array_filter(explode('[@endeach]', self::normalize($template)));
    $result = '';

    if (count($loops) < 1) {
      return trim(self::render_vars($loops[0], $vars));
    }

    foreach ($loops as $loop) {
      if (preg_match("/((?!\[\@each).*)?\[\@each ([^\]]+)\]\s*(.*)/iu", $loop, $matches)) {
        if (count($matches) == 4) {
          $pre = $matches[1];
          $loop_cond = $matches[2];
          $loop_body = $matches[3];
          $result .= trim(self::render_vars($pre, $vars));
          $result .= trim(self::render_loop($loop_cond, $loop_body, $vars));
        }
      }
      else {
        $result .= trim(self::render_vars($loop, $vars));
      }
    }
    return $result;
  }
}