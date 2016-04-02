<?php
class plugin_admin_interface extends cms_plugin_admin {
	protected static $sections = array(
		'catTree' => 'tab_catTree',
		'items' => 'tab_items',
		'fieldsets' => 'tab_fieldsets'
	);

	protected $template;
	protected $languages;
	protected $lang;

	public function main($command, $arguments) {
		$this->init();
		$this->initLanguages();
		$this->cms->int_set_h1($this->plugin['title']);

		$tabs = array();
		$execute = null;

		foreach (self::$sections as $section => $title) {
			$tabs[$section] = "<a href=\"" . $this->cms->format_url($this->plugin["name"], $section, array()) . "\">" . $title . "</a>";

			if ($command == $section) {
				$execute = $section;
			}
		}

		if (!$execute) {
			$execute = 'catTree';
			$this->template = $execute;
		}

		$return = $this->$execute($arguments);

		return $this->cms->parse_template(
			array(
				'tabs' => $this->cms->int_add_tabs($tabs, $execute),
				'output' => $this->cms->getSmartyTemplateResult($this, $this->plugin['name'].'/tpl/'.$this->template.'.tpl', is_array($return) ? $return : array())
			), 'workarea'
		);
	}

	protected function catTree ($arguments) {
		$output = array();

		switch ($arguments['action']) {
			case 'addCat':
			case 'editCat':
				$this->template = 'catAddEdit';
				$output = $this->editAddCategory((int)$arguments['id']);
				break;
			default:
				$output['tree'] = Shop_Category::getTree();
				break;
		}

		return $output;
	}

	protected function editAddCategory ($id) {
		var_dump($this->langs);die;
		return array('item'=>Shop_Category::getById($id));

	}


	// Функция инициализации поддерживаемых на сайте языков
	protected function initLanguages()
	{

		$languages = array();
		$lang = "";

		// Читаем список поддерживаемых на сайте языков
		$sql = "SELECT language_id, short_title, alias FROM " . $this->cms->tables["languages"] . " ORDER BY ord ASC";
		$result = $this->dbc->Execute($sql);
		if (!$result) {
			$this->cms->int_set_message('top', $this->dbc->ErrorMsg() . '<br>Query: ' . $sql, 'SQL Error', 'error');
			return false;
		}

		if ($result->RecordCount() > 0) {

			$counter = 1;
			while ($record = $result->FetchRow()) {

				// Заполняем массив поддерживаемых языков
				$languages[$record["language_id"]] = $record["alias"];

				// Определяем текущий язык сайта как первый в списке
				if ($counter == 1) {
					$lang = $record["alias"];
				}

				$counter++;
			}
		}

		// Сохраняем список языков и текущий язык в переменных класса
		$this->langs = $languages;
		$this->lang = $lang;
	}
}