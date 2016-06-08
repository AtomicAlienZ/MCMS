{*{function name=TPLBuildCat}*}
	{*{if $cat->isVisible()}*}
	{*<div style="padding: 20px 20px 20px 40px; box-shadow: 0px 2px 4px rgba(20, 20, 20, .1); margin: 20px 0;">*}
		{*Category id: {$cat->getId()}*}
		{*<br>*}
		{*Alias: {$cat->getAlias()}*}
		{*<br>*}
		{*Names: {foreach from=$langs item=l}"{$cat->getName($l)}" ({$l}), {/foreach}*}
		{*{if $cat->getFieldset()}*}
			{*<br>*}
			{*Fieldset: {$cat->getFieldset()->getName()}*}
		{*{/if}*}
		{*<br>*}
		{*{if $cat->isVisible()}*}
			{*<a class="sal-ui__button" style="margin-top: 20px;" href="{$output._baseURL}?id={$cat->getId()}">VIEW</a>*}
		{*{/if}*}
		{*{foreach from=$cat->getChildren() item=child name=pewpew}*}
			{*{TPLBuildCat cat=$child}*}
		{*{/foreach}*}
	{*</div>*}
	{*{/if}*}
{*{/function}*}

{*{foreach $output.tree as $cat}{TPLBuildCat cat=$cat}{/foreach}*}
{strip}
<div class="sal-mainPage">
	{if count($output.popular)}
		<div class="sal-mainPage__popular">
			<div class="sal-mainPage__popular__header">Popular</div>

			<div class="sal-slyScroll js-slyScroller">
				<div class="sal-slyScroll__frame js-slyScroller__frame">
					<div class="sal-slyScroll__frame__slidee">

						{foreach from=$output.popular item=item}
							{assign var=media value=$item->getMediaFirstImage()}
							<a href="/?action=item&id={$item->getId()}" class="sal-mainPage__popular__item">
								<div class="sal-mainPage__popular__item__image" style="background-image: url({if $media}{$media.url}{else}/svg/nophoto.svg{/if});"></div>
								<div class="sal-mainPage__popular__item__title">{$item->getName($lang)}</div>
								<money class="sal-mainPage__popular__item__price" amount="{$item->getPrice()}" currency="USD"></money>
							</a>
						{/foreach}

					</div>
				</div>
				<div class="sal-slyScroll__scrollBar js-slyScroller__scrollBar">
					<div class="sal-slyScroll__scrollBar__handle"></div>
				</div>
				<div class="sal-slyScroll__arrow sal-slyScroll__arrow_prev js-slyScrollerPrev"></div>
				<div class="sal-slyScroll__arrow sal-slyScroll__arrow_next js-slyScrollerNext"></div>
			</div>

		</div>
	{/if}

	<div class="sal-mainPage__recommended">
		{if count($output.recommended) > 0}
			{assign var=url value=$output.recommended[0]->getMediaFirstImage()}
			{assign var=url value=$url.url}
			<a href="#" class="sal-mainPage__recommended__main" style="background-image: url('{if $url}{$url}{else}/svg/nophoto.svg{/if}');">
				We recommend
			</a>
			{if count($output.recommended) > 1}
				{assign var=url value=$output.recommended[1]->getMediaFirstImage()}
				{assign var=url value=$url.url}
				<a href="#" class="sal-mainPage__recommended__secondary" style="background-image: url('{if $url}{$url}{else}/svg/nophoto.svg{/if}');"></a>
			{/if}
		{/if}
		<a href="#" class="sal-mainPage__recommended__info">
			Moneyback<br>guarantee
			<span class="sal-mainPage__recommended__info__learnMore">Learn more</span>
		</a>
	</div>

	{if count($output.sale)}
		<div class="sal-mainPage__sale">
			<div class="sal-mainPage__sale__header">On sale</div>

			<div class="sal-slyScroll js-slyScroller">
				<div class="sal-slyScroll__frame js-slyScroller__frame">
					<div class="sal-slyScroll__frame__slidee">

						{foreach from=$output.sale item=item}
							{assign var=media value=$item->getMediaFirstImage()}
							<a href="/?action=item&id={$item->getId()}" class="sal-mainPage__sale__item">
								<div class="sal-mainPage__sale__item__image" style="background-image: url({if $media}{$media.url}{else}/svg/nophoto.svg{/if});"></div>
								<div class="sal-mainPage__sale__item__title">{$item->getName($lang)}</div>
								<money class="sal-mainPage__sale__item__price" amount="{$item->getPrice()}" currency="USD"></money>
							</a>
						{/foreach}

					</div>
				</div>
				<div class="sal-slyScroll__scrollBar js-slyScroller__scrollBar">
					<div class="sal-slyScroll__scrollBar__handle"></div>
				</div>
				<div class="sal-slyScroll__arrow sal-slyScroll__arrow_prev js-slyScrollerPrev"></div>
				<div class="sal-slyScroll__arrow sal-slyScroll__arrow_next js-slyScrollerNext"></div>
			</div>

		</div>
	{/if}
</div>
{/strip}
