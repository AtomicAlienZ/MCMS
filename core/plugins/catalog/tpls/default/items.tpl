{php}
	//Для отладки
	//echo '<pre>'; print_r($this->_tpl_vars['output']['navi']); echo '</pre>';
	//echo '<pre>'; print_r($this->_tpl_vars['output']); echo '</pre>';
{/php}

{php}
	global $IsCatalogSubCategory;
	$IsCatalogSubCategory = false;
{/php}

{if $output.MainCatalogPage}
	<!-- Главная страница каталога -->
	{if $output.MainCatalogPageFolders.items_new_count > 0}
		{assign var='items' value=$output.MainCatalogPageFolders.items_new}
		{section loop=$items name=key}
			{assign var='item' value=$items[key]}
			<div class="category-i">
				<div class="cld-c-1">
					<a href="{$item.full_relative_url}">{$item.title_ru}</a>
				</div>
				<div class="cld-c-2-main">
					<div class="cld-c-p">
						{if $item.NextFoldersCount > 0}
							{assign var='items2' value=$item.NextFolders}
							{section loop=$items2 name=key2}
								{assign var='item2' value=$items2[key2]}
								<a href="{$item2.full_relative_url}">{$item2.title_ru}</a>{if !$smarty.section.key2.last},&nbsp;{/if}
							{/section}
						{else}
							<div style="padding: 10px; font-weight: bold; height: 62px; text-align: center; line-height: 64px;">В этом разделе пока пусто!</div>
						{/if}
					</div>
				</div>
			</div>
		{/section}
	{/if}
	
	{php}
		$_SESSION["sort_type"] = 3;
		$_SESSION["price_min"] = 0;
		$_SESSION["price_max"] = 0;
		$_SESSION["real_price_max"] = 0;
		$_SESSION['FILTER_ADD_FIELDS'] = false;
		$_SESSION['FILTER_ADD_FIELDS_BUFFER'] = false;
	{/php}
	
