<div id="as-map">
{if $output.countItems>0}
	{section name=key loop=$output.items}
		<div class="item" style="padding-left:{$output.items[key].level*20}px"><a href="{$output.items[key].url}">{$output.items[key].title}</a></div>
	{/section}
{/if}
</div>
