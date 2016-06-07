<?php
class Shop_Item {
	const DB_TABLE = 'pm_shop_items';
	const MEDIA_STORAGE = 'content/shop/items/'; // Trailing slash obligatory, NO leading slash
	const MEDIA_IMAGE_MAXWIDTH = 500;
	const MEDIA_IMAGE_MAXHEIGHT = 500;
	const MEDIA_IMAGE_MINI_WIDTH = 150;
	const MEDIA_IMAGE_MINI_HEIGHT = 150;
	const MEDIA_IMAGE_QUALITY = 80;

	protected $id = 0;

	protected $data = array();

	protected $fields;
	protected $media;

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

		$this->media = json_decode($data['media'], true);

		if (!is_array($this->media)) {
			$this->media = array();
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

		if (isset($data['id_user']) && isset($data['id_category'])) {
			$return['id_user'] = $data['id_user'];
			$return['id_category'] = $data['id_category'];
		}

		$return['fields'] = array();

		if (isset($data['fields']) && is_array($data['fields'])) {
			foreach ($data['fields'] as $k=>$v) {
				$return['fields'][$k] = $v;
			}
		}

		if ($updateItem) {
			$return['datetime_modified'] = date('Y-m-d H:i:s');
		}
		else {
			$return['datetime_created'] = date('Y-m-d H:i:s');
		}

		$return['fields'] = $db->realEscape(json_encode($return['fields']));

		return $return;
	}

	protected static function processMedia ($files, $data, self $object) {
		$return = array();

		if (!isset($data['media']) || !is_array($data['media'])) {
			$data['media'] = array();
		}

		// Processing changes in old media
		$changeMedia = array();
		foreach ($data['media'] as $item) {
			$changeMedia[$item['id']] = $item;
		}

		foreach ($object->getMedia() as $media) {
			// DELETION
			if (!isset($changeMedia[$media['id']])) {
				@unlink(self::MEDIA_STORAGE.$media['name']);
				@unlink(self::MEDIA_STORAGE.$media['original']);
			}
			// ADDITION
			else {
				$media['order'] = (int)$changeMedia[$media['id']]['order'];
				$media['active'] = $changeMedia[$media['id']]['active'] == 'y';

				$return[] = $media;
			}
		}

		// Processing new media
		if (!isset($data['newmedia']) || !is_array($data['newmedia'])) {
			$data['newmedia'] = array();
		}

		foreach ($data['newmedia'] as $k=>$item) {
			$item['url'] = trim($item['url']);
			if ($item['type'] == 'video' && preg_match('/^http(s):\/\/(www\.)?(youtube\.com|youtu\.be)/', $item['url'])) {
				$return[] = array(
					'id' => (int)$item['id'],
					'type' => 'video',
					'url' => trim($item['url']),
					'active' => $item['active'] == 'y',
					'order' => (int)$item['order']
				);
			}
			else {
				$tmp = array(
					'id' => (int)$item['id'],
					'type' => 'image',

					'url' => '',
					'originalurl' => '',

					'originalname' => '',
					'name' => '',
					'original' => '',

					'mininame' => '',
					'miniurl' => '',

					'width' => 0,
					'height' => 0,
					'originalwidth' => 0,
					'originalheight' => 0,
					'active' => $item['active'] == 'y',
					'order' => (int)$item['order'],
				);

				$id = (int)$item['id'];

				if (
					isset($files['error']) &&
					isset($files['error']['media']) &&
					isset($files['error']['media'][$id]) &&
					$files['error']['media'][$id] === UPLOAD_ERR_OK
				) {
					if (isset($files['name']['media'][$id])) {
						$tmp['originalname'] = $files['name']['media'][$id];
					}

					// Trying to process image
					// JPEG
					$imgTmpPath = $files['tmp_name']['media'][$id];
					$img = @imagecreatefromjpeg($imgTmpPath);

					// PNG
					if (!$img) {
						$img = @imagecreatefrompng($imgTmpPath);
					}

					// GIF
					if (!$img) {
						$img = @imagecreatefromgif($imgTmpPath);
					}

					if ($img) {
						$width = imagesx($img);
						$height = imagesy($img);

						// Processing max width & height
						$ratio = min(1, self::MEDIA_IMAGE_MAXWIDTH/$width, self::MEDIA_IMAGE_MAXHEIGHT/$height);

						$targetWidth = round($width * $ratio);
						$targetHeight = round($height * $ratio);

						$targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

						if (
							imagecopyresampled(
								$targetImage,                // Destination image resource
								$img,                        // Source image resource
								0,0,                         // Destination coordinates
								0,0,                         // Source coordinates
								$targetWidth, $targetHeight, // Destination width and height
								$width, $height              // Source width and height
							)
						) {
							$tmp['name'] = $object->getId().'-'.$tmp['id'].'-'.time().'.jpg';
							$tmp['original'] = $object->getId().'-'.$tmp['id'].'-'.time().'-original.jpg';
							$tmp['url'] = '/'.self::MEDIA_STORAGE.$tmp['name'];
							$tmp['originalurl'] = '/'.self::MEDIA_STORAGE.$tmp['original'];
							$tmp['width'] = $targetWidth;
							$tmp['height'] = $targetHeight;
							$tmp['originalwidth'] = $width;
							$tmp['originalheight'] = $height;
							$tmp['mininame'] = $object->getId().'-'.$tmp['id'].'-'.time().'-mini.jpg';;
							$tmp['miniurl'] = '/'.self::MEDIA_STORAGE.$tmp['mininame'];

							// Creating miniature
							$miniImg = imagecreatetruecolor(self::MEDIA_IMAGE_MINI_WIDTH, self::MEDIA_IMAGE_MINI_HEIGHT);

							$mRatio = self::MEDIA_IMAGE_MINI_WIDTH / self::MEDIA_IMAGE_MINI_HEIGHT;
							$oRatio = $width / $height;

							if ($mRatio > $oRatio) {
								$sourceWidth = $width;
								$sourceHeight = $width * $mRatio;
							}
							else {
								$sourceWidth = $height * $mRatio;
								$sourceHeight = $height;
							}

							imagecopyresampled(
								$miniImg,                                                       // Destination image resource
								$img,                                                           // Source image resource
								0,0,                                                            // Destination coordinates
								round(($width - $sourceWidth) / 2), round(($height - $sourceHeight) / 2), // Source coordinates
								self::MEDIA_IMAGE_MINI_WIDTH, self::MEDIA_IMAGE_MINI_HEIGHT,    // Destination width and height
								$sourceWidth, $sourceHeight                                     // Source width and height
							);

							// Saving
							imagejpeg($targetImage, self::MEDIA_STORAGE.$tmp['name'], self::MEDIA_IMAGE_QUALITY);
							imagejpeg($img, self::MEDIA_STORAGE.$tmp['original'], self::MEDIA_IMAGE_QUALITY);
							imagejpeg($miniImg, self::MEDIA_STORAGE.$tmp['mininame'], self::MEDIA_IMAGE_QUALITY);

							$return[] = $tmp;
						}
					}
				}
			}
		}

		// Sorting
		usort($return, array('self', 'mediaSorter'));

		return $return;
	}

