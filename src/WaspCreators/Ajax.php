<?php

/**
 * @package wasp
 */

namespace WaspCreators;

use WaspCreators\Fields;

class Ajax
{
  const action_name = 'ajax_loader_action';

  protected $domain;

  protected $loads;



  public function __construct($domain)
  {
    $this->domain = $domain;
    $hook_name    = 'wp_ajax_wasp_' . self::action_name;

    \add_action($hook_name, [$this, 'ajax_loader_action']);
  }



  public function load_handler($loads)
  {
    if (empty($loads)) {
      return false;
    }

    $this->loads = $loads;
    add_action('admin_enqueue_scripts', [$this, 'admin_ajax_enqueue']);
  }



  public function admin_ajax_enqueue()
  {
    $handle_name = $this->domain . '_wasp_ajax_loader';

    // Enqueued script with localized data.
    \wp_enqueue_script($handle_name, \plugin_dir_url(__FILE__) . '../../assets/js/ajax-loader.js', ['jquery'], '1.0.4');

    // Localize the script with new data
    $values = array(
      'ajax_url'    => \admin_url('admin-ajax.php'),
      'action_name' => 'wasp_ajax_loader_action',
      'nonce'       => \wp_create_nonce('wasp_ajax_loader_action'),
      'fields'      => $this->loads
    );

    \wp_localize_script($handle_name, 'ajax_loader_object', $values);
  }



  /**
   * Admin Ajax response output function
   */
  public function ajax_loader_action()
  {
    $has_option_list = ['radio', 'select', 'multi_text_input', 'checkbox'];

    if (\wp_verify_nonce($_REQUEST['nonce'], 'wasp_ajax_loader_action')) {
      $fields = $_REQUEST['fields'];
      $result_json = [];

      foreach ($fields as $key => $field) {
        $hook         = stripslashes($field['hook']);
        $hook_exp     = explode('::', $hook);
        $class_name   = $hook_exp[0];
        $method_name  = $hook_exp[1];
        $params       = isset($field['params']) ? $field['params'] : [];
        $field_type   = $field['field']['type'];

        if (empty($params) || !is_array($params)) {
          $params = [];
        }

        if (!method_exists($class_name, $method_name)) {
          echo $hook . ' not found!';
        }

        $func_result = call_user_func_array($hook, $params);

        $not_found_text = 'not found!!!!!';

        if ($func_result === false) {
          $field['field']['not_found'] = $not_found_text;
        } elseif ($field_type == 'text_input') {
          $field['field']['value'] = $func_result;
        } elseif ($field_type == 'textarea') {
          $field['field']['text'] = $func_result;
        } elseif (in_array($field_type, $has_option_list)) {
          if (is_array($func_result)) {
            $field['field']['options'] = $func_result;
          }
        }

        $result = Fields::prepare($field['field']);

        $result_json[$key]['value'] = $result;
      }

      echo json_encode($result_json);
    }

    \wp_die();
  }
}
