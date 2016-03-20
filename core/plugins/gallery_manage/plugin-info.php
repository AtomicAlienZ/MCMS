<?php
$plugin = array (
				'group'        	    => 'modules',
				'name'         		=> 'gallery_manage',
				'title'         	=> 'Фотогалерея',
				'menu'           	=> true,
				'submenu'  			=> array ()
				);

// Формируем список существующих каталогов в качестве подменю
$query = "SELECT * FROM pm_gallery WHERE parent_id=0 ORDER BY ord DESC, title_ru";
$result = $this->dbc->Execute($query);
if ($result && $result->RecordCount() > 0) {

	// По очереди формируем список каталогов
	while($item = $result->FetchRow()) {

		// Рассчитываем ссылку на просмотр каталога
		$link = "index&arg[parent]=".$item["gallery_id"];
		$plugin["submenu"][$link] = $item["title_ru"];
	}
}
?>
