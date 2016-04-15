<a href="?plg=shop_manage&cmd=fieldsets&arg[action]=addFieldset">ADD</a>

{foreach from=$list item=item}
	<div style="border: 1px solid red;padding: 20px;">
		id={$item->getId()}<br>
		{$item->getName()}<br>
		<a href="?plg=shop_manage&cmd=fieldsets&arg[action]=editFieldset&arg[id]={$item->getId()}">EDIT</a>
	</div>
{/foreach}