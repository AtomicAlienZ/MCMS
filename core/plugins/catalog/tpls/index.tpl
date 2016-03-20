{debug}

{if $output.CatalogFolders neq ''}
	<div class="news">
		{foreach from=$output.CatalogFolders item=rubric}
			<div class="item">
				<div class="ttl"><a href="{$rubric.full_relative_url}">{$rubric.title_ru}</a></div>
			</div>
		{/foreach}
	</div>
{/if}