{if $output.state == "done"}
	123
	{if $output.count_items > 0}
		<form OnSubmit="return SysBasketUpdate(this);" id="sysform-basket" method="post" action="{php}echo "http://".$_SERVER['SERVER_NAME']."/catalog/basket/";{/php}{*$output.basket_url*}?action=edit" name="bskt">
			<div id="basket-contents">
				{assign var='items' value=$output.items}
				{section loop=$items name=key}
					<!-- Basket items -->
					<div class="bc-i">
						<div class="bc-i-img"><img src="{$items[key].img_sm1}" /></div>
						<div class="bc-i-info">
							<div class="bc-i-name">{$items[key].title}</div>
							<table class="bc-i-table">
								<tr>
									<td width="100">Цена</td>
									<td width="100">Количество</td>
									<td width="100">Сумма</td>
									<td width="100" rowspan="2">
										<a class="sysbtn-delete-item-from-basket" href="#{$items[key].id}">
											<img src="/img/cross.png" />
										</a>
									</td>
								</tr>
								<tr>
									<td width="100">{$items[key].price} {$output.currency.title}</td>
									<td width="100">
										<div class="bc-i-input"><input id="sysfield-item-count-{$items[key].id}" type="text" name="basket[{$items[key].id}][quantity]" value="{$items[key].quantity}"></div>
									</td>
									<td width="100">{$items[key].cost} {$output.currency.title}</td>
								</tr>
							</table>
						</div>
					</div>
				{/section}
				
				
				<!-- Summary -->
				<div class="bc-sum">
					<div class="bc-i-img"><img src="/img/blank.gif" /></div>
					<div class="bc-i-info">
						<table class="bc-i-table">
							<tr>
								<td width="100">Общая сумма</td>
								<td width="100">{$output.total_cost} {$output.currency.title}</td>
								<td width="100"><a id="sysbtn-reload-basket" href="#" onclick="document.bskt.onsubmit(); return false;">пересчитать</a></td>
							</tr>
						</table>
					</div>
				</div>
			</div>
		</form>
	{else}
		<p id="empty-basket" class="exists-this-element">Корзина пока что пуста</p>
	{/if}
{elseif $output.state == "error"}
	<p id="empty-basket" class="exists-this-element">Корзина пока что пуста</p>
{/if}