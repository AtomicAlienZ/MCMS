{*{debug}*}
{if $output.state eq 'list'}

	{* If it is a News List *}
	<div class="mj-portfolio">

		{assign var='news' value=$output.items}
		{section loop=$news name=item}
			{if $news[item].descr neq ''}
			<div class="mj-portfolio__item">

				<a href="/{$lang}/{$news[item].categoryurl}/{$news[item].alias}/" class="b-portfolio-item mj-portfolio__item__link">
					<div class="b-portfolio-item-thumb mj-portfolio__item__image"><img src="{$news[item].img}"/></div>
					<div class="b-portfolio-item-label mj-portfolio__item__label">
						{*{$news[item].title}*}{$news[item].descr}
					</div>
				</a>

			</div>
			{/if}

		{/section}

	</div>
	{*Paginator*}

	{* If there's more than 1 page and navigation is on *}
	{if $output.navi.pages_total > 1 && $output.settings.show_navi eq 1}

		{* Paginator begins on the 1st page... *}
		{if $output.navi.page < 6}
			{assign var=start value=0}
		{else}
			{* or current page - 5 *}
			{assign var=start value=$output.navi.page-5}
		{/if}

		{* Here we set the paginator with the number of pages forth *}
		{if $output.navi.pages_total - $output.navi.page >= 5}
			{assign var=max value=5}
		{else}
			{* or set it forth to the last page *}
			{assign var=max value=$output.navi.pages_total}
		{/if}

		{* Links to next and previous pages*}
		{assign var=prev value=$output.navi.page-1}
		{assign var=next value=$output.navi.page+1}
		<div class="b-paginator">

			{assign var='pages' value=$output.navi.pages}
			{section name=page start=$start loop=$pages max=$max}
				{if $pages[page].title neq $output.navi.page}
					{* Link *}
					<a class="b-paginator-item f-ib b-paginator-item__active" href="{$pages[page].url}">
						{$pages[page].title}
					</a>
				{else}
					{* Current Page *}
					<span class="b-paginator-item f-ib b-paginator-item__current">
                        {$pages[page].title}
                    </span>
				{/if}
			{/section}


			{*<div class="arrows">
				{if $output.navi.page neq 1}
					<div class="future"><img src="/img/left-arrow.gif" alt=""/>
						{if $prev eq 1 }<a href="{$output.navi.pages[0].url}">Будущее</a>
						{else}<a href="{$output.navi.prev}">Будущее</a>
						{/if}
					</div>
				{else}
					<div class="future"><img src="/img/left-arrow.gif" alt=""/>Будущее</div>
				{/if}
				{if $output.navi.page neq $output.navi.pages_total}
					<div class="past"><a href="{$output.navi.next}">Прошлое</a><img src="/img/right-arrow.gif" alt=""/>
					</div>
				{else}
					<div class="past">Прошлое<img src="/img/right-arrow.gif" alt=""/></div>
				{/if}
			</div>*}

		</div>
	{/if}

{elseif $output.state eq 'item'}

	<h1 class="mj-plugin__title mj-news">{$output.item.title}</h1>

	<div class="mj-plugin__box mj-news__topic">

		{if $output.item.words neq ''}
			<div class="mj-plugin__tags">
				123
			</div>
		{/if}

		{if $output.item.content neq ''}
			{$output.item.content}
		{else}
			Кажется, мы сошли с ума и ничего не написали об этом. Будьте добры, перезвоните на +38 093 881 44 81 и сообщите об этом.
		{/if}

	</div>

{else} ERROR {/if}


