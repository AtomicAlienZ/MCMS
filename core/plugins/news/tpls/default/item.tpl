{if $lang eq 'ru'}
	{assign var='author' value="Автор"}
	{assign var='source' value="Источник"}
	{assign var='page' value="Стр."}
	{assign var='notFound' value="Материал не найден"}
	{assign var='back' value="Вернуться к списку"}
{elseif $lang eq 'ua'}
	{assign var='author' value="Автор"}
	{assign var='source' value="Джерело"}
	{assign var='page' value="Стор."}
	{assign var='notFound' value="Матеріал не знайдений"}
	{assign var='back' value="Повернутись до списку новин"}
{elseif $lang eq 'en'}
	{assign var='author' value="Author"}
	{assign var='source' value="Source"}
	{assign var='page' value="Page"}
	{assign var='notFound' value="Material is not found"}
	{assign var='back' value="Back to news"}
{/if}



{if $output.item.id>0}
	{assign var='item' value=$output.item}
	<div class="news">
		<div class="item">

			<div class="ttl-item"><h1>{$item.title}</h1></div>
			<!--<div class="date">{$item.dates|date_format:'%d.%m.%Y'}</div>-->
			<div class="date">Автор — {$item.author}.</div>
			<div class="clr">&nbsp;</div>
			<div class="back-to-list"><a href="{$path[1].url}">{$back}</a></div>
			{if $item.img_sm>''}<div class="img"><div><img src="{$item.img_sm}" alt="{$item.title}" title="{$item.title}"></div></div>{/if}
			{if $item.descr>''}<div class="text">{$item.descr}</div>{/if}
			
			{if $item.content>''}<div class="text">{$item.content}</div>{/if}
			{if $item.count_gallery>0}
				<div class="gallery">
					{section loop=$item.gallery name=key2}
						{assign var='gallery' value=$item.gallery[key2]}
						<div class="itm{if $smarty.section.key2.iteration is div by 2}2{/if}"><img src="{$gallery.img_sm}" title="{$gallery.title}"></div>
						{if $smarty.section.key2.iteration is div by 2}<div class="clr">&nbsp;</div>{/if}
					{/section}
					<div class="clr">&nbsp;</div>
				</div>
			{/if}
			<div class="back-to-list"><a href="{$path[1].url}">{$back}</a></div>
		</div>
	</div>
{else}
	<div class="news">
		<div>{$notFound}</div>
		<div class="lnk"><a href="{$structure.url}">{$back}</a></div>
	</div>
{/if}

<div class="break"></div>