<div id="shop_basket" class="sal-basket js-common-dropDown"  data-order="{if $output.order}{$output.order->toArray()|json_encode|htmlspecialchars}{else}{ }{/if}">
	<div class="sal-ui__button sal-ui__button_light sal-basket__button js-common-dropDown__toggle" data-bind="dropDown: { addClass: 'sal-basket__button_open' }">
		<span class="sal-basket__button__title">My cart</span>
		<span class="sal-basket__button__count">
			<!-- ko if: !order() -->
				Empty
			<!-- /ko -->
			<!-- ko if: order() -->
			<!-- ko text: Object.keys(order().items() || { }).length + ' items' --><!-- /ko -->
			<!-- /ko -->
		</span>
	</div>
	<!-- ko if: order() -->
	<div class="sal-basket__dropdown js-common-dropDown__dropdown" style="display: none;" data-bind="click: function (d, e) { e.stopPropagation(); }">
		<div class="sal-basket__dropdown__header">My cart</div>
		<div class="sal-basket__dropdown__contents">
			<!-- ko if: loading -->
			<div class="sal-basket__dropdown__contents__loading"></div>
			<!-- /ko -->

			<div class="sal-basket__dropdown__contents__header">
				My cart
			</div>
			<!-- ko foreach: Object.keys(order().items() || { }) -->
			<div data-bind="with: $parent.order().items()[$data]" class="sal-basket__dropdown__item">
				<div class="sal-basket__dropdown__item__image" data-bind="attr: { style: 'background-image: url(\''+(item.media.length ? item.media[0].miniurl : '/svg/nophoto.svg')+'\')' }"></div>

				<a href="" data-bind="attr: { href: '/?action=item&id=' + item.id }, text: item.name" class="sal-basket__dropdown__item__link"></a>

				<div class="sal-basket__dropdown__item__info">
					<money class="sal-basket__dropdown__item__price" data-bind="money: item.price"></money>

					<div class="sal-basket__dropdown__item__count">
						Count:
						<input type="text" readonly data-bind="textInput: quantity" class="sal-basket__dropdown__item__count__field">
						<span class="sal-basket__dropdown__item__count__remove" data-bind="click: function () { $parents[1].remove(item.id); }">Remove</span>
					</div>

					<div class="sal-basket__dropdown__item__sum">
						Sum:
						<money class="sal-basket__dropdown__item__sum__amount" data-bind="money: (item.price * quantity)"></money>
					</div>
				</div>
			</div>
			<!-- /ko -->

			<div class="sal-basket__dropdown__contents__footer">
				<div class="sal-basket__dropdown__contents__footer__total">
					Total:
					<money data-bind="money: order().price()" class="sal-basket__dropdown__contents__footer__total__amount"></money>
				</div>
				<div class="sal-basket__dropdown__contents__footer__buttons">
					<div data-bind="click: function () { $('body').click(); }" class="sal-ui__button sal-ui__button_light sal-basket__dropdown__contents__footer__buttons__button sal-basket__dropdown__contents__footer__buttons__button_back">Back to shop</div>
					<a href="#" class="sal-ui__button sal-basket__dropdown__contents__footer__buttons__button sal-basket__dropdown__contents__footer__buttons__button_checkout">Checkout</a>
				</div>
			</div>
		</div>
	</div>
	<!-- /ko -->
</div>

<script>
$(function () {
	var $el = $('#shop_basket');
	if ($el.length > 0) {
		var VM = {
				order: ko.observable(),
				loading: ko.observable(false),
				add: function (options) {
					this._ajax('add',options);
				},
				remove: function (id) {
					this._ajax('remove', { id: id, quantity: 1 });
				},
				_ajax: function (command, params) {
					if (VM.loading()) {
						return;
					}

					VM.loading(true);
					console.log(params);
					$.post(
							'{$output._baseURL}?_ajaxModule=basket&_action='+command,
							params,
							function (result) {
								VM.loading(false);
								VM.processOrder(result);
							},
							'json'
						)
						.error(function () {
							VM.loading(false);
						});
				},
				processOrder: function (data) {
					data.price = ko.observable(data.price);
					data.items = ko.observable(data.items);

					VM.order(data);
				}
			},
			data = $el.attr('data-order');

		try {
			data = JSON.parse($el.attr('data-order'));

			VM.processOrder(data);
		}
		catch (e) { }

		ko.applyBindings(VM, $el[0]);

		$(document).on('js-addToOrder', function (e, params) {
			VM.add(params);
		});
	}
});
</script>