<?php
class Shop_Item {
	const DB_TABLE = 'pm_shop_items';

	protected $id = 0;

	protected $data = array();

	protected $fields;

	/**
	 * @var Shop_Category
	 */
	protected $category;

	/**
	 * @var Shop_Fieldset
	 */
	protected $fieldset;

	protected function __construct ($data) {
		$this->id = (int)$data['id'];

		$this->data = $data;

		$this->fields = json_decode($data['fields']);

		if (!is_array($this->fields)) {
			$this->fields = array();
		}
	}

	protected static function getInsUpdArray($data, self $updateItem = null) {
		$return = array();
		$langs = cms_core::getLanguages();
		$db = cms_core::getDBC();

		foreach ($langs as $lang) {
			$return['name_'.$lang] = $db->realEscape(strip_tags($data['name'][$lang]));
			$return['desc_'.$lang] = $db->realEscape(preg_replace('/[\r\n\0]+/',"\n",strip_tags($data['desc'][$lang])));
		}

		$return['price'] = number_format((float)$data['price'], 2, '.', '');

		$return['is_active'] = $data['is_active'] == 'y' ? 'y' : 'n';

		$return['id_user'] = $data['id_user'];
		$return['id_category'] = $data['id_category'];

		$return['fields'] = array();

		foreach ($data['fields'] as $k=>$v) {
			$return['fields'][$k] = $v;
		}

		$return['fields'] = $db->realEscape(json_encode($return['fields']));

		return $return;
	}

	public static function create($data, $idUser, $idCategory) {
		$data['id_user'] = (int)$idUser;
		$data['id_category'] = (int)$idCategory;

		if (!$data['id_user'] || !$data['id_category']) {
			throw new Exception('WRONG PARAMETERS');
		}

		$insarr = self::getInsUpdArray($data);

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

		return self::getById($id);
	}

	public static function getByUserId ($id) {
		$return = array();
		$db = cms_core::getDBC();

		$result = $db->Execute('SELECT * FROM `'.self::DB_TABLE.'` WHERE `id_user` = '.(int)$id);
		while ($data = $result->fetchRow()) {
			$return[] = new self($data);
		}

		return $return;
	}

	public static function getById ($id) {
		$db = cms_core::getDBC();

		$result = $db->Execute('SELECT * FROM `'.self::DB_TABLE.'` WHERE `id` = '.(int)$id);
		while ($data = $result->fetchRow()) {
			return new self($data);
		}

		return null;
	}

	public function get($what, $lang = '') {
		$key = $what.($lang ? '_'.$lang : '');
		if (isset($this->data[$key])) {
			return $this->data[$key];
		}

		return '';
	}

	public function getCategory () {
		if (!isset($this->category)) {
			$this->category = Shop_Category::getById($this->getIdCategory());
		}

		return $this->category;
	}

	public function getFields ($lang) {
		if (!isset($this->fieldset) || isset($this->fields)) {
			$this->fieldset = $this->getCategory()->getFieldset();

			if ($this->fieldset) {
				return $this->fieldset->compileFields($this->fields, $lang);
			}
		}

		return array();
	}

	public function getId()         { return (int)$this->id; }
	public function isActive()      { return $this->get('is_active') == 'y'; }
	public function isBanned()      { return $this->get('is_banned') == 'y'; }
	public function getName($lang)  { return $this->get('name', $lang); }
	public function getPrice()      { return $this->get('price'); }
	public function getIdCategory() { return (int)$this->get('id_category'); }
}