{else}
	<!-- Не главная страница каталога -->
	<!-- Если это первая подкатегория -->
	{if $output.CatalogLevel2}
		<!-- Подкатегория -->
		{if $output.CatalogLevelCategoryCount > 0}
			{assign var='items' value=$output.CatalogLevelCategoryItems}
			{section loop=$items name=key}
				{assign var='item' value=$items[key]}
				<div class="category-i">
					<div class="cld-c-1">
						<a href="{$item.full_relative_url}">{$item.title_ru} ({if $item.NextFoldersCount > 0}{$item.NextFoldersCount}{else}0{/if})</a>
					</div>
					<div class="cld-c-2-main">
						<div class="cld-c-p">
							{if $item.NextFoldersCount > 0}
								<div><b>10 популярных товаров:</b></div>
								<div>
									{assign var='items2' value=$item.NextFolders}
									{section loop=$items2 name=key2}
										{*
										{$smarty.section.key2.index}
										*}
										{if $smarty.section.key2.index <= 9}
											{assign var='item2' value=$items2[key2]}
											<a href="{$item2.full_relative_url}">{$item2.title_ru}</a>{if (!$smarty.section.key2.last) and ($smarty.section.key2.index < 9) },&nbsp;{/if}
										{/if}
									{/section}
								</div>
							{else}
								<div style="padding: 10px; font-weight: bold; height: 62px; text-align: center; line-height: 64px;">В этом разделе пока пусто!</div>
							{/if}
						</div>
					</div>
				</div>
			{/section}
		{/if}
		
		{php}
			$_SESSION["sort_type"] = 3;
			$_SESSION["price_min"] = 0;
			$_SESSION["price_max"] = 0;
			$_SESSION["real_price_max"] = 0;
			$_SESSION['FILTER_ADD_FIELDS'] = false;
			$_SESSION['FILTER_ADD_FIELDS_BUFFER'] = false;
		{/php}
		
	{else}
		<!-- Список товаров -->
		{php}
			global $IsCatalogSubCategory;
			$IsCatalogSubCategory = true;
		{/php}
		
		{if $output.count_items > 0}
			<div id="view-switch">
				<div id="vs-list" class="active">Список</div>
				<div id="vs-small">Маленькие картинки</div>
				<div id="vs-big">Большие картинки</div>
			</div>
			<div class="clr">&nbsp;</div>
			<div id="subcategory">
				{assign var='items2' value=$output.items}
				{section loop=$items2 name=key2}
					{assign var='item2' value=$items2[key2]}
					
					<div class="s-i list">
						<div class="stickers">
							{if $item2.action == '1'}<div class="new inline">&nbsp;</div>{/if}
							{if $item2.news == '1'}<div class="hit inline">&nbsp;</div>{/if}
							{if $item2.best == '1'}<div class="special inline">&nbsp;</div>{/if}
							{if $item2.recomended == '1'}<div class="nv inline">&nbsp;</div>{/if}
						</div>
						<div class="s-i-img"><img src="{if $item2.img != ''}{$item2.img}?time={php}echo time();{/php}{else}/img/noimage.jpg{/if}"></div>
						<div class="s-i-info">
							<div class="s-i-name"><a href="{$item2.url}">{$item2.title|truncate:80}</a></div>
							<div class="s-i-price">
								
								{$item2.price} {$output.currency.title}
								
								{php}
									//echo '<pre>'; print_r($this->_tpl_vars); echo '</pre>';
								{/php}
							</div>
							<div class="s-i-descr">
								
								<!--<div class="ci-label">Характеристики</div>-->
								<div class="table">
									{foreach from=$item2.additional item=additional}
										{if $additional.type neq "multipleprice"}
											<div class="ci-c-left inline"><b>{$additional.field}</b></div>
											<div class="ci-c-right inline" style="width: 220px;">
												{if $additional.type == "multiple"}
													{foreach from=$additional.value item=multiple_value name=multiple}
														{$multiple_value}
														{if !$smarty.foreach.multiple.last}, {/if}
													{/foreach}
												{elseif $additional.type == "image"}
													{if $additional.value.img > ''}
														<a href="{$additional.value.img}"><img src="{$additional.value.img_sm}" /></a>
													{/if}
												{else}
													{$additional.value}
												{/if}
											</div>
										{/if}
									{/foreach}
								</div>
								
								<!--Описание товара: короткое, ненавязчивое, но дающее полную и важную информацию о том, что собирается купить посетитель.-->
							</div>
							<div class="s-i-add">
								{if $item2.state eq 0 && $item2.price>0}
									<a href="#" onclick="addBasket('{$output.catalog_path}', '{$output.basket_url}', '{$item2.title|escape:"quotes"|escape:"html"}', {$item2.id}, true, 1, '{$additional_item}'); return false;">Добавить в корзину</a>
								{else}
									<a href="/{$lang}/catalog/preorder/cid_{$item2.id}/">Уведомить о наличии</a>
								{/if}
							</div>
						</div>
					</div>
				{/section}
			</div>
			<div id="paginator">
				{if $output.navi.pages_total >= 2}
					{if $output.navi.prev != ''}<a id="prev" href="{$output.navi.prev}">предыдущая</a>{/if}
					{if $output.navi.next != ''}<a id="next" href="{$output.navi.next}">следующая</a>{/if}
				{/if}
				<ul id="pag-list">
					{if $output.navi.pages_total >= 2}
						{foreach from=$output.navi.pages item=page}
							{if $page.title == $output.navi.page}
								<li class="active">{$page.title}</li>
							{else}
								<li><a href="{$page.url}">{$page.title}</a></li>
							{/if}
						{/foreach}
					{/if}
					<!--<li class="active">1</li>
					<li><a href="#">2</a></li>
					<li><a href="#">3</a></li>
					<li><a href="#">4</a></li>
					<li><a href="#">5</a></li>-->
					
					<!--<li><a href="#">&gt;</a></li>-->
				</ul>
			</div>
		{else}
			{php}
				$_SESSION["sort_type"] = 3;
				$_SESSION["price_min"] = 0;
				$_SESSION["price_max"] = 0;
				$_SESSION["real_price_max"] = 0;
				$_SESSION['FILTER_ADD_FIELDS'] = false;
				$_SESSION['FILTER_ADD_FIELDS_BUFFER'] = false;
			{/php}
			<div style="padding: 10px; font-weight: bold; height: 62px; text-align: center; line-height: 64px;">В этом разделе пока пусто!</div>
		{/if}
	{/if}
{/if}