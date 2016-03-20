<?php
/**
* class message_box
*
*/
class message_box {

    /**
    * determines how messages in group should be printed
    *     group_by_type       all messages in the group will be grouped according to their type
    *     group_all           all messages will be grouped into one
    *     separate            all messages will be printed separately
    *
    */
    var $msg_group_mode = "group_by_type";

    /**
    * message_box::$messages_separator
    * separator between messages in group
    */
    var $messages_separator = '<br>';

    /**
    * message_box::$images_dir
    * url for directory where images are located
    */
    var $images_dir    = 'images/';

    /**
    * message_box::$var_prefix
    * prefix to mark variables used in templates
    */
    var $var_prefix    = '{$';

    /**
    * message_box::$var_suffix
    * suffix to mark variables used in templates
    */
    var $var_suffix    = '}';

    /**
    * message_box::$message_types
    * deffault message types and their captions
    */
    var $message_types = array (
                       'error'        => '�������',
                       'warning'      => '������������',
                       'information'  => '����������',
                       'confirmation' => 'ϳ�����������',
                       'help'         => '��������',
                       'custom'       => ''
                       );

    /**
    * message_box::$groups
    * arrays for message groups
    */
    var $groups        = array();

    /**
    * message_box::$templates
    * templates
    */
    var $templates     = array(
                       'message_box' => '',
                       'message_body'        => '<font face="verdana,tahoma,arial,sans-serif" size="-1"><b>{$caption}</b><br>{$message}</font><center>{$buttons}</center>',
                       'button'      => '&nbsp;[<a title="{$title}" href="{$url}" style="text-decoration:none; color: #000000; font-size:95%;"><b>{$title}</b></a>]&nbsp;'
                       //'button' => '&nbsp;<input type="button" title="{#title}" value="{#title}" onclick="window.location.href={#url}" />&nbsp;'
                       );

