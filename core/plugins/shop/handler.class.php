<?php

class shop_handler {
	protected $_template = 'root';

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
	    require_once $this->plugin['path'].'Shop_Item.php';

//	    var_dump(Shop_Item::getByIdsArray(array(1,2,3,4,5)));die;

	    // TODO add check for access rights

	    $return = array();

	    $this->cms->vars_falseget['shop'] = 'true';

	    try {
		    switch ($params['tpl_alias']) {
			    case 'shop':
				    // TODO add check for user access rights (whether he has a shop and his shop is not banned)
				    $return = $this->commandShop();
				    break;
			    case 'categories':
				    // TODO add check for user access rights (whether he has a shop and his shop is not banned)
				    $return = $this->commandCategories();
				    break;
			    case 'basket':
//					die('!');
				    break;
			    case 'default':
			    default:
					/*  DO NOTHING */
				    break;
		    }
	    }
	    catch (Exception $e) {
		    $this->cms->vars_falseget['shop'] = 'false';
	    }

	    $path = $this->page_info['path'];
	    $path = end($path);

	    $return['_baseURL'] = $path['url'];

	    $return['_tpl'] = 'shop/'.$this->_template;

	    return $this->parseTemplate($params['tpl_alias'], $return);
    }

	protected function commandCategories () {
		$return = array();

		$this->_template = 'categories';

		$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
		$action = isset($_GET['action']) ? $_GET['action'] : false;

		// Item view

		// Category view
		if ($action == 'item') {
			$return['item'] = Shop_Item::getById($id);
			$this->_template = 'item';

			if (!$return['item'] || !$return['item']->isVisible()) {
				$this->cms->vars_falseget['shop'] = 'false';
			}
		}
		elseif (!$action && $id) {
			$return['category'] = Shop_Category::getById($id);
			$this->_template = 'category';

			if (!$return['category'] || !$return['category']->isVisible()) {
				$this->cms->vars_falseget['shop'] = 'false';
			}
			else {
				$return['list'] = Shop_Item::getByCagtegoryId($id);
				for ($i = 0, $c = count($return['list']); $i < $c; $i++) {
					$return['list'][$i] = $return['list'][$i]->toDisplayArray(cms_core::getLanguage());
				}
			}
		}
		// Categories list
		else {
			$return['tree'] = Shop_Category::getTree();
		}

		return $return;
	}

	protected function commandShop () {
		$return = array();

		$action = isset($_GET['action']) ? $_GET['action'] : '';
		$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

		if ($action == 'add') {
			$return['category'] = Shop_Category::getById($id);

			if (!$return['category'] || !$return['category']->isVisible()) {
				throw new Exception(404);
			}

			$this->_template = 'addedit';
		}
		elseif ($action == 'edit') {
			$return['item'] = Shop_Item::getById($id);

			if (!$return['item']) {
				throw new Exception(404);
			}

			$return['category'] = $return['item']->getCategory();

			$this->_template = 'addedit';
		}
		else {
			$return['categories'] = Shop_Category::getTree();
			$return['list'] = Shop_Item::getByUserId($this->page_info['user_data']['uid']);
		}

		if (($action == 'add' || $action == 'edit') && isset($_POST['FORM']) && is_array($_POST['FORM'])) {

			if ($action == 'edit' && isset($return['item'])) {
				// TODO
				$return['item']->save($_POST['FORM'], isset($_FILES['FORM']) ? $_FILES['FORM'] : array());
			}
			else if ($action == 'add') {
				Shop_Item::create($_POST['FORM'], isset($_FILES['FORM']) ? $_FILES['FORM'] : array(), $this->page_info['user_data']['uid'], $return['category']->getId());
			}

			$path = $this->page_info['path'];
			$path = end($path);

			header('Location: '.$path['url']);die;
		}

		return $return;
	}
}