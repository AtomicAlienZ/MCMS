<?php

class Shop_Fieldset {
	const DB_TABLE = 'pm_shop_fieldset';

	const FIELDTYPE_STRING      = 'string';
	const FIELDTYPE_TEXT        = 'text';
	const FIELDTYPE_HTML        = 'html';
	const FIELDTYPE_SELECT      = 'select';
	const FIELDTYPE_MULTISELECT = 'multiselect';
	const FIELDTYPE_DATE        = 'date';
	const FIELDTYPE_INT         = 'int';
	const FIELDTYPE_FLOAT       = 'float';

	public static $fieldTypes = array(
		self::FIELDTYPE_STRING,
		self::FIELDTYPE_TEXT,
		self::FIELDTYPE_HTML,
		self::FIELDTYPE_SELECT,
		self::FIELDTYPE_MULTISELECT,
		self::FIELDTYPE_DATE,
		self::FIELDTYPE_INT,
		self::FIELDTYPE_FLOAT
	);

	// TODO field modifiers like POSITIVE int and REGEXED string

	protected $id;
	protected $name;
	protected $fields;

	protected function __construct($dbData) {
		$this->id = (int)$dbData['id'];
		$this->name = $dbData['name'];

		$this->fields = json_decode($dbData['data'], true);
		if (!is_array($this->fields)) {
			$this->fields = array();
		}
	}

	/**
	 * @return self[]
	 */
	public static function getList () {
		$return = array();
		$db = cms_admin::getDBC();

		$result = $db->Execute('SELECT * FROM `'.self::DB_TABLE.'`');
		while ($data = $result->fetchRow()) {
			$return[] = new self($data);
		}
//var_dump($return);die;
		return $return;
	}

	/**
	 * @param $id
	 * @return self
	 */
	public static function getById ($id) {
		$result = cms_admin::getDBC()->Execute('SELECT * FROM `'.self::DB_TABLE.'` WHERE `id` = '.(int)$id);
		while ($data = $result->fetchRow()) {
			return new self($data);
		}
		return null;
	}

	public function getId() {
		return $this->id;
	}

	public function getName() {
		return $this->name;
	}

	public function getFields() {
		return $this->fields;
	}
}