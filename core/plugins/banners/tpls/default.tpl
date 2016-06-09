{*
{if $output.countBanners>0}

	{if $output.insert_mode == 1}
		{assign var='banners' value=$output.banners}

		{foreach item=banner from=$banners}

			<noindex>
				<a href="{$banner.url}" target="_blank" rel="nofollow">
					<img alt="{$banner.title}" title="{$banner.title}" src="/{$banner.file_url}" />
				</a>
			</noindex>


		{/foreach}

	{/if}

{/if}
*}





{if $output.countBanners>0}

	<div class="sal-advert fotorama" data-width="100%" data-height="300">

		{assign var='banners' value=$output.banners}

		{foreach item=banner from=$banners}

			<div class="sal-advert__big" style="background-image: url({$banner.file_url})">


				<a href="{$banner.url}" class="sal-ui__button sal-advert__button">
					{$banner.title}
				</a>

			</div>

		{/foreach}

	</div>

{/if}