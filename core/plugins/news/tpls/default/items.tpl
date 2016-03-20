
{if $lang eq 'ru'}
	{assign var='author' value="Автор"}
	{assign var='source' value="Источник"}
	{assign var='page' value="Стр."}
	{assign var='notFound' value="Раздел наполняется"}
	{assign var='fullList' value="Вернутся к полному перечню"}
{elseif $lang eq 'ua'}
	{assign var='author' value="Автор"}
	{assign var='source' value="Джерело"}
	{assign var='page' value="Стор."}
	{assign var='notFound' value="Розділ наповнюється"}
	{assign var='fullList' value="Вернутись до повного перелiку"}
{elseif $lang eq 'en'}
	{assign var='author' value="Author"}
	{assign var='source' value="Source"}
	{assign var='page' value="Page"}
	{assign var='notFound' value="A section is filled"}
	{assign var='fullList' value="Back to full list"}
{/if}

{if $output.countItems>0}
	<div class="news">
		{assign var='items' value=$output.items}
		{section loop=$items name=key}
			<div class="item">
				<div class="ttl"><a href="{$items[key].url}">{$items[key].title}</a></div>
				<div class="date">{$items[key].dates|date_format:'%d.%m.%Y'}</div>
				<div class="date">Автор — {$items[key].author}.</div>
				<div class="clr">&nbsp;</div>
				{if $items[key].img_sm1>''}<div class="img"><div><a href="{$items[key].url}"><img src="{$items[key].img_sm1}" alt="{$items[key].title}" title="{$items[key].title}"></a></div></div>{/if}
				{if $items[key].descr>''}<div class="text">{$items[key].descr}</div>{/if}
				<div class="clr">&nbsp;</div>
				
				{if $items[key].count_gallery>0 && 0}
					<div class="gallery">
						{section loop=$items[key].gallery name=key2 max=4}
							{assign var='gallery' value=$items[key].gallery[key2]}
							<div class="itm{if $smarty.section.key2.iteration is div by 2}2{/if}"><img src="{$gallery.img_sm}" title="{$gallery.title}"></div>
							{if $smarty.section.key2.iteration is div by 2}<div class="clr">&nbsp;</div>{/if}
						{/section}
						<div class="clr">&nbsp;</div>
					</div>
				{/if}
			</div>
		{/section}
	</div>

	{if $output.navi.pages_total > 1 && $output.settings.show_navi eq 1}
	
				<div class="pages">
					{if $output.navi.page < 6}
						{assign var=start value=0}
					{else}
                        {assign var=start value=$output.navi.page-5}
					{/if}

					{if $output.navi.pages_total - $output.navi.page >= 5}
						{assign var=max value=5}
					{else}
						{assign var=max value=$output.navi.pages_total}
					{/if}
					
					<!--Page = {$output.navi.page}, total = {$output.navi.pages_total}
					Start = {$start}, max = {$max},-->
					
					{assign var=prev value=$output.navi.page-2}
					{assign var=next value=$output.navi.page}	
					<div class="arrows">
						{if $output.navi.page neq 1}
							<div class="future"><img src="/img/left-arrow.gif" alt="" />
                              {if $prev eq 'http://phototour.pro/news/page_1/'}<a href="{$output.navi.pages[0].url}">Будущее</a>
                              {else}<a href="{$output.navi.prev}">Будущее</a>
                              {/if}
                            </div>
						{else}
							<div class="future"><img src="/img/left-arrow.gif" alt="" />Будущее</div>
						{/if}
						{if $output.navi.page neq $output.navi.pages_total}
							<div class="past"><a href="{$output.navi.next}">Прошлое</a><img src="/img/right-arrow.gif" alt="" /></div>
						{else}
							<div class="past">Прошлое<img src="/img/right-arrow.gif" alt="" /></div>
						{/if}
					</div>
					<div class="clr"></div>
					{assign var='items' value=$output.navi.pages}
					{section name=key start=$start loop=$items max=$max} 
						{if $items[key].title neq $output.navi.page}
							<a href="{$items[key].url}">{$items[key].title}</a>
						{else}
							<span>{$items[key].title}</span>
						{/if}
					{/section}
				</div>
	
	

	{/if}
{else}
{*	<div class="news">
		<div>{$notFound}</div>
	</div>*}
{/if}