{function name=TPLBuildCat}
	<div style="padding: 20px 20px 20px 40px;border: 1px solid red;">
		CATEGORY id: {$cat->getId()}
		<br>
		ALIAS: {$cat->getAlias()}
		<br>
		NAMES: {foreach from=$langs item=l}"{$cat->getName($l)}" ({$l}), {/foreach}
		{if $cat->getFieldset()}
			<br>
			FIELDSET: {$cat->getFieldset()->getName()}
		{/if}
		<br>
		{if $cat->canAddItems()}
			<a href="?action=add&id={$cat->getId()}">ADD ITEM</a>
		{/if}
		{foreach from=$cat->getChildren() item=child name=pewpew}
			{TPLBuildCat cat=$child}
		{/foreach}
	</div>
{/function}

<h2>MY ITEMS</h2>
{foreach from=$output.list item=item}
<div style="padding: 10px;margin: 10px;border: 1px solid;">
	CATEGORY: {$item->getCategory()->getName($lang)}
	<br>
	NAMES: {foreach from=$langs item=l}"{$item->getName($l)}" ({$l}), {/foreach}
	<br>
	Active: {if $item->isActive()}YES{else}NO{/if}
	<br>
	Banned: {if $item->isBanned()}YES{else}NO{/if}
	<br>
	Price: {$item->getPrice()}
	<br>
	<b>FIELDS:</b>
	{foreach from=$item->getFields($lang) item=field}
		<div>
			{$field.name} - {if is_array($field.value)}{', '|implode: $field.value}{else}{$field.value}{/if}
		</div>
	{/foreach}
</div>
{/foreach}


<h2>CATEGORIES</h2>
{foreach $output.categories as $cat}{TPLBuildCat cat=$cat}{/foreach}