<?php
/**
* @todo якщо добавили поле для закачки файлу, автоматично виставляти правильний enctype
* @todo переробити add_radio ($options - варіанти, $value - вибраний варіант)
* @todo всі шаблони привести до вигляду {$variable_name}
* @todo де це можливо, замінити " на '
* @todo формат дати для add_date - стандартний формат, і можливість задати окремий формат для кожного поля
*/
class form_builder
{
    var $error_level   = E_USER_WARNING;
    var $check_referer = true;
    var $val_separator = '||';
    var $cap_separator = '<br>';
    var $use_paterns   = true;
    var $form_variable = 'FORM';
    var $var_prefix    = '[#';
    var $var_suffix    = ']';
    var $form_data     = '';
    var $form = array(
            'name'     => 'fob',
            'action'   => '',
            'method'   => 'post',
            'class'    => 'fob-form',
            'enctype'  => 'multipart/form-data'
        );

    var $element_paterns = array(
            'text' => array(
//                'maxlength' => 255,
                'class' => 'fob-text'
            ),
            'text-short' => array(
                'class' => 'fob-text-short'
            ),
            'date' => array(
                'class' => 'fob-date'
            ),
            'login' => array(
                'class' => 'fob-login'
            ),
            'password' => array(
                'class' => 'fob-password'
            ),
            'file' => array(
                'class' => 'fob-file'
            ),
            'textarea' => array(
                'rows' => 5,
                'class' => 'fob-textarea'
            ),
            'wysiwyg' => array(
                'rows' => 30,
                'class' => 'fob-wysiwyg'
            ),
            'checkbox' => array(
                'class' => 'fob-checkbox'
            ),
            'radio' => array(
                'class' => 'fob-radio'
            ),
            'button' => array(
                'class' => 'fob-button'
            ),
            'submit' => array(
                'class' => 'fob-button'
            ),
            'image' => array(
                'class' => 'fob-image'
            ),
            'select' => array(
                'class' => 'fob-select'
            ),
            'fieldset' => array(
                'class' => 'fob-fieldset'
            ),
            'listbox' => array(
                'class' => 'fob-listbox'
            )
        );
    var $validate_paterns = array(
            'login' => array(
                'minlen' => 4,
                'maxlen' => 16,
                'function' => 'is_login'
            ),
            'password' => array(
                'minlen' => 4,
                'maxlen' => 16,
                'function' => 'is_password'
            ),
            'url' => array(
                'function' => 'is_url'
            ),
            'email' => array(
                'function' => 'is_email'
            ),
            'emails' => array(
                'function' => 'is_emails'
            ),
            'alpha' => array(
                'function' => 'is_alpha'
            ),
            'numeric' => array(
                'function' => 'is_numeric'
            ),
            'float' => array(
                'function' => 'is_float'
            )
        );
    var $validate_functions = array(
/*                'pass' => array(
                                  'function'   => 'is_password_secure',
                                  'vars'       => 'password||login',
                                  'message'    => 'Ненадежный пароль'
                                   ),
*/
                );
    var $validate_error_messages = array(
            '2short' => 'Введенное значение слишком короткое',
            '2long' => 'Введенное значение слишком длинное',
            'format' => 'Неверный формат вносимых данных',
            'required' => 'Поле является обязательным для заполнения'
        );

    var $elements   = array();
    var $errors     = array();
    var $templates  = array(
            'form'       => '[#form]<table class="fob-table">[#main]</table></form>',
            'input'      => '<tr><td class="l ">[#title][#required]<br><small>[#description]</small></td><td class="fr"><div class="b-wysiwyg">[#input]</div>[#caption]<br>[#error]</td></tr>',
            'date_input' => '<tr><td class="l">[#title][#required]<br><small>[#description]</small></td><td class="fr">[#input][#caption]<br>[#error]</td></tr>',
            'radio_item' => '[#input][#caption]<br>',
            'listbox'    => '<div id="[#listbox_id]_input_div" style="display: [#input_div_display];">[#text_input]&nbsp;&nbsp;<a href="" onclick="switch_listbox(\'[#listbox_id]\', true); return false;">Выбрать из списка</a></div><div id="[#listbox_id]_select_div" style="display: [#select_div_display];">[#select_input]&nbsp;&nbsp;<a href="" onclick="switch_listbox(\'[#listbox_id]\', false); return false;">Ввести вручную</a></div>',
            'button'     => '<tr><td colspan="2" align="center">[#input]<br><small>[#description]</small></td></tr>',
            'html_code'  => '<tr><td colspan="2" class="l">[#input]</td></tr>',
            'required'   => '&nbsp;&nbsp;<span class="fob-req">*</span>',
            'error'      => '<div class="fob-error"><div>Ошибка:</div>[#error_message]</div>',
        );
    var $variables      = array();
    var $form_header    = array();
    var $form_footer    = array();

    var $default_element = array(
            'title' => '',
            'description' => '',
            'name' => '',
            'type' => 'text',
            'properties' => '',
            'validate' => '',
            'template' => '',
            'value' => '',
            'required' => false,
        );


    /* WYSIWYG */
    var $wysiwyg_areas  = array();

    // private variables
    var $_is_submited    = false;
    var $_submit_checked = false;

    /******************************************************************************/
    /************************  FORM CONSTRUCTION PART  ****************************/
    /******************************************************************************/
    /**
    * form_builder::form_builder()
    * { general form attributes }
    * @param string $name         = Form name
    * @param string $action       = url
    * @param string $method       = POST/GET
    * @param string $target       = _blank, _self, _parent, _top
    * @param string $class        = CSS class
    * @param string $html         = <form ... $html>
    * @param string $enctype
    */
    function form_builder($name='', $action='', $method='POST', $target='', $class='', $html = '', $enctype = '')
    {
        if (empty($name)) {
            $name = 'FORM';
        }
        $this->form_variable = $name;
        $this->form['name'] = $name;
        if (!empty($action)) {
           $this->form['action'] = $action;
         } else {
           $this->form['action'] = $_SERVER["PHP_SELF"];
        }
        $this->form['method'] = $method;
        $this->form['target'] = $target;
        $this->form['enctype'] = $enctype;
        $this->form['class'] = $class;
        $this->form['html'] = $html;
    }

    /**
    * form_builder::add_template()
    * { Defines template }
    * @param string $name         template name
    * @param string $filename     full path and filename
    */
    function add_template($name, $filename) {
        if( file_exists( $filename))
            $this->templates[$name] = implode ('', file ($filename));
        else
            trigger_error ("Form Builder: Template file not found (".$name.")", $this->error_level);
    }

    /**
    * form_builder::add_element_patern()
    * { Defines default propperties of form elements }
    * @param string $name       patern name
    * @param array  $patern     array of patern values
    */
    function add_element_patern($name, $patern) {
        if (is_array($patern))
           $this->element_paterns[$name] = $patern;
        else
           trigger_error ("Form Builder: Invalid element patern for '".$name."'", $this->error_level);
    }

    /**
    * form_builder::add_validate_patern()
    * { Defines validate patern }
    * @param string $name       patern name
    * @param array  $patern     array of patern values
    */
    function add_validate_patern($name, $patern) {
        if (is_array($patern))
           $this->validate_paterns[$name] = $patern;
        else
           trigger_error ("Form Builder: Invalid validate patern for element <b>".$name."</b>", $this->error_level);
    }

