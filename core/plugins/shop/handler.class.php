<?php

class shop_handler {
	function parseTemplate ($template, $data) {

		// Рассчитываем имя файла содержащего шаблон
		$tpl_file = $this->plugin['path'] . 'tpls/' . $template . '.tpl';

		if (file_exists($tpl_file) && is_readable($tpl_file)) {

			// Присваиваем переменные шаблону
			$this->cms->smarty->assign(array('output' => $data));

			// Производим обработку шаблона и получаем готовый HTML-код в качестве результата
			$output = $this->cms->smarty->fetch($tpl_file, $this->cms->smarty->cache_id);

		} else {
			$output = false;
		}

		return $output;
	}

    function exec($params) {
	    // Requiring needed
	    require_once $this->plugin['path'].'Shop_Category.php';
	    require_once $this->plugin['path'].'Shop_Fieldset.php';
//	    require_once $this->plugin['path'].'Shop_.php';

	    $return = array();

	    switch ($params['tpl_alias']) {
		    case 'shop':
				// TODO add check for user access rights (whether he has a shop and his shop is not banned)
				$return = $this->commandShop();
			    break;
		    case 'default':
		    default:
				/*  DO NOTHING */
			    break;
	    }

	    return $this->parseTemplate($params['tpl_alias'], $return);
    }

	protected function commandShop () {
		$return = array();

		$return['categories'] = Shop_Category::getTree();

		return $return;
	}
}