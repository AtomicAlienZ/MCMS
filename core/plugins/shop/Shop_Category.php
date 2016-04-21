<?php
/**
 * Shop Category implementation
 */
class Shop_Category {
	const DB_TABLE = 'pm_shop_catregories';

	protected static $tree;

	/**
	 * @var self[]
	 */
	protected static $pool;

	protected $id;
	protected $data;

	/**
	 * @var self
	 */
	protected $parent;

	/**
	 * @var self[]
	 */
	protected $children = array();

	/**
	 * @var Shop_Fieldset | null
	 */
	protected $fieldset = false;

	protected static function _init ($force = false) {
		if (!isset(self::$pool) || $force) {
			$db = cms_core::getDBC();

			$result = $db->Execute('SELECT * FROM `'.self::DB_TABLE.'`');

			while ($tmp = $result->fetchRow()) {
				$o = new self($tmp);

				self::$pool[$o->getId()] = $o;
			}

			// Turning hash to tree and setting dependencies
			self::$tree = self::_recursiveBuildTree();
		}
	}

	protected static function _recursiveBuildTree (self $parent = null) {
		$return = [];

		foreach (self::$pool as $item) {
			if ($item->getIdParent() == ($parent ? $parent->getId() : 0)) {
				$item->parent = $parent;
				$item->children = self::_recursiveBuildTree($item);
				$return[$item->getId()] = $item;
			}
		}

		return $return;
	}

	/**
	 * @param self[] $subTreeArray
	 * @return self[]
	 */
	protected static function _getSubtreeAsArray($subTreeArray) {
		$return = array();

		foreach ($subTreeArray as $item) {
			$return[$item->getId()] = $item;

			$return = $return + self::_getSubtreeAsArray($item->getChildren());
		}

		return $return;
	}

	/**
	 * Prepares data array for insert/update
	 *
	 * @param $data
	 * @param Shop_Category|null $updateItem
	 * @return array
	 * @throws MultiException
	 */
	protected static function _getInsUpdArray ($data, self $updateItem = null) {
		$return = array();
		$errors = array();
		$db = cms_core::getDBC();
		$langs = cms_core::getLanguages();

		// Checking alias for validity
		if (!preg_match('/^[a-z0-9_-]{2,}$/uiU', $data['alias'])) {
			$errors['alias'] = 'wrongformat';
		}

		// Checking alias for existence
		$result = $db->Execute('SELECT `id` FROM `'.self::DB_TABLE.'` WHERE `alias` = "'.$db->realEscape($data['alias']).'"'.($updateItem ? ' AND `id` != '.$updateItem->getId() : ''));
		if ($result->RowCount()) {
			$errors['alias'] = 'exists';
		}

		$return['alias'] = $db->realEscape($data['alias']);

		// Checking names for existence and preparing localized data
		foreach ($langs as $lang) {
			$return['name_'.$lang] = $db->realEscape(trim($data['name_'.$lang]));
			$return['title_'.$lang] = $db->realEscape(trim($data['title_'.$lang]));
			$return['description_'.$lang] = $db->realEscape(trim($data['description_'.$lang]));
			$return['meta_desc_'.$lang] = $db->realEscape(trim($data['meta_desc_'.$lang]));
			$return['meta_keywords_'.$lang] = $db->realEscape(trim($data['meta_keywords_'.$lang]));

			$result = $db->Execute('SELECT `id` FROM `'.self::DB_TABLE.'` WHERE `name_'.$lang.'` = "'.$return['name_'.$lang].'"'.($updateItem ? ' AND `id` != '.$updateItem->getId() : ''));
			if ($result->RowCount()) {
				$errors['name_'.$lang] = 'exists';
			}
		}

		// Checking parent id
		$return['id_parent'] = (int)$data['id_parent'];
		if ($updateItem && !array_key_exists($return['id_parent'], $updateItem->getPossibleParents()) && $return['id_parent'] != 0) {
			$errors['id_parent'] = 'wrongparent';
		}

		$return['id_fieldset'] = (int)$data['id_fieldset'];

		$return['is_active'] = $data['is_active'] ? 'y' : 'n';

		if ($updateItem) {
			$return['datetime_modified'] = date('Y-m-d H:i:s');
		}
		else {
			$return['datetime_created'] = date('Y-m-d H:i:s');
		}

		if (count($errors)) {
			foreach ($errors as $k=>$v) {
				$errors[$k] = array(
					'caption' => 'Field error',
					'field' => $k,
					'message' => $v
				);
			}

			throw new MultiException($errors);
		}

		return $return;
	}