    /**
    * form_builder::add_validate_function()
    * { Assigns validate function to the specified form element }
    * @param string $name         element name
    * @param array  $function     array of function properties
    */
    function add_validate_function($name, $function) {
        if (is_array($function)) {
           if (function_exists($function['function'])) {
              $this->validate_functions[$name][] = $function;
           } else {
               trigger_error ("Form Builder: Validate function <b>".$function['function']."</b> for element <b>".$name."</b> not exists", $this->error_level);
           }
        } else {
           trigger_error ("Form Builder: Invalid validate function for element <b>".$name."</b>", $this->error_level);
        }
    }
    /**
    * form_builder::add_variable()
    * { Assigns a value to the template variable }
    * @param string $name       variable name
    * @param string $value      variable value
    */
    function add_variable($name, $value) {
        $this->variables[$name] = $value;
    }

    /**
    * form_builder::add_elements()
    * { adds an array of the elements to the current form }
    */
    function add_elements($elements_array){
        if (is_array($elements_array)) {
            while (list($key, $element) = each ($elements_array)) {
                if (is_array($element)) {
                   $this->add_element($element);
                } else {
                   trigger_error ("Form Builder::add_elements(): wrong element format, array expected", $this->error_level);
                }
            }
        } else {
            trigger_error ("Form Builder::add_elements(): array expected", $this->error_level);
        }
    }

    /**
    * form_builder::add_element()
    * { adds an element to the current form }
    */
    function add_element($source_element) {
        static $counter = 0;
        if (!is_array($source_element)) {
            trigger_error ("Form Builder::add_element(): wrong element format, array expected", $this->error_level);
            return false;
        }
        $counter++;
        $element = $this->default_element;
        foreach($source_element as $key => $value){
            $element[$key] = $value;
            if (!empty($value)) {
            }
        }
        switch ($element['type']) {
            case 'image':
                $element['type'] = 'file';
                break;
            case 'select':
                if (strstr($element['value'], $this->val_separator)) {
                    $values = explode($this->val_separator, $element['value']);
                    $element['value'] = array();
                    while (list($key, $value) = each ($values)) {
                        $element['value'][$value] = $value;
                    }
                }
                if (is_array($element['selected'])) {
                } else if (strstr($element['selected'],$this->val_separator)) {
                    $values = explode('||', $element['selected']);
                    $element['selected'] = array();
                    while (list($key, $value) = each ($values)) {
                          $element['selected'][$value] = $value;
                    }
                } else {
                    $selected_key = $element['selected'];
                    $element['selected'] = array();
                    $element['selected'][$selected_key] = '';
                }
                break;

            case 'checkbox':
                $element['checked'] = $element['value'];
                $element['value'] = 1;
                break;

            case 'radio':
                $element['checked'] = $element['value'];
                $element['value'] = $element['options'];
                //echo '<pre>';print_r($element);die('@');
                break;
        } // switch

        switch ($element['type']) {
            case 'select':
            case 'checkbox':
            case 'radio':
            case 'fieldset':
            case 'text':
            case 'password':
            case 'submit':
            case 'reset':
            case 'file':
            case 'hidden':
            case 'image':
            case 'button':
            case 'textarea':
                $new_element = array();
                // default values
                if (($this->use_paterns) && (!empty($this->element_paterns[$element['type']])) ) {
                    while (list($key, $pat_value) = each ($this->element_paterns[$element['type']])) {
                        $new_element[$key] = $pat_value;
                    }
                    reset($this->element_paterns[$element['type']]);
                }
                // user provided values
                while (list($key, $u_value) = each ($element)) {
                    $new_element[$key] = $u_value;
                }
                // additional element properties
                $properties = $this->_get_paterns($element['properties'], $element['validate'], $element['template']);
                if (is_array($properties)) {
                    while (list($key, $value) = each ($properties)) {
                        $new_element[$key] = $value;
                    }
                }
                $this->elements[] = $new_element;
                break;

            case 'html':
                $new_element = array();
                $new_element['type'] = 'html';
                $new_element['name'] = $element['name'];
                $new_element['value'] = $element['title'];
                if (empty($element['value'])) {
                    $new_element['template'] = '';
                } else if (array_key_exists($element['value'], $this->templates)) {
                    $new_element['template'] = $this->templates[$template];
                } else {
                    $new_element['template'] = $element['value'];
                }
                $this->elements[] = $element;
                break;

            case 'wysiwyg':
                $this->add_wysiwyg($element['required'], $element['name'], $element['value'], $element['title'], $element['description']);
                break;

            case 'date':
                $this->add_date($element['name'], $element['value'], $element['title'], $element['description']);
                break;

            default:
                trigger_error ("Form Builder::add_element(): Unknown element type: ".$element['type'], $this->error_level);
                break;
        } // switch

    }

    /**
    * form_builder::add_hidden()
    */
    function add_hidden($name, $value, $template='[#input]') {
        $element = array();
        $element['type'] = 'hidden';
        $element['name'] = $name;
        $element['value'] = $value;
        $element['template'] = $template;
        $this->elements[] = $element;
    }

    /**
    * form_builder::add_text()
    * @param string $description
    * @param string $class
    * @param string $hint
    * @param string $properties
    * @param string $validate
    * @param string $template
    */
    function add_text($required, $name, $value, $title, $description='', $class='fob-text', $hint='', $properties='', $validate='', $template='') {
        $element = array();
        $element['type'] = 'text';
        $element['name'] = $name;
        $element['value'] = $value;
        $element['title'] = $title;
        $element['class'] = $class;
        $element['required'] = $required;
        $element['hint'] = $hint;
        $element['description'] = $description;
        if ($this->use_paterns && !empty($this->element_paterns[$element['type']]) ) {
            while (list($key, $pat_value) = each ($this->element_paterns[$element['type']])) {
                if (empty($element[$key]) && !empty($pat_value))
                   $element[$key] = $pat_value;
            }
        }
        // additional element properties
        $properties = $this->_get_paterns($properties, $validate, $template);
        if (is_array($properties))
            while (list($key, $value) = each ($properties)) {
                $element[$key] = $value;
            }
        $this->elements[] = $element;
    }

    /**
    * form_builder::add_password()
    */
    function add_password($required, $name, $value, $title, $description='', $class='fob-password', $hint='', $properties='', $validate=array(), $template='') {
        $element = array();
        $element['type'] = 'password';
        $element['name'] = $name;
        $element['value'] = $value;
        $element['title'] = $title;
        $element['required'] = $required;
        $element['hint'] = $hint;
        $element['description'] = $description;
        $element['class'] = $class;
        if ($this->use_paterns && !empty($this->element_paterns[$element['type']]) ) {
            while (list($key, $pat_value) = each ($this->element_paterns[$element['type']])) {
                if (empty($element[$key]) && !empty($pat_value))
                   $element[$key] = $pat_value;
            }
        }
        // additional element properties
        $properties = $this->_get_paterns($properties, $validate, $template);
        if (is_array($properties))
            while (list($key, $value) = each ($properties)) {
                $element[$key] = $value;
            }
        $this->elements[] = $element;
    }

