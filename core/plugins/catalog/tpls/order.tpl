{if $output.tpl> ''}
	{assign var='tpl' value=$output.pathTemplate|cat:'order/'|cat:$output.tpl|cat:'.tpl'}
	{include file=$tpl}
{/if}
