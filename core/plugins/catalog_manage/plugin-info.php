<?php
# plugin identification file

$plugin = array (
	'group'		=> 'modules',
	'name'		=> 'catalog_manage',
	'title'		=> 'Каталог',
	'menu'		=> true,
	'submenu'	=> array()
);

// Формируем список существующих каталогов в качестве подменю
$query = "SELECT * FROM pm_catalog WHERE parent_id=0 AND type='folder' ORDER BY ord DESC, title_ru";
$result = $this->dbc->Execute($query);
if ($result && $result->RecordCount() > 0) {

	// По очереди формируем список каталогов
	while($item = $result->FetchRow()) {

		// Рассчитываем ссылку на просмотр каталога
		$link = "records&arg[id]=".$item["id"];
		$plugin["submenu"][$link] = $item["title_ru"];
	}
}

?>