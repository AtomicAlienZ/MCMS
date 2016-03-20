{* Шаблон показа информации о содержимом корзины покупок *}
<script>
	var coint_item_in_basket = {if $output.total_quantity>0}{$output.total_quantity}{else}0{/if};
</script>
<div class="news-side">
{*	<div class="title"><a href="{$output.url}">{if $output.settings.title>''}{$output.settings.title}{else}<img class="png" src="/img/blank.gif" style="background: url(/img/news-side/title.png)" width="111" height="27"/>{/if}</a></div>*}
	<div class="list">
		{if $output.state == "done"}
			<div class="name"><a href="{$output.basket_url}" id="coint_item_in_basket">Ваша корзина: {$output.total_quantity} товар(ов) на сумму {$output.total_cost} {$output.currency.title}</a></div>
		{elseif $output.state == "error"}
			<p><a href="{$output.basket_url}" id="coint_item_in_basket">Ваша корзина: пока пуста</a></p>
		{/if}
	</div>
</div>