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

	/**
	 * @var self[]
	 */
	protected static $pool = array();

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

		// Processing fields
		foreach ($this->fields as $fk => $fv) {
			if (isset($fv['options']) && is_array($fv['options'])) {
				$this->fields[$fk]['optionsById'] = array();

				foreach ($fv['options'] as $option) {
					$this->fields[$fk]['optionsById'][$option['id']] = $option;
				}
			}
		}
	}

	/**
	 * @return self[]
	 */
	public static function getList () {
		$return = array();
		$db = cms_core::getDBC();

		$result = $db->Execute('SELECT * FROM `'.self::DB_TABLE.'`');
		while ($data = $result->fetchRow()) {
			$o = new self($data);
			self::$pool[$o->getId()] = $o;
			$return[$o->getId()] = $o;
		}

		return $return;
	}

	/**
	 * @param $id
	 * @return self
	 */
	public static function getById ($id) {
		if (isset(self::$pool[$id])) {
			return self::$pool[$id];
		}

		$result = cms_core::getDBC()->Execute('SELECT * FROM `'.self::DB_TABLE.'` WHERE `id` = '.(int)$id);
		while ($data = $result->fetchRow()) {
			$o = new self($data);
			self::$pool[$o->getId()] = $o;
			return $o;
		}

		return null;
	}

	public static function create ($data) {
		$errors = array();
		$fields = array();

		$db = cms_core::getDBC();

		$fields['name'] = $db->realEscape($data['name']);

		// Preparing and fixing fields
		foreach ($data['fields'] as $k => $v) {
			$data['fields'][$k]['required'] = $data['fields'][$k]['required'] !== 'false';
			$data['fields'][$k]['enabled'] = $data['fields'][$k]['enabled'] !== 'false';
		}

		$fields['data'] = $db->realEscape(json_encode($data['fields']));

		$result = $db->Execute('SELECT `id` FROM `'.self::DB_TABLE.'` WHERE `name` = "'.$fields['name'].'"');
		if ($result->RowCount()) {
			$errors['name'] = 'exists';
		}

		if (count($errors) > 0) {
			foreach ($errors as $k=>$v) {
				$errors[$k] = array(
					'caption' => 'Field error',
					'field' => $k,
					'message' => $v
				);
			}

			throw new MultiException($errors);
		}

		$sql = 'INSERT INTO `'.self::DB_TABLE.'` (`'.implode('`,`',array_keys($fields)).'`) VALUES ("'.implode('","',array_values($fields)).'")';
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

	public function save ($data) {
		$errors = array();
		$fields = array();

		$db = cms_core::getDBC();

		$fields['name'] = $db->realEscape($data['name']);

		// Preparing and fixing fields
		foreach ($data['fields'] as $k => $v) {
			$data['fields'][$k]['required'] = $data['fields'][$k]['required'] !== 'false';
			$data['fields'][$k]['enabled'] = $data['fields'][$k]['enabled'] !== 'false';
		}

		$fields['data'] = $db->realEscape(json_encode($data['fields']));

		$result = $db->Execute('SELECT `id` FROM `'.self::DB_TABLE.'` WHERE `name` = "'.$fields['name'].'" AND `id` != '.$this->getId());
		if ($result->RowCount()) {
			$errors['name'] = 'exists';
		}

		if (count($errors) > 0) {
			foreach ($errors as $k=>$v) {
				$errors[$k] = array(
					'caption' => 'Field error',
					'field' => $k,
					'message' => $v
				);
			}

			throw new MultiException($errors);
		}

		$sql = array();
		foreach ($fields as $k=>$v) {
			$sql[] = '`'.$k.'` = "'.$v.'"';
		}
		$sql = 'UPDATE `'.self::DB_TABLE.'` SET '.implode(', ', $sql).' WHERE `id` = '.$this->getId();// (`'.implode('`,`',array_keys($fields)).'`) VALUES ("'.implode('","',array_values($fields)).'")';
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

	public function getId() {
		return $this->id;
	}

	public function getName() {
		return $this->name;
	}

	public function getFields() {
		return $this->fields;
	}

	public function compileFields ($data, $lang) {
		$return = array();

		foreach ($this->getFields() as $field) {
			$tmp = array(
				'id' => $field['id'],
				'name' => $field['names'][$lang],
				'enabled' => $field['enabled']
			);

			switch ($field['type']) {
				case self::FIELDTYPE_DATE:
					// TODO
					break;

				case self::FIELDTYPE_SELECT:
				case self::FIELDTYPE_MULTISELECT:
					$tmp['value'] = array();
					$values = $data[$field['id']];

					if (!is_array($values)) {
						$values = array($values);
					}

					foreach ($values as $value) {
						if (isset($field['optionsById'][$value])) {
							$tmp['value'][$value] = $field['optionsById'][$value]['names'][$lang];
						}
					}

					break;

				case self::FIELDTYPE_STRING:
				case self::FIELDTYPE_TEXT:
				case self::FIELDTYPE_HTML:
				case self::FIELDTYPE_INT:
				case self::FIELDTYPE_FLOAT:
				default:
					$tmp['value'] = $data[$field['id']];
					break;
			}

			$return[] = $tmp;
		}

		return $return;
	}
}