    /**
    * form_builder::add_checkbox()
    */
    function add_checkbox($required, $name, $value, $title, $caption, $description='', $checked=0, $class='fob-checkbox', $hint='', $properties='', $validate=array(), $template='') {
        $element = array();
        $element['type'] = 'checkbox';
        $element['name'] = $name;
        $element['value'] = $value;
        $element['caption'] = '&nbsp;'.$caption;
        $element['title'] = $title;
        $element['hint'] = $hint;
        $element['description'] = $description;
        $element['required'] = $required;
        $element['checked'] = $checked;
        $element['class'] = $class;
        if ($this->use_paterns && !empty($this->element_paterns[$element['type']]) ) {
            while (list($key, $pat_value) = each ($this->element_paterns[$element['type']])) {
                if (empty($element[$key]) && !empty($pat_value))
                   $element[$key] = $pat_value;
            }
        }
        // additional element properties
        $properties = $this->_get_paterns($properties, $validate, $template);
        if (is_array($properties))
            while (list($key, $value) = each ($properties)) {
                $element[$key] = $value;
            }
        $this->elements[] = $element;
    }

    /**
    * form_builder::add_radio()
    */
    function add_radio($required, $name, $value, $title, $caption, $description='', $checked=0, $class='fob-radio', $hint='', $properties='', $validate=array(), $template='') {
        $element = array();
        $element['type'] = 'radio';
        $element['name']         = $name;
        $element['value']         = $value;
        $element['caption']     = $caption;
        $element['title']         = $title;
        $element['hint']         = $hint;
        $element['description'] = $description;
        $element['required']     = $required;
        $element['checked']     = $checked;
        $element['class']         = $class;
        if ($this->use_paterns && !empty($this->element_paterns[$element['type']]) ) {
            while (list($key, $pat_value) = each ($this->element_paterns[$element['type']])) {
                if (empty($element[$key]) && !empty($pat_value))
                   $element[$key] = $pat_value;
            }
        }
        // additional element properties
        $properties = $this->_get_paterns($properties, $validate, $template);
        if (is_array($properties)) {
            while (list($key, $value) = each ($properties)) {
                $element[$key] = $value;
            }
        }
        $this->elements[] = $element;
    }

    /**
    * form_builder::add_button()
    */
    function add_button($type, $name, $value, $title, $description='', $class='fob-button', $hint='', $properties='', $validate=array(), $template='') {
        $element = array();
        $element['type'] = $type;
        $element['name'] = $name;
        $element['value'] = $value;
        $element['title'] = $title;
        $element['hint'] = $hint;
        $element['description'] = $description;
        $element['class'] = $class;
        if ($this->use_paterns && !empty($this->element_paterns[$element['type']]) ) {
            while (list($key, $pat_value) = each ($this->element_paterns[$element['type']])) {
                if (empty($element[$key]) && !empty($pat_value))
                   $element[$key] = $pat_value;
            }
        }
        // additional element properties
        $properties = $this->_get_paterns($properties, $validate, $template);
        if (is_array($properties))
            while (list($key, $value) = each ($properties)) {
                $element[$key] = $value;
            }
        $this->elements[] = $element;
    }

    /**
    * form_builder::add_image()
    */
    function add_image($name, $value, $src, $title, $description='', $align='', $class='fob-image', $hint='', $properties='', $validate=array(), $template='') {
        $element = array();
        $element['type'] = 'image';
        $element['name'] = $name;
        $element['value'] = $value;
        $element['title'] = $title;
        $element['hint'] = $hint;
        $element['description'] = $description;
        $element['src'] = $src;
        $element['align'] = $align;
        $element['class'] = $class;
        if ($this->use_paterns && !empty($this->element_paterns[$element['type']]) ) {
            while (list($key, $pat_value) = each ($this->element_paterns[$element['type']])) {
                if (empty($element[$key]) && !empty($pat_value))
                   $element[$key] = $pat_value;
            }
        }
        // additional element properties
        $properties = $this->_get_paterns($properties, $validate, $template);
        if (is_array($properties))
            while (list($key, $value) = each ($properties)) {
                $element[$key] = $value;
            }
        $this->elements[] = $element;
    }

    /**
    * form_builder::add_file()
    */
    function add_file($name, $title, $description='', $class='fob-file', $hint='', $size='', $properties='', $validate=array(), $template='') {
        $element = array();
        $element['type'] = 'file';
        $element['name'] = $name;
        $element['title'] = $title;
        $element['hint'] = $hint;
        $element['description'] = $description;
        $element['size'] = $size;
        $element['class'] = $class;
        if ($this->use_paterns && !empty($this->element_paterns[$element['type']]) ) {
            while (list($key, $pat_value) = each ($this->element_paterns[$element['type']])) {
                if (empty($element[$key]) && !empty($pat_value))
                   $element[$key] = $pat_value;
            }
        }
        // additional element properties
        $properties = $this->_get_paterns($properties, $validate, $template);
        if (is_array($properties))
            while (list($key, $value) = each ($properties)) {
                $element[$key] = $value;
            }
        $this->elements[] = $element;
    }

    /**
    * form_builder::add_select()
    */
    function add_select($required, $name, $value, $title, $description='', $selected='', $class='fob-select', $size='', $multi='', $properties='', $validate=array(), $template='', $attr='', $js='') {
        $element = array();
        $element['type'] = 'select';
        $element['name'] = $name;
        if (is_array($value)) {
            $element['value'] = $value;
        } else if (strstr($value, $this->val_separator)) {
            $values = explode($this->val_separator, $value);
            $element['value'] = array();
            while (list($key, $value) = each ($values)) {
                $element['value'][$value] = $value;
            }
        }
        $element['title'] = $title;
        $element['description'] = $description;
        $element['class'] = $class;
        $element['size'] = $size;
        $element['multi'] = $multi;
        $element['required'] = $required;
        $element['attr'] = $attr;
        $element['js'] = $js;
        if (is_array($selected)) {
            $element['selected'] = $selected;
        } else if (strstr($selected,$this->val_separator)) {
            $values = explode('||', $selected);
            $element['selected'] = array();
            while (list($key, $value) = each ($values)) {
                  $element['selected'][$value] = $value;
            }
        } else {
            $element['selected'][$selected] = '';
        }
        if ($this->use_paterns && !empty($this->element_paterns[$element['type']]) ) {
            while (list($key, $pat_value) = each ($this->element_paterns[$element['type']])) {
                if (empty($element[$key]) && !empty($pat_value))
                   $element[$key] = $pat_value;
            }
        }
        // additional element properties
        $properties = $this->_get_paterns($properties, $validate, $template);
        if (is_array($properties))
            while (list($key, $value) = each ($properties)) {
                $element[$key] = $value;
            }
        $this->elements[] = $element;
    }

