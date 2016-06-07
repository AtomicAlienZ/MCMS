<a href="?plg=shop_manage&cmd=fieldsets&arg[action]=addFieldset">ADD</a>

{foreach from=$list item=item}
	<div style="padding: 20px 20px 20px 40px; box-shadow: 0px 2px 4px rgba(20, 20, 20, .1); margin: 20px 0;">
		id={$item->getId()}<br>
		{$item->getName()}<br>
		<a href="?plg=shop_manage&cmd=fieldsets&arg[action]=editFieldset&arg[id]={$item->getId()}">EDIT</a>
	</div>
{/foreach}