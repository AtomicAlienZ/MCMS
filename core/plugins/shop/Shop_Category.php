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
	 * @var array
	 */
	protected $media;

	/**
	 * @var self
	 */
	protected $parent;

	/**
	 * @var self[]
	 */
	protected $children = array();

	protected static function _init () {
		if (!isset(self::$pool)) {
			$db = cms_admin::getDBC();

			$result = $db->Execute('SELECT * FROM `'.self::DB_TABLE.'`');

	//			var_dump($result->getAll());

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

	public static function getTree () {
		self::_init();

		return self::$tree;
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

	public function __construct($DBData) {
		$this->data = $DBData;

		$this->id = (int)$this->data['id'];
		$this->media = json_decode($this->data['media'], true);

		unset($this->data['id']);
		unset($this->data['media']);

		if ($this->media === null) {
			$this->media = array();
		}
	}

	public function getId() {
		return $this->id;
	}

	public function getIdParent() {
		return (int)$this->data['id_parent'];
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
}