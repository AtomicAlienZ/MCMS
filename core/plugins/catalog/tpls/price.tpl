{* Шаблон показа записей каталога *}

{if $output.state == "done"}
	{if $output.count_items > 0}
	<div class="wtext center_bg pad_text">
		<div class="price-list">
			{assign var='items' value=$output.items}
			{section loop=$items name=key}
				{assign var='item' value=$items[key]}
				{if $item.type eq 'item'}
					<div style="margin-left: {$item.level*10-10}px;" class="price-item row{if $smarty.section.key.iteration is div by 2}1{else}2{/if} pngFix"><div class="fLeft"><a href="{$item.url}">{$item.title}</a></div><div class="currency fRight">{if $item.price>0}{$item.price} {$output.currency.title}{else}-{/if}</div><div class="clr">&nbsp;</div></div>
				{else}
					<div class="price-category" style="padding-left: {$item.level*10-10}px;"><a href="{$item.url}">{$item.title}</a></div>
				{/if}
			{/section}
		</div>
	</div>
	<div class="center_text_bg_bottom"></div>
	{/if}
{elseif $output.state == "error"}
	<div class="catalog">
		<div>Записи не найдены</div>
	</div>
{/if}
