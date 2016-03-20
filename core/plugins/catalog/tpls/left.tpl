<div id="catalog-left">
	<div class="label">каталог</div>
	<ul id="c-l-menu">
		{if $output.items_new_count > 0}
			{assign var='items' value=$output.items_new}
			{section loop=$items name=key}
				{assign var='item' value=$items[key]}
				<li>
					<p>{$item.title_ru}</p>
					<div class="c-l-d">
						{if $item.NextFoldersCount > 0}
							{assign var='items2' value=$item.NextFolders}
							{section loop=$items2 name=key2}
								{assign var='item2' value=$items2[key2]}
								<div class="c-l-d-{if $smarty.section.key2.iteration is div by 2}2{else}1{/if}">
									<!-- Category name -->
									<div class="cld-c-1">
										<a href="{$item2.full_relative_url}">{$item2.title_ru}</a>
									</div>
									<!-- Category contents -->
									<div class="cld-c-2">
										<!-- Production -->
										<div class="cld-c-p">
											{assign var='items3' value=$item2.NextFolders}
											{section loop=$items3 name=key3}
												{assign var='item3' value=$items3[key3]}
												<a href="{$item3.full_relative_url}">{$item3.title_ru}</a>{if !$smarty.section.key3.last},&nbsp;{/if}
											{/section}
										</div>
									</div>
								</div>
							{/section}
						{else}
							<div style="padding: 10px; font-weight: bold; height: 62px; text-align: center; line-height: 64px;">В этом разделе пока пусто!</div>
						{/if}
						<div class="c-l-d-bot">&nbsp;</div>
					</div>
				</li>
			{/section}
		{/if}
	</ul>
</div>
<div id="c-l-b">&nbsp;</div>