	public static function getTree () {
		self::_init();

		return self::$tree;
	}

	public static function getAll() {
		self::_init();

		return self::$pool;
	}

	/**
	 * @param $id
	 * @return null|Shop_Category
	 */
	public static function getById ($id) {
		self::_init();

		if (isset(self::$pool[$id])) {
			return self::$pool[$id];
		}

		return null;
	}

	/**
	 * @param $data
	 * @return self
	 * @throws Exception
	 */
	public static function create ($data) {
		$insarr = self::_getInsUpdArray($data);

		$db = cms_core::getDBC();

		$sql = 'INSERT INTO `'.self::DB_TABLE.'` (`'.implode('`,`',array_keys($insarr)).'`) VALUES ("'.implode('","',array_values($insarr)).'")';
		$result = $db->Execute($sql);
		if (!$result) {
			throw new MultiException(array(
				array(
					'caption' => 'DB error!',
					'message' => $db->ErrorMsg() . '<br>Query: ' . $sql
				)
			));
		}

		$id = $db->_insertid();

		self::_init(true);

		return self::getById($id);
	}

	public function __construct($DBData) {
		$this->data = $DBData;

		$this->id = (int)$this->data['id'];

		unset($this->data['id']);
	}

	/**
	 * @return Shop_Category | null
	 */
	public function getParent() {
		return $this->parent;
	}

	/**
	 * @return Shop_Category[]
	 */
	public function getChildren () {
		return $this->children;
	}

	/**
	 * @return Shop_Category[]
	 */
	public function getPossibleParents () {
		$return = array();
		$forbiddenIds = array_keys(self::_getSubtreeAsArray($this->children));
		$forbiddenIds[] = $this->getId();
		$forbiddenIds = array_combine($forbiddenIds,$forbiddenIds);

		foreach (self::$pool as $id => $item) {
			if (!isset($forbiddenIds[$id])) {
				$return[$id] = $item;
			}
		}

		return $return;
	}

	public function save ($data) {
		$updarr = self::_getInsUpdArray($data, $this);

		foreach ($updarr as $k=>$v) {
			$updarr[$k] = '`'.$k.'` = "'.$v.'"';
		}

		$db = cms_core::getDBC();

		$sql = 'UPDATE `'.self::DB_TABLE.'` SET '.implode(', ', $updarr).' WHERE `id` = '.$this->getId();
		$result = $db->Execute($sql);
		if (!$result) {
			throw new MultiException(array(
				array(
					'caption' => 'DB error!',
					'message' => $db->ErrorMsg() . '<br>Query: ' . $sql
				)
			));
		}
	}

	// Getters
	public function get($what, $lang = '') {
		$key = $what.($lang ? '_'.$lang : '');
		if (isset($this->data[$key])) {
			return $this->data[$key];
		}

		return '';
	}

	public function getId()                   { return (int)$this->id; }
	public function getIdParent()             { return (int)$this->get('id_parent'); }
	public function getIdFieldset()           { return (int)$this->get('id_fieldset'); }
	public function isActive()                { return $this->get('is_active') == 'y'; }
	public function getAlias()                { return $this->get('alias'); }
	public function getName($lang)            { return $this->get('name', $lang); }
	public function getPageTitle($lang)       { return $this->get('title', $lang); }
	public function getDescription($lang)     { return $this->get('description', $lang); }
	public function getMetaDescription($lang) { return $this->get('meta_desc', $lang); }
	public function getMetaKeywords($lang)    { return $this->get('meta_keywords', $lang); }

	public function getFieldset() {
		if ($this->fieldset === false) {
			$this->fieldset = Shop_Fieldset::getById($this->get('id_fieldset'));
		}

		return $this->fieldset;
	}

	public function canAddItems () {
		$return = $this->isActive();

		$parent = $this;

		while ($parent = $parent->getParent()) {
			$return = $return && $parent->isActive();
		}

		return $return;
	}
}