    /**
    * form_builder::add_listbox ()
    */
    function add_listbox($required, $name, $value, $title, $description='', $selected='', $class='fob-listbox', $size='', $multi='', $properties='', $validate=array(), $template='') {
        $element = array();
        $element['type'] = 'listbox';
        $element['name'] = $name;
        if (is_array($value)) {
            $element['value'] = $value;
        } else if (strstr($value, $this->val_separator)) {
            $values = explode($this->val_separator, $value);
            $element['value'] = array();
            while (list($key, $value) = each ($values)) {
                $element['value'][$value] = $value;
            }
        }
        $element['title'] = $title;
        $element['description'] = $description;
        $element['class'] = $class;
        $element['size'] = $size;
        $element['multi'] = $multi;
        $element['required'] = $required;
        if (is_array($selected)) {
            $element['selected'] = $selected;
        } else if (strstr($selected,$this->val_separator)) {
            $values = explode('||', $selected);
            $element['selected'] = array();
            while (list($key, $value) = each ($values)) {
                  $element['selected'][$value] = $value;
            }
        } else {
            $element['selected'][$selected] = '';
        }
        if ($this->use_paterns && !empty($this->element_paterns[$element['type']]) ) {
            while (list($key, $pat_value) = each ($this->element_paterns[$element['type']])) {
                if (empty($element[$key]) && !empty($pat_value))
                   $element[$key] = $pat_value;
            }
        }
        // additional element properties
        $properties = $this->_get_paterns($properties, $validate, $template);
        if (is_array($properties))
            while (list($key, $value) = each ($properties)) {
                $element[$key] = $value;
            }
        $this->elements[] = $element;
    }

    /**
    * form_builder::add_textarea()
    */
    function add_textarea($required, $name, $value, $title, $description='', $cols='', $rows=5, $class='fob-textarea', $hint='', $properties='', $validate=array(), $template='') {
        $element = array();
        $element['type'] = 'textarea';
        $element['name'] = $name;
        $element['value'] = $value;
        $element['title'] = $title;
        $element['hint'] = $hint;
        $element['description'] = $description;
        $element['required'] = $required;
        $element['class'] = $class;
        if (!empty($cols)) {
            $element['cols'] = $cols;
        }
        if (!empty($rows)) {
            $element['rows'] = $rows;
        }
        if ($this->use_paterns && !empty($this->element_paterns[$element['type']]) ) {
            while (list($key, $pat_value) = each ($this->element_paterns[$element['type']])) {
                if (empty($element[$key]) && !empty($pat_value))
                   $element[$key] = $pat_value;
            }
        }
        // additional element properties
        $properties = $this->_get_paterns($properties, $validate, $template);
        if (is_array($properties))
            while (list($key, $value) = each ($properties)) {
                $element[$key] = $value;
            }
        $this->elements[] = $element;
    }

    /**
    * form_builder::add_wysiwyg()
    */
    function add_wysiwyg($required, $name, $value, $title, $description='', $cols='', $rows=30, $class='fob-wysiwyg', $hint='', $properties='', $validate=array(), $template='') {
        $element = array();
        $element['type'] = 'wysiwyg';
        $this->_register_wysiwyg_area($name);
        $element['name']        = $name;
        $element['id']          = $name;
        $element['value']       = $value;
        $element['title']       = $title;
        $element['hint']        = $hint;
        $element['description'] = $description;
        $element['required']    = $required;
        $element['class']       = $class;
        if (!empty($cols)) {
            $element['cols']    = $cols;
        }
        if (!empty($rows)) {
            $element['rows']    = $rows;
        }
        if ($this->use_paterns && !empty($this->element_paterns[$element['type']]) ) {
            while (list($key, $pat_value) = each ($this->element_paterns[$element['type']])) {
                if (empty($element[$key]) && !empty($pat_value))
                   $element[$key] = $pat_value;
            }
        }
        // additional element properties
        $properties = $this->_get_paterns($properties, $validate, $template);
        if (is_array($properties))
            while (list($key, $value) = each ($properties)) {
                $element[$key] = $value;
            }
        $this->elements[] = $element;
    }

    /**
    * form_builder::add_date()
    * @param string $description
    * @param string $class
    * @param string $hint
    * @param string $properties
    * @param string $validate
    * @param string $template
    */
    function add_date($name, $value, $title, $description='', $class='fob-date', $hint='', $properties='', $validate='', $template='') {
        static $counter = 0;
        $counter++;
        $element = array();
        $element['type'] = 'date';
        $element['name'] = $name;
        $element['value'] = $value;
        $element['calendar_id'] = $counter;
        $element['title'] = $title;
        $element['class'] = $class;
        $element['hint'] = $hint;
        $element['description'] = $description;
        if ($this->use_paterns && !empty($this->element_paterns[$element['type']]) ) {
            while (list($key, $pat_value) = each ($this->element_paterns[$element['type']])) {
                if (empty($element[$key]) && !empty($pat_value))
                   $element[$key] = $pat_value;
            }
        }
        // additional element properties
        $properties = $this->_get_paterns($properties, $validate, $template);
        /*if (is_array($properties))
            while (list($key, $value) = each ($properties)) {
                $element[$key] = $value;
            }
        */
        $element['id'] = 'js_date'.$counter;
        $this->elements[] = $element;
        if ($counter == 1) {
            $html = '<!-- dateInput -->';
            $html .= '<style type="text/css">@import url(/admin/css/calendar.css);</style>';
            $html .= '<script type="text/javascript" src="/admin/js/calendar.js"></script>';
            $html .= '<script type="text/javascript" src="/admin/js/calendar-ru.js"></script>';
            $html .= '<script type="text/javascript" src="/admin/js/calendar-setup.js"></script>';
            $html .= '<!-- /dateInput -->';
            $this->form_header[] = $html;
        }
        $html = '<!-- dateInput'.$counter.' -->';
        $html .= '<script type="text/javascript">Calendar.setup({inputField : "js_date'.$counter.'", ifFormat : "%d.%m.%Y", button : "jsc_trigger'.$counter.'"});</script>';
        $html .= '<!-- /dateInput'.$counter.' -->';
        $this->form_footer[] = $html;
        //echo '<pre>';print_r($element);die('@');
    }

    /**
    * form_builder::add_fieldset()
    */
    function add_fieldset($value, $title, $legend='', $description='', $class='fob-fieldset', $properties='', $template='') {
        $element = array();
        $element['type'] = 'fieldset';
        $element['name'] = $value['form']['name'];
        $element['value'] = $value;
        $element['title'] = $title;
        $element['legend'] = $legend;
        $element['description'] = $description;
        $element['class'] = $class;
        if ($this->use_paterns && !empty($this->element_paterns[$element['type']]) ) {
            while (list($key, $pat_value) = each ($this->element_paterns[$element['type']])) {
                if (empty($element[$key]) && !empty($pat_value))
                   $element[$key] = $pat_value;
            }
        }
        // additional element properties
        $properties = $this->_get_paterns($properties, array(), $template); //!!!
        if (is_array($properties))
            while (list($key, $value) = each ($properties)) {
                $element[$key] = $value;
            }
        $this->elements[] = $element;
    }

    /**
    * form_builder::add_html()
    */
    function add_html($name, $html, $template='html_code') {
        $element = array();
        $element['type'] = 'html';
        $element['name'] = $name;
        $element['value'] = $html;
        if (empty($template)) {
            $element['template'] = '';
        } else if (array_key_exists($template, $this->templates)) {
            $element['template'] = $this->templates[$template];
        } else {
            $element['template'] = $template;
        }
        $this->elements[] = $element;
    }

    /**
    * form_builder::test()
    */
    function test() {
        //print_r($this);
        print_r($this->elements);
    }

