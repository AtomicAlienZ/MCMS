{if $structure.alias neq 'ru'}
	{assign var="language" value=$structure.alias}
{else}
	{assign var="language" value=""}
{/if}

<div class="b-news-small mj-news">

    <h2 class="b-news-small-title mj-plugin__title">
		<a class="mj-news__item__caption mj-link" href="/{$lang}/{$output.items[0].categoryurl}/">
			{if $output.settings.title>''}{$output.settings.title}{/if}
		</a>
	</h2>

    {assign var='items' value=$output.items}
    {section loop=$items name=key}

        <div class="b-news-small-item mj-news__item">
            <span class="b-news-small-item-date mj-news__item__date">{$items[key].dates|date_format:'%d.%m.%Y'}&nbsp;</span>
            <a class="mj-news__item__caption mj-link" href="/{$lang}/{$items[key].categoryurl}/{$items[key].alias}/">
                {$items[key].title}
            </a>
        </div>
    {/section}

</div>
