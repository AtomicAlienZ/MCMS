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
			TODO
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
		<div class="sal-itemView__similar__items">
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
	{/strip}
</div>