	public static function mediaSorter ($a, $b) {
		return $a['order'] - $b['order'];
	}

	public static function create($data, $files, $idUser, $idCategory) {
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

		$o = self::getById($id);

		$media = $db->realEscape(json_encode(self::processMedia($files, $data, $o)));

		$sql = 'UPDATE `'.self::DB_TABLE.'` SET `media` = "'.$media.'" WHERE `id` = '.$o->getId();
		$result = $db->Execute($sql);
		if (!$result) {
			throw new MultiException(array(
				array(
					'caption' => 'DB error!',
					'message' => $db->ErrorMsg() . '<br>Query: ' . $sql
				)
			));
		}

		return $o;
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

	/**
	 * @param $id
	 * @param bool|false $all
	 * @return self[]
	 */
	public static function getByCagtegoryId ($id, $all = false) {
		$return = array();
		$db = cms_core::getDBC();

		// TODO make DB indexes + add caching
		$result = $db->Execute('SELECT * FROM `'.self::DB_TABLE.'` WHERE `id_category` = '.(int)$id.(!$all ? ' AND `is_active` = "y" AND `is_banned` = "n"' : ''));
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

	public static function getByIdsArray ($ids) {
		$return = array();

		if (is_array($ids)) {
			$tmpids = array();

			foreach ($ids as $id) {
				$id = (int)$id;

				if ($id > 0) {
					$tmpids[$id] = $id;
				}
			}

			if (count($tmpids) > 0) {
				$result = cms_core::getDBC()->Execute('SELECT * FROM `'.self::DB_TABLE.'` WHERE `id` IN ('.implode(',', $tmpids).')');

				while ($tmp = $result->fetchRow()) {
					$o = new self($tmp);

					$return[$o->getId()] = $o;
				}
			}
		}

		return $return;
	}

	public static function getRandomVisisble ($categoryId, $count) {
		$return = array();

		$db = cms_core::getDBC();

		$result = cms_core::getDBC()->Execute('SELECT * FROM `'.self::DB_TABLE.'` WHERE `id_category` = '.(int)$categoryId.' AND `is_active` = "y" AND `is_banned` = "n" ORDER BY RAND() LIMIT '.(int)$count);

		while ($tmp = $result->fetchRow()) {
			$o = new self($tmp);

			$return[$o->getId()] = $o;
		}

		return $return;
	}

	public function save ($data, $files) {
		$updarr = self::getInsUpdArray($data, $this);
		$db = cms_core::getDBC();

		$updarr['media'] = $db->realEscape(json_encode(self::processMedia($files, $data, $this)));

		foreach ($updarr as $k=>$v) {
			$updarr[$k] = '`'.$k.'` = "'.$v.'"';
		}

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

	public function toArray () {
		$return = $this->data;

		$return['fields'] = $this->fields;
		$return['media'] = $this->media;
		$return['is_active'] = $this->isActive();

		return $return;
	}

	public function toDisplayArray ($lang) {
		$return = array();

		$return['id'] = $this->getId();
		$return['fields'] = $this->getFields($lang);
		$return['media'] = $this->media;
		$return['name'] = $this->getName($lang);
		$return['desc'] = $this->getDesc($lang);
		$return['price'] = $this->getPrice();
		$return['times_ordered'] = (int)$this->get('times_ordered');
		$return['date'] = $this->get('datetime_created');

		return $return;
	}

	public function getId()         { return (int)$this->id; }
	public function isActive()      { return $this->get('is_active') == 'y'; }
	public function isBanned()      { return $this->get('is_banned') == 'y'; }
	public function isVisible()     { return $this->isActive() && !$this->isBanned(); }
	public function getName($lang)  { return $this->get('name', $lang); }
	public function getDesc($lang)  { return $this->get('desc', $lang); }
	public function getPrice()      { return (float)$this->get('price'); }
	public function getIdCategory() { return (int)$this->get('id_category'); }
	public function getMedia()      { return $this->media; }

	public function getMediaFirstImage() {
		foreach ($this->media as $item) {
			if ($item['type'] == 'image' && $item['active']) {
				return $item;
			}
		}

		return null;
	}
}