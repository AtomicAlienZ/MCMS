{if $output.countBanners>0}
<div class="header_adv">
	{assign var='banners' value=$output.banners}
	{foreach item=banner from=$banners}
		<div class="b-banner">
			<div class="b-banner__top-left">
				<div class="b-banner__image">{if $banner.url > ''}<noindex><a href="/banner.php?id={$banner.id}&url={$banner.url}" target="_blank" rel="nofollow">{/if}<img width="{$banner.w_size}" height="{$banner.h_size}" alt="{$banner.title}" title="{$banner.title}" src="/{$banner.file_url}" border="0">{if $banner.url > ''}</a></noindex>{/if}</div>
				<div class="b-banner__top-right"></div>
				<div class="b-banner__bottom-left"></div>
				<div class="b-banner__bottom-right"></div>
			</div>
		</div>
	{/foreach}
</div>



{/if}