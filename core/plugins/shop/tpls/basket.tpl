<fieldset id="shop_basket" style="border: 1px solid red;" data-order="{if $output.order}{$output.order->toArray()|json_encode|htmlspecialchars}{else}{ }{/if}">
	<legend>BASKET</legend>

	<!-- ko if: loading -->
	<div>LOADING</div>
	<!-- /ko -->

	<!-- ko if: !order() -->
	BASKET EMPTY
	<!-- /ko -->

	<!-- ko if: order() -->
		PRICE: <!-- ko text: order().price() --><!-- /ko -->
		<br>
		Items:
		<!-- ko foreach: Object.keys(order().items() || { }) -->
		<div data-bind="with: $parent.order().items()[$data]">
			{*<!-- ko if: item.media.length -->*}
			{*<img src="" data-bind="attr: { src: item.media[0].miniurl }">*}
			{*<!-- /ko -->*}
			<!-- ko text: item.name --><!-- /ko -->
			<!-- ko text: item.price --><!-- /ko -->x<!-- ko text: quantity --><!-- /ko -->
			<button type="button" data-bind="click: function () { $parents[1].remove(item.id); }">REMOVE ONE</button>
		</div>
		<!-- /ko -->
	<!-- /ko -->

</fieldset>
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