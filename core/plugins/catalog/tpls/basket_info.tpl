{if $output.tpl> ''}
	{assign var='tpl' value=$output.pathTemplate|cat:'basket/'|cat:$output.tpl|cat:'.tpl'}
	{include file=$tpl}
{/if}