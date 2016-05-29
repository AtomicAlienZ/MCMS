<?php
class Shop_Order {
	const DB_TABLE = 'pm_shop_orders';
	const DB_DEPS_TABLE = 'pm_shop_orders_items';

	const COOKIE = 'MCMSSHOR'; // MCMS SHop ORder
	const COOKIE_TTL = 432000;//5*86400;

	const STATUS_NEW     = 'new';
	const STATUS_PENDING = 'pending';
	const STATUS_DONE    = 'done';

	protected $id;
	protected $data;
	protected $price;

	/**
	 * @var array
	 *
	 * key - itemId
	 * value - array(
	 *      'item' => Shop_Item,
	 *      'quantity' => int
	 * )
	 */
	protected $items;

	protected function __construct($data) {
		if (!isset($data['total_price'])) {
			$data['total_price'] = 0;
		}

		$this->id = (int)$data['id'];
		$this->data = $data;
		$this->price = (float)$data['total_price'];
	}

	protected static function getHash () {
		return md5(microtime(true).'-'.mt_rand(1, 10000));
	}

	public static function getCurrent ($uid) {
		$uid = (int)$uid;
		$db = cms_core::getDBC();
		$return = null;

		if ($uid > 0) {
			$sql = 'SELECT * FROM
						`'.self::DB_TABLE.'`
					WHERE
						`id_user` = '.$uid.'
						AND
						`status` != "'.self::STATUS_DONE.'"';
		}
		elseif (isset($_COOKIE[self::COOKIE]) && strlen($_COOKIE[self::COOKIE]) == 32) {
			$sql = 'SELECT * FROM
						`'.self::DB_TABLE.'`
					WHERE
						`id_user` = 0
						AND
						`status` != "'.self::STATUS_DONE.'"
						AND
						`hash` = "'.$db->realEscape($_COOKIE[self::COOKIE]).'"';
		}

		if (isset($sql)) {
			$result = $db->Execute($sql);

			if ($result) {
				$tmp = $result->fetchRow();

				if ($tmp) {
					$return = new self($tmp);
				}
			}
			else {
				throw new Exception($db->ErrorMsg().' SQL: '.$sql);
			}
		}

		return $return;
	}

