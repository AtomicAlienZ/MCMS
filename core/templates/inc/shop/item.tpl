{*{$output|var_dump}*}
{*{$output.item->getMedia()|var_dump}*}
<script>
	$(document).on('click','.js-buyButton', function () {
		var $this = $(this);

		$(document).trigger('js-addToOrder', { items: [ { id: $this.attr('data-id'), quantity: 1 } ] });
	});
</script>

<div class="sal-itemView">
	<nav class="sal-itemView__catPath">
		<a href="/" class="sal-itemView__catPath__item">Catalogue</a>
		{foreach from=$output.item->getCategory()->getParentsArray() item=cat}
			&mdash;
			<a href="/?id={$cat->getId()}" class="sal-itemView__catPath__item">{$cat->getName($lang)}</a>
		{/foreach}
		&mdash;
		<a href="/?id={$output.item->getCategory()->getId()}" class="sal-itemView__catPath__item">{$output.item->getCategory()->getName($lang)}</a>
	</nav>

	<div class="sal-itemView__header">
		{$output.item->getName($lang)}
	</div>

	<div class="sal-itemView__content">
		<div class="sal-itemView__content__gallery">
			{assign var=media value=$output.item->getMediaActive()}
			{if count($media)}
				<div class="sal-itemView__content__gallery__fotorama js-fotoramaCustom"
				     data-fit="cover"
				     data-auto="false" {*needed for fullscreen magic*}
				     data-allowfullscreen="true"
				     data-width="100%"
				     data-ratio="1/1"
				     data-nav="thumbs"
				     data-thumbwidth="93"
				     data-thumbheight="93"
				     data-thumbfit="contain"
				     data-thumbmargin="8">
					{foreach from=$media item=item}
						{if $item.type == 'image'}
							<a href="{$item.url}" data-full="{$item.originalurl}">
								<img src="{if isset($item.miniurl)}{$item.miniurl}{/if}">
							</a>
						{elseif $item.type == 'video'}
							<div data-thumb="/svg/youTubeIcon.svg">
								{assign var=url value=$item.url}
								{assign var=url value=$url|replace:'https://youtu.be/':''}
								{assign var=url value=$url|replace:'https://www.youtube.com/watch?v=':''}
								<iframe width="400" height="400" src="https://www.youtube.com/embed/{$url}?enablejsapi=1" class="js-fotoramaCustom__videoIframe" frameborder="0" allowfullscreen></iframe>
							</div>
						{/if}
					{/foreach}
				</div>

				<script>
					$('.js-fotoramaCustom')
						.on('fotorama:fullscreenenter fotorama:fullscreenexit', function (e, fotorama) {
							if (e.type === 'fotorama:fullscreenenter') {
								// Options for the fullscreen
								fotorama.setOptions({
									fit: 'scaledown'
								});
							} else {
								// Back to normal settings
								fotorama.setOptions({
									fit: 'cover'
								});
							}
						})
						.on('fotorama:show', function (e, fotorama) {
							var $players = $('.js-fotoramaCustom__videoIframe');

							for (var i = 0; i < $players.length; i++) {
								$players[i].contentWindow.postMessage('{ "event":"command","func":"pauseVideo","args":"" }', '*')
							}
						})
						.fotorama();
				</script>
			{else}
				<div class="sal-itemView__content__gallery__noImage"></div>
			{/if}
		</div>
		<div class="sal-itemView__content__buy">
			<money class="sal-itemView__content__buy__price" amount="{$output.item->getPrice()}" currency="USD"></money>
			<div class="sal-ui__button sal-itemView__content__buy__addToCart js-buyButton" data-id="{$output.item->getId()}">Add to cart</div>
		</div>
		<div class="sal-itemView__content__description">
			<div class="sal-itemView__content__description__title">Description</div>
			<p>{$output.item->getDesc($lang)|replace:"\n":"</p><p>"}</p>
		</div>
		<div class="sal-itemView__content__fields">
			<div class="sal-itemView__content__fields__title">Parameters</div>
			<table class="sal-itemView__content__fields__fields" cellspacing="0" cellpadding="0">
				{foreach from=$output.item->getFields($lang) item=field}
					<tr class="sal-itemView__content__fields__field">
						<td class="sal-itemView__content__fields__field__name">
							{$field.name}
						</td>
						<td class="sal-itemView__content__fields__field__value">
							{if is_array($field.value)}
								{foreach from=$field.value item=valuepart name=fieldIter}{if !$smarty.foreach.fieldIter.first}, {/if}{$valuepart}{/foreach}
							{else}
								{$field.value}
							{/if}
						</td>
					</tr>
				{/foreach}
			</table>
		</div>
	</div>

	{strip}
		<div class="sal-itemView__similar">
			<div class="sal-itemView__similar__title">Similar items</div>

			<div class="sal-slyScroll js-slyScroller">
				<div class="sal-slyScroll__frame js-slyScroller__frame">
					<div class="sal-slyScroll__frame__slidee">

						{foreach from=$output.similar item=item}
							{assign var=media value=$item->getMediaFirstImage()}
							<a href="/?action=item&id={$item->getId()}" class="sal-itemView__similar__item">
								<div class="sal-itemView__similar__item__image" style="background-image: url({if $media}{$media.url}{else}/svg/nophoto.svg{/if});"></div>
								<div class="sal-itemView__similar__item__title">{$item->getName($lang)}</div>
								<money class="sal-itemView__similar__item__price" amount="{$item->getPrice()}" currency="USD"></money>
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
	{/strip}
</div>