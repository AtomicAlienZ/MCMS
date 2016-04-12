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
				$this->template = $execute;
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

		if (!isset($arguments['action'])) {
			$arguments['action'] = 'catTree';
		}

		switch ($arguments['action']) {
			case 'addCat':
			case 'editCat':
				$this->template = 'form';
				$output = $this->editAddCategory(isset($arguments['id']) ? (int)$arguments['id'] : null, $arguments['action']);
				break;
			default:
				$output['tree'] = Shop_Category::getTree();
				break;
		}

		return $output;
	}

	protected function editAddCategory ($id, $action) {
		$item = Shop_Category::getById($id);
		$fob = $this->init_fob('',$_SERVER['REQUEST_URI']);

		// Needed fields
		$fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
		$fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[action]', $action);
		if ($id) {
			$fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $id);
		}

		$fob->add_html('sep', $this->cms->int_add_h1($item ? "Редактирование категории" : "Новая категория"));

		// Alias
		$fob->add_text(true, 'alias', ($item ? $item->getAlias() : ''), 'Alias');

		$possibleParentsArray = array(
			0 => '---'
		);

		$possibleParents = ($item ? $item->getPossibleParents() : Shop_Category::getAll());

		foreach ($possibleParents as $parent) {
			$possibleParentsArray[$parent->getId()] = $parent->getName('en') . ' (id='.$parent->getId().', '.($parent->isActive() ? 'enabled' : 'disabled').')';
		}

		$fob->add_select(false, 'id_parent', $possibleParentsArray, 'Parent category', '', ($item ? $item->getIdParent() : 0));

		// Names
		foreach ($this->langs as $lang) {
			$fob->add_html('info', $this->cms->int_add_h2($lang));

			// Name
			$fob->add_text(true, 'name_' . $lang, ($item ? $item->getName($lang) : ''), 'Name', $lang);

			// Description
			$fob->add_wysiwyg(false, 'description_' . $lang, ($item ? $item->getDescription($lang) : ''), 'Description', $lang);

			// Page Title
			$fob->add_text(true, 'title_' . $lang, ($item ? $item->getPageTitle($lang) : ''), 'Page Title', $lang);

			// Meta keywords
			$fob->add_textarea(false, 'meta_desc_' . $lang, ($item ? $item->getMetaKeywords($lang) : ''), 'Meta description', $lang);

			// Meta description
			$fob->add_textarea(false, 'meta_keywords_' . $lang, ($item ? $item->getMetaDescription($lang) : ''), 'Meta keywords', $lang);
		}

		$fob->add_html('info', $this->cms->int_add_h2(''));

		$fob->add_checkbox(false, 'is_active', 'y', 'Включено', '', '', ($item ? $item->isActive() : ''));

		// Buttons
		$fob->add_button('submit', 'save', ($item ? 'Сохранить' : 'Добавить'), '');
		$fob->add_button('submit', 'save_stay', ($item ? 'Сохранить и остаться' : 'Добавить и перейти'), '');

		if ($fob->is_submited() && $fob->is_valid_submit()) {
			$values = $fob->get_submited_values();

			try {
				// Saving
				if ($item) {
					$item->save($values);
				}
				// Creating new
				else {
					$item = Shop_Category::create($values);
				}

				// Redirect to tree
				if ($values['save_stay'] != '' && $item) {
					$target_url = $this->cms->format_url($this->plugin['name'], '', array("id" => $item->getId(), "action" => 'editCat'));
				}
				// Redirect to item
				else {
					$target_url = $this->cms->format_url($this->plugin['name'], '', array("action" => 'catTree'));
				}
				header('Location: ' . $target_url);
				die;
			}
			catch (MultiException $e) {
				foreach ($e->getMessages() as $message) {
					$this->cms->int_set_message('top', $message['message'].(isset($message['field']) ? '('.$message['field'].')' : ''), $message['caption'], 'error');
				}
			}
		}

		return array(
			'item'=>$item,
			'form'=>$fob
		);
	}

	protected function fieldsets ($arguments) {
		$output = array();

		switch ($arguments['action']) {
			case 'addFieldset':
			case 'editFieldset':
				$this->template = 'editAddFieldset';
				$output = $this->editAddFieldset(isset($arguments['id']) ? (int)$arguments['id'] : null, $arguments['action']);
				break;
			default:
				$output['list'] = Shop_Fieldset::getList();
				break;
		}

		return $output;
	}

	protected function editAddFieldset ($id, $action) {
		$item = Shop_Fieldset::getById($id);
//		$fob = $this->init_fob('',$_SERVER['REQUEST_URI']);
//
//		// Needed fields
//		$fob->add_hidden('$' . $this->cms->request_vars['plugin'], $this->plugin['name']);
//		$fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[action]', $action);
//		if ($id) {
//			$fob->add_hidden('$' . $this->cms->request_vars['arguments'] . '[id]', $id);
//		}
//
//		$fob->add_html('sep', $this->cms->int_add_h1($item ? "Редактирование набора полей" : "Новый набор полей"));

		return array(
			'item'=>$item
		);
	}

	// Функция инициализации поддерживаемых на сайте языков
	protected function initLanguages()
	{
		// Сохраняем список языков и текущий язык в переменных класса
		$this->langs = cms_admin::getLanguages();
		$this->lang = cms_admin::getLanguage();
	}
}