	public static function create ($uid, $setCookie = true) {
		$uid = (int)$uid;
		$hash = '';

		$db = cms_core::getDBC();

		if ($uid == 0) {
			$hash = self::getHash();

			if ($setCookie) {
				setcookie(self::COOKIE, $hash, time() + self::COOKIE_TTL, '/');
			}
		}

		$modifDate = date('Y-m-d H:i:s');

		$db->Execute('INSERT INTO `'.self::DB_TABLE.'` SET
						`id_user` = '.$uid.',
						`hash` = "'.$db->realEscape($hash).'",
						`datetime_modified` = "'.$modifDate.'",
						`status` = "'.self::STATUS_NEW.'"');

		$return = new self(array(
			'id' => $db->_insertid(),
			'hash' => $hash,
			'id_user' => $uid,
			'datetime_modified' => $modifDate,
			'status' => self::STATUS_NEW,
			'total_price' => 0
		));
		$return->items = array(); // No items yet

		return $return;
	}

	public static function addToCurrent ($uid, Shop_Item $item, $quantity) {
		$order = self::getCurrent($uid);

		if (!$order) {
			$order = self::create($uid);
		}

		$order->addItem($item, $quantity);

		return $order;
	}

	public static function removeFromCurrent ($uid, Shop_Item $item, $quantity) {
		$order = self::getCurrent($uid);

		if (!$order) {
			$order = self::create($uid);
		}

		$order->removeItem($item, $quantity);

		return $order;
	}

	public function getId ()     { return $this->id; }
	public function getStatus () { return $this->data['status']; }
	public function getPrice ()  { return $this->price; }

	public function getItems () {
		if (!isset($this->items)) {
			$db = cms_core::getDBC();

			$result = $db->Execute('SELECT * FROM `'.self::DB_DEPS_TABLE.'` WHERE `id_order` = '.$this->getId());
			while ($tmp = $result->fetchRow()) {
				$this->items[(int)$tmp['id_item']] = array(
					'item' => false,
					'quantity' => (int)$tmp['quantity'],
					'linkid' => (int)$tmp['id']
				);
			}

			// Getting Items
			$itemObjects = Shop_Item::getByIdsArray(array_keys($this->items));
			foreach ($this->items as $id => $array) {
				if (isset($itemObjects[$id]) && is_object($itemObjects[$id])) {
					$this->items[$id]['item'] = $itemObjects[$id];
				}
				else {
					unset($this->items[$id]);
				}
			}
		}

		return $this->items;
	}

	public function addItem (Shop_Item $item, $quantity) {
		$quantity = (int)$quantity;

		if ($item->isVisible() && $quantity > 0 && $this->getStatus() == self::STATUS_NEW) {
			$this->getItems();
			$db = cms_core::getDBC();

			if (isset($this->items[$item->getId()])) {
				$this->items[$item->getId()]['quantity'] += $quantity;

				$sql = 'UPDATE
							`'.self::DB_DEPS_TABLE.'`
						SET
							`quantity` = `quantity` + '.$quantity.',
							`price` = '.$item->getPrice().'
						WHERE
							`id` = '.$this->items[$item->getId()]['linkid'];

				$dbr = $db->Execute($sql);

				if (!$dbr) {
					throw new Exception($db->ErrorMsg().' SQL: '.$sql);
				}
			}
			else {
				$sql = 'INSERT INTO
							`'.self::DB_DEPS_TABLE.'`
						SET
							`id_order` = '.$this->getId().',
							`id_item` = '.$item->getId().',
							`price` = '.$item->getPrice().',
							`quantity` = '.$quantity.'
							';

				$dbr = $db->Execute($sql);

				if (!$dbr) {
					throw new Exception($db->ErrorMsg().' SQL: '.$sql);
				}

				$this->items[$item->getId()] = array(
					'item' => $item,
					'quantity' => $quantity,
					'linkid' => (int)$db->_insertid()
				);
			}
		}

		// Recalculating price
		$this->recalculatePrice();
	}

	public function removeItem (Shop_Item $item, $quantity) {
		$this->getItems();

		if ($quantity !== INF) {
			$quantity = (int)$quantity;
		}

		if (isset($this->items[$item->getId()]) && $quantity > 0) {
			$db = cms_core::getDBC();

			if ($this->items[$item->getId()]['quantity'] > $quantity) {
				$this->items[$item->getId()]['quantity'] -= $quantity;

				$db->Execute('UPDATE `'.self::DB_DEPS_TABLE.'` SET `quantity` = `quantity` - '.$quantity.' WHERE `id` = '.$this->items[$item->getId()]['linkid']);
			}
			else {
				unset($this->items[$item->getId()]);

				$db->Execute('DELETE FROM `'.self::DB_DEPS_TABLE.'` WHERE `id` = '.$this->items[$item->getId()]['linkid']);
			}

			$this->recalculatePrice();
		}
	}

	public function toArray() {
		$return = array(
			'id' => $this->getId(),
			'price' => $this->getPrice(),
			'items' => $this->getItems()
		);

		foreach ($return['items'] as $id => $linkdata) {
			$return['items'][$id]['item'] = $return['items'][$id]['item']->toDisplayArray(cms_core::getLanguage());
		}

		return $return;
	}

	protected function recalculatePrice () {
		$items = $this->getItems();
		$this->price = 0;

		foreach ($items as $id=>$linkdata) {
			$this->price += $linkdata['item']->getPrice() * (int)$linkdata['quantity'];
		}

		$db = cms_core::getDBC();
		$sql = 'UPDATE `'.self::DB_TABLE.'` SET `total_price` = '.$this->price.' WHERE `id` = '.$this->getId();
		$dbr = $db->Execute($sql);

		if (!$dbr) {
			throw new Exception($db->ErrorMsg().' SQL: '.$sql);
		}
	}
}