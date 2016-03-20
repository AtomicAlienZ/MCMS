{if $output.state eq 'list'}

<div class="mj-flow">

    <h1 class="mj-flow__title mj-plugin__title">
		<a class="mj-news__item__caption mj-link" href="/{$lang}/{$output.items[0].categoryurl}/">
			{$output.settings.title}
		</a>
	</h1>

    {assign var='news' value=$output.items}
    {section loop=$news name=item}
		{if $news[item].descr neq ''}

        <div class="mj-flow__item">

            <div class="mj-flow__item__date">{$news[item].dates}</div>

			<a class="mj-flow__item__caption" href="/{$lang}/{$news[item].categoryurl}/{$news[item].alias}/">
                <span class="mj-flow__item__caption__title mj-link"> {$news[item].title} </span><br/>
				<span class="mj-flow__item__caption__description">{$news[item].descr}</span>
            </a>

        </div>

		{/if}

    {/section}

</div>

{elseif $output.state eq 'item'}

    <h1 class="mj-plugin__title mj-news">{$output.item.title}</h1>

	<div class="mj-plugin__box mj-news__topic">

		{if $output.item.words neq ''}
			<div class="mj-plugin__tags">

			</div>
		{/if}

		{if $output.item.content neq ''}
			{$output.item.content}
		{else}
			Кажется, мы сошли с ума и ничего не написали об этом. Будьте добры, перезвоните на +38 093 881 44 81 и сообщите об этом.
		{/if}

	</div>


{else} ERROR {/if}



