{* Шаблон показа записей каталога *}

<div class="polka">&nbsp;</div>
{if $output.state == "done"}
{if $output.count_items > 0}
	{assign var='items' value=$output.items}
	{section loop=$items|@count name=key step=3}
		<div class="catalog">

			<div class="top-row">
				{section loop=3 name=key2}
					{assign var='keys' value=`$smarty.section.key.iteration*3-3+$smarty.section.key2.iteration-1`}
					{if $items[$keys].title>''}
						<div class="cell top{if $smarty.section.key2.last} last{/if}"></div>
						{if !$smarty.section.key2.last}<div class="cell2">&nbsp;</div>{/if}
					{/if}
				{/section}
			</div>
			<div class="row">
				{section loop=3 name=key2}
					{assign var='keys' value=`$smarty.section.key.iteration*3-3+$smarty.section.key2.iteration-1`}

					{assign var='item' value=$items[$keys]}

					{if $item.title>''}
						<div class="cell center{if $smarty.section.key2.last} last{/if}">
							<div class="pd">
								<h2 class="name"><a href="{$item.url}">{$item.title}</a></h2>
							</div>
						</div>
						{if !$smarty.section.key2.last}<div class="cell2">&nbsp;</div>{/if}
					{/if}
				{/section}
			</div>
			<div class="row">
				{section loop=3 name=key2}
					{assign var='keys' value=`$smarty.section.key.iteration*3-3+$smarty.section.key2.iteration-1`}

					{assign var='item' value=$items[$keys]}

					{if $item.title>''}
						<div class="cell center{if $smarty.section.key2.last} last{/if}">
							<div class="image"><a href="{$item.url}"><img src="{if $item.img_sm1>''}{$item.img_sm1}{else}/img/no_img_sm1.gif{/if}"></a></div>
						</div>
						{if !$smarty.section.key2.last}<div class="cell2">&nbsp;</div>{/if}
					{/if}
				{/section}
			</div>
			<div class="row">
				{section loop=3 name=key2}
					{assign var='keys' value=`$smarty.section.key.iteration*3-3+$smarty.section.key2.iteration-1`}

					{assign var='item' value=$items[$keys]}

					{assign var='price' value=$item.price}
					{assign var='additional_item' value=''}
					{foreach from=$item.additional item=additional key=key_a}
						{if $additional.type eq "multipleprice"}
							{foreach from=$additional.value item=multiple_value name=multiple key=key_a2}
								{if $smarty.foreach.multiple.first}
									{assign var='price' value=`$price+$multiple_value.price`}
									{assign var='additional_item' value=$additional_item|cat:"&basket[additional]["|cat:$key_a|cat:"]="|cat:$multiple_value.id}
								{/if}
							{/foreach}
						{/if}
					{/foreach}

					{if $item.title>''}
						<div class="cell center{if $smarty.section.key2.last} last{/if}">
							<div class="pd">
								{if $item.description>''}<div class="descr">{$item.description|strip_tags|truncate:50}</div>{/if}
								<div class="price">{if $item.price>0}<strong>{$price} {$output.currency.title}</strong><br />{/if}</div>
								<div class="add_basket">
									{if $item.state eq 0 && $item.price>0}
										<a href="#" onclick="addBasket('{$output.catalog_path}', '{$output.basket_url}', '{$item.title|escape:"quotes"|escape:"html"}', {$item.id}, true, 1, '{$additional_item}'); return false;">добавить в корзину</a>
									{else}
										<a href="/{$lang}/catalogs/preorder/cid_{$item.id}/">уведомить о наличии</a>
									{/if}
								</div>
								<div class="add_basket"><a href="#" onclick="addCompare({$item.id}, 1); return false;">Удалить из сравнения</a></div>
							</div>
						</div>
						{if !$smarty.section.key2.last}<div class="cell2">&nbsp;</div>{/if}
					{/if}
				{/section}
			</div>
			
{foreach from=$output.fields item=field key=key name=fld}
{if $field.type neq "multipleprice"}
			<div class="row">
			<table cellpadding="0" cellspacing="0" border="0"><tr>
			{section loop=3 name=key2}
					{assign var='keys' value=`$smarty.section.key.iteration*3-3+$smarty.section.key2.iteration-1`}
					{assign var='item' value=$items[$keys]}
					
					{if $item.title>''}
	
		{if $field.type neq "multipleprice"}
			{assign var='field_sys' value=$key}
			{assign var='additional' value=$items[key2].additional}
				<td class="r{if $smarty.foreach.fld.iteration is div by 2} r2{/if}">
					<div class="r-inner">
					<span class="field-ttl">{$field.title}</span>
					<span class="field-val">{$additional[$field_sys].value}</span>
					</div>
				</td>
		{/if}
		
	{if !$smarty.section.key2.last}<td class="cell2">&nbsp;</div>{/if}
					{/if}
			{/section}
			</tr></table>
			</div>	
{/if}			
{/foreach}			
			
			
			
			<div class="bottom-row">
				{section loop=3 name=key2}
					{assign var='keys' value=`$smarty.section.key.iteration*3-3+$smarty.section.key2.iteration-1`}
					{if $items[$keys].title>''}
						<div class="cell bottom{if $smarty.section.key2.last} last{/if}">&nbsp;</div>
						{if !$smarty.section.key2.last}<div class="cell2">&nbsp;</div>{/if}
					{/if}
				{/section}
			</div>
		</div>
		{if !$smarty.section.key.last}<div class="polka">&nbsp;</div>{/if}
	{/section}
{/if}

{elseif $output.state == "error"}
	<div class="wtext center_bg pad_text">
		<div>Вы не выбрали элементы для сравнения.</div>
	</div>
	<div class="center_text_bg_bottom"></div>
{/if}