    /**
    * message_box::message_box()
    * class constructor
    *
    * @param    string    $images_dir      url to directory conntaining images used in templates for message_box
    */
    function message_box($images_dir = 'images/') {
$_message_box_tpl = <<<TPL
<br><table align="center" width="100%" border="0" cellpadding="5" cellspacing="0" style="border: 1px solid #000000; border-left:none; border-right:none; width: 70%; background-color: #ffffff; ">
 <tr>
   <td valign="top" width="1%"><img src="{#images_dir}mb_{#message_type}.gif" alt="{#message_type_caption}" border="0" vspace="5" hspace="5"></td>
   <td valign="top">{#body}</td>
 </tr>
</table><br>
TPL;
    $this->templates['message_box'] = $_message_box_tpl;
    $this->images_dir = $images_dir;
    }

    /**
    * message_box::display()
    * dispalys message box
    *
    * @access    public
    * @param    string    $message    message to display
    * @param    string    $caption    message caption
    * @param    string    $msg_type   message type
    * @param    array     $buttons    array of links(buttons) if user's feedback required
    *
    * Example:
       $buttons = array (
           'yes' => 'http://d13.com.ua?yes',
           'no'  => 'http://d13.com.ua?no');
       $msg_box->display("Delete selected messages?", 'Confirm action', 'confirmation', $buttons);
    */
    function display($message, $caption='', $msg_type = 'information', $buttons=array()) {
        $message_box = $this->get_message($message, $caption, $msg_type, $buttons);
        echo $message_box;
    }
    
    /**
    * message_box::get_message()
    * returns html code with message box
    *
    * @access    public
    * @param    string    $message    message to display
    * @param    string    $caption    message caption
    * @param    string    $msg_type   message type
    * @param    array     $buttons    array of links(buttons) if user's feedback required
    *
    * Example:
       $buttons = array (
           'yes' => 'http://d13.com.ua?yes',
           'no'  => 'http://d13.com.ua?no');
       $msg_box->get_message("Delete selected messages?", 'Confirm action', 'confirmation', $buttons);
    */
    function get_message($message, $caption='', $msg_type = 'information', $buttons=array()) {
        $msg_body_vars = array();
        $msg_body_vars['caption']             = $caption;
        $msg_body_vars['message']             = $message;
        $msg_body_vars['buttons']             = $buttons;
        //$message_body = $this->_parse_template($msg_body_vars, $this->templates['message_body']);
        $message_body = $this->_format_message_body($msg_body_vars);
        $msg_box_vars = array();
        if (!array_key_exists(strtolower($msg_type), $this->message_types)) {
            $msg_box_vars['message_type'] = 'custom';
        } else {
            $msg_box_vars['message_type'] = strtolower($msg_type);
        }
        $msg_box_vars['images_dir']           = $this->images_dir;
        $msg_box_vars['body']                 = $message_body;
        $msg_box_vars['message_type_caption'] = $this->message_types[$msg_box_vars['message_type']];
        $message_box = $this->_parse_template($msg_box_vars, $this->templates['message_box']);
        return $message_box;
    }

    /**
    * message_box::add()
    * adds message to specified group
    *
    * @access    public
    * @param    string    $caption
    * @param    string    $msg_type
    * @param    array     $buttons
    */
    function add($group, $message, $caption='', $msg_type = 'information', $buttons=array()) {
        $message_type = strtolower($msg_type);
        $msg_box = array();
        if (!array_key_exists($message_type, $this->message_types)) {
            $msg_box['message_type'] = 'custom';
        } else {
            $msg_box['message_type'] = $message_type;
        }
        $msg_box['caption']              = $caption;
        $msg_box['message']              = $message;
        $msg_box['buttons']              = $buttons;
        switch ($this->msg_group_mode) {
            case "separate":
                 $this->groups[$group][] = $msg_box;
                 break;

            case "group_all":
                 $this->groups[$group][] = $msg_box;
                 break;

            case "group_by_type":
            default:
                 $this->groups[$group][$message_type][] = $msg_box;
                 break;
        }
    }

    /**
    * message_box::display_group()
    * displays specified group
    *
    * @access    public
    * @param     string      $group        name of the group which should be displayed
    * @param     boolean     $clear        if set to true, all messages in group will be deleted after output
    */
    function display_group($group, $clear = true) {
        echo $this->get_group($group, $clear);
    }

    /**
    * message_box::display_group()
    * displays specified group
    *
    * @access    public
    * @param     string      $group        name of the group which should be displayed
    * @param     boolean     $clear        if set to true, all messages in group will be deleted after output
    */
    function get_group($group, $clear = true) {
        if (!empty($group) && array_key_exists($group, $this->groups) && is_array($this->groups[$group])) {
            $result = '';
            switch ($this->msg_group_mode) {
                case "separate":
                     //$this->groups[$group][] = $msg_box;
                     while (list($msg_id, $msg_vars) = each ($this->groups[$group])) {
                         $result .= $this->get_message($msg_vars['message'], $msg_vars['caption'], $msg_vars['message_type'], $msg_vars['buttons']);
                     }
                     break;

                case "group_all":
                     //$this->groups[$group][] = $msg_box;
                     $message_body = '';
                     $sep = '';
                     while (list($msg_id, $msg_vars) = each ($this->groups[$group])) {
                        $message_body .= $sep.$this->_format_message_body($msg_vars);
                        $sep = $this->messages_separator;
                     }
                     $msg_box = array();
                     $msg_box['message_type']         = 'custom';
                     $msg_box['body']                 = $message_body;
                     $message_box = $this->_format_message_box($msg_box);
                     $result = $message_box;
                     break;

                case "group_by_type":
                default:
                     //$this->groups[$group][$message_type][] = $msg_box;
                     while (list($msg_type, $messages_arr) = each ($this->groups[$group])) {
                         $message_body = '';
                         $sep = '';
                         while (list($id, $msg_body_vars) = each ($messages_arr)) {
                            $message_body .= $sep.$this->_format_message_body($msg_body_vars);
                            $sep = $this->messages_separator;
                         }
                         $msg_box = array();
                         $msg_box['message_type']         = $msg_type;
                         $msg_box['body']                 = $message_body;
                         $message_box = $this->_format_message_box($msg_box);
                         $result .= $message_box;
                     }
                     break;
            }
            if ($clear) {
               $this->groups[$group] = array();
            }
            return $result;
        } else {
            return false;
        }
    }

    ############################## Internal functions ##########################
    


    /**
    * message_box::_parse_template()
    * replace all variables in template with their values
    *
    * @access    private
    * @param     array     $variables        array of variables used in specified template
    * @param     string    $template         string containing template
    */
    function _parse_template($variables, $template)
    {
        while (list($name, $value) = each ($variables)) {
            $template = str_replace($this->var_prefix.$name.$this->var_suffix, $value, $template);
        }
        return $template;
    }

    /**
    * message_box::_format_message_body()
    * formats message body (caption, message text, buutons)
    *
    * @access    private
    * @param     array     $msg_body        array should consists of variables used in template "message_body"
    */
    function _format_message_body($msg_body) {
        $buttons_html = '';
        if (is_array($msg_body['buttons']))
            while (list($title, $url) = each ($msg_body['buttons'])) {
                $buttons_html .= $this->_parse_template(array('url' => $url, 'title' => $title), $this->templates['button']);
            }
        $msg_body['buttons']             = $buttons_html;
        $message_body = $this->_parse_template($msg_body, $this->templates['message_body']);
        return $message_body;
    }

    /**
    * message_box::_format_message_box()
    * formats message box
    *
    * @access    private
    * @param     array     $msg_box        array should consists of variables used in template "message_box"
    */
    function _format_message_box($msg_box) {
        $msg_box['images_dir']           = $this->images_dir;
        $msg_box['message_type_caption'] = $this->message_types[$msg_box['message_type']];
        $message_box = $this->_parse_template($msg_box, $this->templates['message_box']);
        return $message_box;
    }
}
?>