    /**
    * form_builder::display()
    * { displayes form }
    */
    function display($mode = 'echo') {
        if (!$this->_submit_checked) {
            $this->submited();
        }
        $block = $this->templates['form'];
        $form_tag = '<form method="'.$this->form['method'].'" action="'.$this->form['action'].'" name="'.$this->form['name'].'"';
        if (!empty($this->form['target']))
           $form_tag .= ' target="'.$this->form['target'].'"';
        if (!empty($this->form['enctype']))
           $form_tag .= ' enctype="'.$this->form['enctype'].'"';
        $form_tag .= ' '.$this->form['html'].'>';
        reset($this->elements);
        $elements = '';
        while (list($i, $element) = each ($this->elements)) {
            $html = $this->_format_element($element);
            $elements .= $html;
        }
        $this->add_variable('main', $elements);
        $this->add_variable('form', $form_tag);
        $block = $this->_parse_template($this->variables, $block);

        $header = implode("\n", $this->form_header);
        $footer = implode("\n", $this->form_footer);
        $block = $header.$block.$footer;
        if ($mode == 'echo') {
            echo $block;
        } else {
            return $block;
        }
    }
    /******************************************************************************/
    /**************************  FORM VALIDATION PART  ****************************/
    /******************************************************************************/
    /**
    * form_builder::submited()
    * @param string $check_var
    */
    function submited($check_var='') {
        return $this->is_submited($check_var);
    }
    function is_submited($check_var='') {
        if (!empty($check_var)) {
            $var = $check_var;
        } else {
            $var = $this->form_variable;
        }
        switch (strtoupper($this->form['method'])) {
            case 'POST':
                 $this->form_data = $_POST;
            break;

            case 'GET':
                 $this->form_data = $_GET;
            break;

            default:
                 $this->form_data = $_REQUEST;
            break;
        }
        if (!empty($this->form_data[$var])) {
            if ($this->check_referer && empty($_SERVER['HTTP_REFERER']) && (strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) === false) ) {
                $this->_is_submited = false;
            } else {
                $this->_is_submited = true;
            }
        } else {
            $this->_is_submited = false;
        }
        if ($this->_is_submited) {
            while (list($i, $element) = each ($this->elements)) {
                $submited_value = $this->_get_element_value($element['name']);
                $this->_is_valid_input($element, $submited_value);
            }
        }
        $this->_submit_checked = true;
        return $this->_is_submited;
    }

    /**
    * form_builder::is_valid_submit()
    */
    function is_valid_submit() {
        if (!$this->_submit_checked) {
            $this->submited();
        }
        if (  (!$this->_is_submited) || (count($this->errors)>0) ) {
            return false;
        } else {
            return true;
        }
    }

    /**
    * form_builder::get_submited_value()
    * { returns submited value for element $var_name }
    */
    function get_submited_value($var_name)
    {
        if (!$this->_submit_checked) {
            $this->submited();
        }
        if ($this->_is_submited)
           return $this->_get_element_value($var_name);
        else
           return false;
    }

    /**
    * form_builder::get_submited_values()
    * { returns all submited values }
    */
    function get_submited_aray()
    {
        $values = array();
        reset($this->elements);
        while (list($i, $element) = each ($this->elements)) {
              $submited_value = $this->_get_element_value($element['name']);
              $name = $this->_get_element_name($element['name']);
              $values[$name] = $submited_value;
        }
        return $values;
    }

    function get_submited_values()
    {
        $values = array();
        reset($this->elements);
        while (list($i, $element) = each ($this->elements)) {
              if ($element['type']=='listbox') {
                  $submited_value = $this->_get_listbox_value($element['name']);
              } else {
                  $submited_value = $this->_get_element_value($element['name']);
              }
              if ($element['type']=='checkbox') {
                  if ($submited_value)
                      $submited_value = 1;
              }
              if ($element['type']=='date') {
                  //$submited_value = (int) $submited_value;
              }

              if (substr($element['name'], 0, 1) == '$') {
                  $name = substr($element['name'], 1);
              } else if (strpos($element['name'], '[') > 0) {
                  $name = $element['name'];
              } else {
                  $name = $element['name'];
              }
              $this->_add_submited_value($name, $submited_value, $values);
        }
        return $values;
    }

    function _add_submited_value($name, $submited_value, &$values) {
        if (empty($name)) {
        } else if (substr($name, 0, 1) == '$') {
            $name = substr($name, 1);
            $this->_add_submited_value($name, $submited_value, $values);
        } else if (strpos($name, '[') > 0) {
            $pos        = strpos($name, '[');
            $key        = substr($name, 0, $pos);
            $sub_name   = substr($name, $pos+1, -1);
            $this->_add_submited_value($sub_name, $submited_value, $values[$key]);
        } else {
            $values[$name] = $submited_value;
        }
    }

    function get_submited_files()
    {
        $values = array();
        if (empty($_FILES)) {
            return false;
        }
        $files = $_FILES[$this->form['name']];
        while (list($name, $val) = each ($files['name'])) {
            $file = array(
                        'name'     => $files['name'][$name],
                        'type'     => $files['type'][$name],
                        'tmp_name' => $files['tmp_name'][$name],
                        'error'    => $files['error'][$name],
                        'size'     => $files['size'][$name]
                        );
            $values[$name] = $file;
        }
        return $values;
    }
    /******************************************************************************/
    /*****************************  INTERNAL FUNCTIONS  ***************************/
    /******************************************************************************/

    /**
    * form_builder::_parse_template()
    */
    function _parse_template($variables, $template)
    {
        while (list($name, $value) = each ($variables)) {
            if (!is_array($value)) {
                @$template = str_replace($this->var_prefix.$name.$this->var_suffix, $value, $template);
            }
        }
        return $template;
    }

    function _is_valid_input($element, $input)
    {
        $errors_count=0;
        $errors = array();
        // validate
        if (empty($element['validate'])) {
        } else if (is_array($element['validate']) && !empty($input)) {
            $validate = $element['validate'];
            // length
            if (!empty($validate['minlen']) || (!empty($validate['maxlen']))) {
                $length = strlen($input);
                if ($length < $validate['minlen']) {
                    $errors[] = $this->validate_error_messages['2short'];
                } else if ($length > $validate['maxlen']) {
                    $errors[] = $this->validate_error_messages['2long'];
                }
            }
            // format
            if (!empty($validate['function'])) {
                $function = $validate['function'];
                $result = $function($input);
                if ($result === false) {
                    $errors[] = $this->validate_error_messages['format'];
                }
            }
        } else if (!is_array($element['validate'])) {
            trigger_error('Invalid validate options for '.$element['name'] , $this->error_level);
        }
        // validate functions
        if (!empty($this->validate_functions[$element['name']]) && is_array($this->validate_functions[$element['name']])) {
                while (list($id, $func_info) = each ($this->validate_functions[$element['name']])) {
                    $vars = $func_info['vars'];
                    $parameters = array();
                    if (is_array($vars)) {
                    } else if (strstr($vars, $this->val_separator)) {
                        $values = explode($this->val_separator, $vars);
                        $vars = array();
                        while (list($i, $variable) = each ($values)) {
                            $vars[] = $variable;
                        }
                    } else {
                        $vars = array($vars);
                    }

                        while (list($key, $variable) = each ($vars)) {
                            if (substr($variable, 0, 1) <> '$')
                                $parameters[] = $variable;
                            else
                                $parameters[] = $this->_get_element_value(substr($variable, 1));
                        }

                    $function = $func_info['function'];
                    //$result = $function($parameters);
                    $result = call_user_func_array ($function, $parameters);
                    if (!$result) {
                        $errors[] = $func_info['message'];
                    }
                }
        }
        // required
        if ( !empty($element['required']) && empty($input) )
            $errors[] = $this->validate_error_messages['required'];
        if (count($errors)>0) {
            $this->errors[$element['name']] = $errors;
            return false;
        } else {
            return true;
        }
    }

    /**
    * form_builder::_get_element_name()
    */
    function _get_element_name($name) {
        if (empty($name)) {
            return false;
        } else if (substr($name, 0, 1) == '$') {
            return substr($name, 1);
        } else if (strpos($name, '[') > 0) {
            $name = str_replace('[', '][', $name);
            return $this->form_variable."[".$name;
        } else {
            return $this->form_variable."[".$name."]";
        }
    }

    /**
    * form_builder::_get_element_value()
    * { returns submited value for element $name}
    function _get_element_value($name) {
        $new_value = '';
        if (substr($name, 0, 1) == '$') {
            $name = substr($name, 1);
            if(!empty($this->form_data[$name])) {
                $new_value = $this->form_data[$name];
            }
        } else if (strpos($name, '[') > 0) {
            $pos = strpos($name, '[');
            $array =substr($name, 0, $pos);
            $key = substr($name, $pos+1, -1);
            if (!empty($this->form_data[$this->form_variable][$array][$key])) {
                $new_value = $this->form_data[$this->form_variable][$array][$key];
            } else {$new_value = 0;}
        } else {
            if (!empty($this->form_data[$this->form_variable][$name])) {
                $new_value = $this->form_data[$this->form_variable][$name];
            }
        }
        return $new_value;
    }

    */
    function _get_element_value($name) {
        $new_value = '';
        if (substr($name, 0, 1) == '$') {
            $name = substr($name, 1);
            $new_value = $this->_get_element_value2($name, $this->form_data);
            //$new_value = '11-'.$new_value;
        } else if (strpos($name, '[') > 0) {
            $new_value = $this->_get_element_value2($name, $this->form_data[$this->form_variable]);
            //$new_value = '12-'.$new_value;
        } else {
            if (!empty($this->form_data[$this->form_variable][$name])) {
                $new_value = $this->form_data[$this->form_variable][$name];
                //$new_value = '13-'.$new_value;
            }
        }
        return $new_value;
    }

    function _get_element_value2($name, $datasource) {
        //die("_get_element_value2($name, $datasource)");
        $new_value = '';
        if (strpos($name, '[') > 0) {
            $pos = strpos($name, '[');
            $array =substr($name, 0, $pos);
            $key = substr($name, $pos+1, -1);
            if (!empty($datasource[$array])) {
                $new_value = $this->_get_element_value2($key, $datasource[$array]);
                //$new_value = 210;
            } else {
                /*
                $new_value = '211-'."$array<br>";
                print_r($datasource);
                die($new_value);
                */
            }
        } else {
            if (!empty($datasource[$name])) {
                $new_value = $datasource[$name];
                //$new_value = 220;
            } else {
                //$new_value = 221;
            }
        }
        return $new_value;
    }

    /**
    * form_builder::_get_element_value()
    * { returns submited value for element $name}
    */
    function _get_listbox_value($name) {
        $new_value = '';
        $submited_text   = '';
        $submited_select = '';
        if (substr($name, 0, 1) == '$') {
            $name = substr($name, 1);
            if (!empty($this->form_data[$name])) {
                $submited_text   =  $this->form_data[$name]['input'];
                $submited_select =  $this->form_data[$name]['select'];
            }
        } else if (strpos($name, '[') > 0) {
            $pos = strpos($name, '[');
            $array =substr($name, 0, $pos);
            $key = substr($name, $pos+1, -1);
            if (!empty($this->form_data[$this->form_variable][$array][$key])) {
                //$new_value = $this->form_data[$this->form_variable][$array][$key];
                $submited_text   =  $this->form_data[$this->form_variable][$array][$key]['input'];
                $submited_select =  $this->form_data[$this->form_variable][$array][$key]['select'];
            } else {$new_value = 0;}
        } else {
            if (!empty($this->form_data[$this->form_variable][$name])) {
                //$new_value = $this->form_data[$this->form_variable][$name];
                $submited_text   =  $this->form_data[$this->form_variable][$name]['input'];
                $submited_select =  $this->form_data[$this->form_variable][$name]['select'];
            }
        }
        if (!empty($submited_text)) {
            $new_value = $submited_text;
        } else {
            $new_value = $submited_select;
        }
        //die($name.'='.$new_value);
        return $new_value;
    }

    /**
    * form_builder::_get_element_patern()
    */
    function _get_element_patern($properties) {
        $patern = array();
        if (empty($properties)) {
            $patern = array();
        } else if (is_array($properties)) {
            $patern = $properties;
        } else if (array_key_exists($properties, $this->element_paterns)) {
            $patern = $this->element_paterns[$properties];
        }
        return $patern;
    }

    /**
    * form_builder::_get_validate_patern()
    */
    function _get_validate_patern($validate) {
        if (empty($validate)) {
            $patern = array();
        } else if (is_array($validate)) {
            $patern = $validate;
        } else if (array_key_exists($validate, $this->validate_paterns)) {
            $patern = $this->validate_paterns[$validate];
        }
        return $patern;
    }

    /**
    * form_builder::_get_paterns()
    */
    function _get_paterns($properties, $validate, $template) {
        // additional element properties
        $element = array();
        $properties = $this->_get_element_patern($properties);
        if (is_array($properties))
            while (list($key, $value) = each ($properties)) {
                $element[$key] = $value;
            }
        // validation options
        $element['validate'] = $this->_get_validate_patern($validate);
        // element template
        if (empty($template))
            $element['template'] = '';
        else if (array_key_exists($template, $this->templates))
            $element['template'] = $this->templates[$template];
        else
            $element['template'] = $template;
        return $element;
    }

    /**
    * form_builder::format_input()
    */
    function _format_input($element)
    {
            /*
                TYPE=[ text | password | checkbox | radio | submit | reset | file | hidden | image | button ] (type of input)
                NAME=CDATA (key in submitted form)
                VALUE=CDATA (value of input)
                CHECKED (check radio button or checkbox)
                SIZE=CDATA (suggested number of characters for text input)
                MAXLENGTH=Number (maximum number of characters for text input)
                SRC=URI (source for image)
                ALT=CDATA (alternate text for image input)
                USEMAP=URI (client-side image map)
                ALIGN=[ top | middle | bottom | left | right ] (alignment of image input)
                DISABLED (disable element)
                READONLY (prevent changes)
                ACCEPT=ContentTypes (media types for file upload)
                ACCESSKEY=Character (shortcut key)
                TABINDEX=Number (position in tabbing order)
                ONFOCUS=Script (element received focus)
                ONBLUR=Script (element lost focus)
                ONSELECT=Script (element text selected)
                ONCHANGE=Script (element value changed)

                common attributes
                ID
                CLASS
                STYLE
                TITLE

                ONCLICK, when the mouse button is clicked on an element;
                ONDBLCLICK, when the mouse button is double-clicked on an element;
                ONMOUSEDOWN, when the mouse button is pressed over an element;
                ONMOUSEUP, when the mouse button is released over an element;
                ONMOUSEOVER, when the mouse is moved onto an element;
                ONMOUSEMOVE, when the mouse is moved while over an element;
                ONMOUSEOUT, when the mouse is moved away from an element;
                ONKEYPRESS, when a key is pressed and released over an element;
                ONKEYDOWN, when a key is pressed down over an element;
                ONKEYUP
            */
        $html = "<input type=\"".$element['type']."\"";
        if (!empty($element['name']))
           $html .= " name=\"".$element['name']."\"";
        //if (!empty($element['value']))
        if ( array_key_exists('value', $element) )
           $html .= " value=\"".$element['value']."\"";
        /*else
           $html .= " value=\"\"";*/
        if (!empty($element['class']))
           $html .= " class=\"".$element['class']."\"";
        if (!empty($element['hint']))
           $html .= " title=\"".$element['hint']."\"";
        if (!empty($element['id']))
           $html .= " id=\"".$element['id']."\"";
        if (!empty($element['disabled']))
           $html .= " disabled";
        if (!empty($element['readonly']))
           $html .= " readonly";
        if (!empty($element['accesskey']))
           $html .= " accesskey=\"".$element['accesskey']."\"";
        switch ($element['type']) {
            case "file":
                 if (!empty($element['size']))
                    $html .= " size=\"".$element['size']."\"";
                 break;
            case "password":
            case "text":
                 if (!empty($element['size']))
                    $html .= " size=\"".$element['size']."\"";
                 if (!empty($element['maxlength']))
                    $html .= " maxlength=\"".$element['maxlength']."\"";
                 break;
            case "submit":
            case "reset":
            case "button":
                 break;
            case "hidden":
                 break;
            case "checkbox":
            case "radio":
            //echo ($element['checked'].' == '.$element['value']." / ");
                if ( !empty($element['checked']) && !strstr($element['value'], $this->val_separator) ) {
                    //echo '<pre>';print_r($element);die('@');
                    $html .= ' checked';
                }
                 //}
                 break;
            case "image":
                 if (!empty($element['src']))
                    $html .= " src=\"".$element['src']."\"";
                 if (!empty($element['align']))
                    $html .= " align=\"".$element['align']."\"";
                 if (!empty($element['hint']))
                    $html .= " alt=\"".$element['hint']."\"";
                 break;
        } // switch
        if (!empty($element['html']))
           $html .= " ".$element['html'];
        $html .= ">";
        if ( ($element['type']=='radio') && strstr($element['value'], $this->val_separator) ) {
             $values = explode($this->val_separator, $element['value']);
             $captions = explode($this->val_separator, $element['caption']);
             $html = '';
             $block = $this->templates['radio_item'];
             while (list($key, $value) = each ($values)) {
                   $sub_element = $element;
                   $sub_element['value'] = $values[$key];
                   $sub_element['caption'] = $captions[$key];
                   if ($sub_element['value'] == $element['checked']) {
                       $sub_element['checked'] = true;
                   } else {
                       $sub_element['checked'] = false;
                   }
                   $input = $this->_format_input($sub_element);
                   $html .= $this->_parse_template(array(
                                                           'caption' => '&nbsp;'.$captions[$key],
                                                           'input' => $input),
                                                   $this->templates['radio_item']);
             }
        }
        return $html;
    }

    /**
    * form_builder::_get_selected_key()
    */
    function _get_selected_key($sel_key, $values)
    {
        return $sel_key;
        /*
        пошук ключа, якщо вхідний параметр текстове значення
        $sel_key = array_search($sel_value, $values);
        if ($sel_key) {
            return $sel_key;
        } else {
            while (list($key, $value) = each ($values)) {
                if (is_array($value)) {
                   $sel_key = array_search($sel_value, $value);
                   if ($sel_key)
                      return $sel_key;
                }
            }
        }
        */
    }

    /**
    * form_builder::_format_select()
    */
    function _format_select($element)
    {
        $html = "<select";
        if (!empty($element['name']))
           $html .= " name=\"".$element['name']."\"";
        if (!empty($element['id']))
           $html .= " id=\"".$element['id']."\"";

        if (!empty($element['class']))
           $html .= " class=\"".$element['class']."\"";
        if (!empty($element['multi']))
           $html .= " multiple";
        if (!empty($element['disabled']))
           $html .= " disabled";
        if (!empty($element['readonly']))
           $html .= " readonly";
        if (!empty($element['accesskey']))
           $html .= " accesskey=\"".$element['accesskey']."\"";
        if (!empty($element['size']))
           $html .= " size=\"".$element['size']."\"";
        if (!empty($element['html']))
           $html .= " ".$element['html'];
        if (!empty($element['attr']))
           $html .= " ".$element['attr'];
        if (!empty($element['js']))
           $html = $element['js'].$html;
        // options
        $options = '';
        if (!empty($element['value']) && is_array($element['value'])) {
            while (list($key, $value) = each ($element['value'])) {
                //echo $key."<br>";
                if (is_array($value)) {
                    // OPTGROUP
                    $optgroup = $this->_format_options($value, $element['selected']);
                    $options .= "<optgroup label=\"".$key."\">".$optgroup."</optgroup>";
                } else {
                    // OPTION
                    $options .= $this->_format_options(array($key => $value), $element['selected']);
                }
            }
        } else if (!empty($element['value'])){
            trigger_error('Form Builder: invalid format of SELECT values for '.$element['name'],  $this->error_level);
        } else {
            trigger_error('Form Builder: Undefined SELECT values for '.$element['name'],  $this->error_level);
        }
        // end
        $html .= ">".$options."</select>";
        return $html;
    }


    /**
    * form_builder::_format_select()
    */
    function _format_listbox($element)
    {
    $element_id = md5($this->form['name'].$element['name']);
    $select_element =  $element;
    $select_element['name'] = $element['name'].'[select]';
    $select_element['id'] = $element_id.'_select';
    $select_html = $this->_format_select($select_element);
    $input_element  =  $element;
    $input_element['name'] = $element['name'].'[input]';
    $input_element['id'] = $element_id.'_input';
    $input_element['type'] = 'text';
    $input_element['size'] = '';
    $input_element['value'] = '';
    $input_html = $this->_format_input($input_element);
    $block = $this->templates['listbox'];
    $tpl_vars = array(
                'input_div_display'     => 'none',
                'select_div_display'    => 'block',
                'listbox_id'            => $element_id,
                'select_input'          => $select_html,
                'text_input'            => $input_html
              );
    $block = $this->_parse_template($tpl_vars, $block);
    //$block = $this->_parse_template($element, $block);
    /*
        echo "<pre>";
        echo $input_html;
        echo "\n\n\n";
        echo $select_html;
        echo "\n\n\n";
        print_r($element);
        die();
    /**/

        // end
        return $block;
    }

    /**
    * form_builder::_format_options()
    */
    function _format_options($values, $selected)
    {
        $options = '';
        while (list($key, $value) = each ($values)) {
             if (array_key_exists($key, $selected) ) {
                $sel = ' selected ';
             } else {
                 $sel = '';
             }
             $options .= "<option ".$sel."value=\"".$key."\">".$value."</option>";
        }
        return $options;
    }


    /**
    * form_builder::_format_element()
    */
    function _format_element($element) {
        $html = '';
        $required = '';
        $label = '';
        $error = '';
        if (!$this->_submit_checked) {
            $this->submited();
        }
        $submited = $this->_is_submited;
        if ($submited) {
            if ($element['type']=='listbox') {
                $submited_value = $this->_get_listbox_value($element['name']);
            } else {
                $submited_value = $this->_get_element_value($element['name']);
            }
            $submited_value = ( get_magic_quotes_gpc() == 1 ? StripSlashes($submited_value) : $submited_value );
            $submited_value = htmlspecialchars($submited_value, ENT_QUOTES );
            if (!empty($this->errors[$element['name']]) && count($this->errors[$element['name']])>0) {
                $error_message = '<li>'.implode('<br><li>', $this->errors[$element['name']]);
                $error = $this->_parse_template(array('error_message' => $error_message), $this->templates['error']);
            }
        }
        $element['name'] = $this->_get_element_name($element['name']);
        switch ($element['type']) {
            case "fieldset":
//                 $fieldset = $element['value']->display('return');
                 $fieldset = $element['value'];
                 if (!empty($element['legend'])) {
                    $legend = "<legend>".$element['legend']."</legend>";
                 } else {
                    $legend = '';
                 }
                 $html = "<fieldset ";
                 if (!empty($element['class']))
                    $html .= " class=\"".$element['class']."\"";
                 if (!empty($element['html']))
                    $html .= " ".$element['html'];
                 $html .= ">".$legend.$fieldset."</fieldset>";
                 break;
            case "text":
            case "password":
                 if ($submited) {
                     $element['value'] = $submited_value;
                 } else {
                     $element['value'] = htmlspecialchars($element['value'], ENT_QUOTES );
                 }
                 $html = $this->_format_input($element);
                 break;
            case "checkbox":
                 if ($submited) {
                     $element['checked'] = $submited_value;
                 }
                 $html = $this->_format_input($element);
                 break;
            case "radio":
                 if ($submited) {
                     $element['checked'] = $submited_value;
                 }
                 $html = $this->_format_input($element);
                 if ( ($element['type']=='radio') && strstr($element['value'], $this->val_separator) ) {
                     $element['caption'] = '';
                 }
                 break;
            case "submit":
            case "reset":
            case "file":
            case "hidden":
            case "image":
            case "button":
                 $html = $this->_format_input($element);
                 break;
            case "select":
                 if ($submited) {
                    $key = $this->_get_selected_key($submited_value, $element['value']);
                    $element['selected'] = Array();
                    $element['selected'][$key] = '';
                 }
                 $html = $this->_format_select($element);
                 break;
            case "listbox":
                 if ($submited) {
                    $key = $this->_get_selected_key($submited_value, $element['value']);
                    $element['selected'] = Array();
                    $element['selected'][$key] = '';
                 }
                 $html = $this->_format_listbox($element);
                 break;
            case 'wysiwyg':
                $this->_init_wysiwyg();
            case 'textarea':
                 if ($submited) {
                    $element['value'] = $submited_value;
                 } else {
                     $element['value'] = htmlspecialchars($element['value'], ENT_QUOTES );
                 }
                 $html = "<textarea ";
                 if (!empty($element['id']))
                    $html .= " id=\"".$element['id']."\"";
                 if (!empty($element['class']))
                    $html .= " class=\"".$element['class']."\"";
                 if (!empty($element['name']))
                    $html .= " name=\"".$element['name']."\"";
                 if (!empty($element['cols']))
                    $html .= " cols=\"".$element['cols']."\"";
                 if (!empty($element['rows']))
                    $html .= " rows=\"".$element['rows']."\"";
                 if (!empty($element['hint']))
                    $html .= " title=\"".$element['hint']."\"";
                 if (!empty($element['html']))
                    $html .= " ".$element['html'];
                 $html .= ">".$element['value']."</textarea>";
                 break;
            case 'html':
                 $html .= $element['value'];
                 break;
            case "date":
                 if ($submited) {
                     $element['value'] = $submited_value;
                 } else {
                     $element['value'] = $element['value'];
                 }
                 $element['type'] = 'text';
                 $html = $this->_format_input($element);
                 $html .= '<img src="css/calendar_img.gif" id="jsc_trigger'.$element['calendar_id'].'" style="cursor: pointer;" border=0>';
                 //$html .= '<button id="jsc_trigger'.$element['calendar_id'].'">...</button>';
                 break;

        } // switch

        if (!empty($element['required'])) {
            $required = $this->templates['required'];
        }
        if (!empty($element['template'])) {
            $block = $element['template'];
        } else {
            $block = $this->templates['input'];
        }
        if (!empty($element['title'])) {
            $label = '<label for="'.$element['name'].'">'.$element['title'].'</label>';
        }
        if (empty($element['caption'])) {
            $element['caption'] = '';
        }
        if (empty($element['description'])) {
            $element['description'] = '';
        }
        $tpl_vars = array(
                  'caption'  => $element['caption'],
                  'input'    => $html,
                  'required' => $required,
                  'title'    => $label,
                  'description' => $element['description'],
                  'error' => $error,
                  );
        $block = $this->_parse_template($tpl_vars, $block);
        $block = $this->_parse_template($element, $block);
        return $block;
    }

    /* WYSIWYG */
    function _register_wysiwyg_area($name){
        $this->wysiwyg_areas[]  = $name;
    }

    function _init_wysiwyg(){
//		echo "<pre>"; print_r(get_object_vars($this)); echo "</pre>";
//		echo "<pre>"; print_r($this->wysiwyg_areas); echo "</pre>";
        static $is_loaded = false;
        if ($is_loaded || count($this->wysiwyg_areas)==0) {
            return true;
        }
		$elements_wysiwyg = array();
		foreach($this->wysiwyg_areas as $elements) {
			$elements_wysiwyg[] = $elements;
		}
//		echo "<pre>"; print_r($elements_wysiwyg); echo "</pre>";


// Dwalin 10 nov 2013
        $html = '<!-- WYSYWYG -->';
		
		$urlCK = str_replace("index.php", "", cms_ADMIN_URL)."ckeditor/";
        
		$html .= '<script type="text/javascript" src="'.$urlCK.'ckeditor.js"></script>';
		//$html .= '<script src="'.$urlCK.'plugins/tinybrowser/tb_tinymce.js.php" type="text/javascript"></script>';
        $html .= '<script type="text/javascript">';
		
		$html .= 'window.onload = function() {';


// Dwalin 10 nov 2013
		
		foreach($this->wysiwyg_areas as $elements) {
			$html .= 'CKEDITOR.replace(\''.$elements.'\');';
			//echo 'CKEDITOR.replace(\''.$elements.'\');';
		}
		
		$html .= '};';
	
				
        $html .= '</script><!-- /WYSYWYG -->';
        $this->form_header[] = $html;
        $is_loaded = true;
    }

} /* form builder class end */

class fob_fieldset extends form_builder
{
    var $templates = array(
            'form' => '[#main]',
            'input' => '[#title][#required]&nbsp;[#input]&nbsp;[#description][#caption][#error]<br>'
            );
}

?>
