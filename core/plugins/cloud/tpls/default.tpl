{if $output.countItems>0}
	{assign var='menu1' value=$handlers.main_menu.catalog_top}
	<noindex>
        <div class="box_side"> <div class="content">
		{section name=key loop=$output.items}
			<a href="{$output.items[key].url}" rel="nofollow" style="font-size:{$output.items[key].fontsSize}px;">{$output.items[key].word}</a>
		{/section}
	</div></div>
        </noindex>
{/if}