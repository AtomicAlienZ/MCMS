{if $output.countBanners>0}
<div class="banner-left">
	{if $output.insert_mode == 1} 
		{assign var='banners' value=$output.banners}
		{foreach item=banner from=$banners}
			<div>
				{* если картинка *} 
				{if $banner.type == 1}
					{if $banner.url > ''}<noindex><a href="/banner.php?id={$banner.id}&url={$banner.url}" rel="nofollow">{/if}<img width="{$banner.w_size}" height="{$banner.h_size}" alt="{$banner.title|escape}" title="{$banner.title|escape}" src="/{$banner.file_url}" border="0">{if $banner.url > ''}</a></noindex>{/if} 	
				{* если флэш *} 
				{elseif $banner.type == 2}
					<noindex><object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="{$banner.w_size}" height="{$banner.h_size}">
					<param name="movie" value="/{$banner.file_url}">
					<param name="menu" value="{$banner.flash_menu}">
					{if $banner.transparent_b == 1}<param name="wmode" value="transparent">{/if}
					{if $banner.background > ''}<param name="bgcolor " value="{$banner.background}">{/if}		
					<param name="quality" value="{$banner.quality}">			
					<embed src="/{$banner.file_url}" menu="{$banner.flash_menu}"{if $banner.transparent_b == 1} wmode="transparent"{/if}{if $banner.background > ''} bgcolor="{$banner.background}"{/if} quality="{$banner.quality}" width="{$banner.w_size}" height="{$banner.h_size}" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer"> 
					</object></noindex>
				{* если внешний код *} 
				{elseif $banner.type == 3}
					<noindex>{if $banner.showAfterPage eq 1}<div id="bannerOut_{$banner.id}"></div>{else}{$banner.code}{/if}</noindex>
				{/if}
			</div> 
		{/foreach}
	{/if}
{*	{section name=cid loop=$output.items}
		<div>
			{if $output.items[cid].isflash eq 1}
				{$output.items[cid].flashtext}
			{else}
				{if $output.items[cid].link>''}<a href="{$output.items[cid].link}">{/if}<img src="{$output.items[cid].image_url}" alt="{$output.items[cid].title|escape}" title="{$output.items[cid].title|escape}{if $output.items[cid].descr > ''}. {$output.items[cid].descr|escape}{/if}">{if $output.items[cid].link>''}</a>{/if}
			{/if}
		</div>
	{/section}*}
</div>
{/if}