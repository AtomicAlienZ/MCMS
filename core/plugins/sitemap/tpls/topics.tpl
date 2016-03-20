<div id="cite-map">
{if $output.countItems>0}
	{section name=key loop=$output.items}
		{if $output.items[key].level > 2 and $structure.s_id == $output.items[key].parent}
			<div class="map-item"><a href="{$output.items[key].url}">{$output.items[key].title}</a></div>
		{/if}
	{/section}
{/if}
</div>