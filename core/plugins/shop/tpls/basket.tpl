<fieldset id="shop_basket" style="padding: 20px 20px 20px 40px; box-shadow: 0px 2px 4px rgba(20, 20, 20, .1); margin: 20px 0;" data-order="{if $output.order}{$output.order->toArray()|json_encode|htmlspecialchars}{else}{ }{/if}">
	<legend>BASKET</legend>

	<!-- ko if: !order() -->
	BASKET EMPTY
	<!-- /ko -->

	<!-- ko if: order() -->
		PRICE: <!-- ko text: order().price() --><!-- /ko -->
		<br>
		Items:
		<!-- ko foreach: Object.keys(order().items() || { }) -->
		<div data-bind="with: $parent.order().items()[$data]">
			<!-- ko if: item.media.length -->
			<img src="" data-bind="attr: { src: item.media[0].miniurl }">
			<!-- /ko -->
			<!-- ko text: item.name --><!-- /ko -->
			<!-- ko text: item.price --><!-- /ko -->x<!-- ko text: quantity --><!-- /ko -->
		</div>
		<!-- /ko -->
	<!-- /ko -->

</fieldset>
<script>
$(function () {
	var $el = $('#shop_basket');
	if ($el.length > 0) {
		var VM = {
				order: ko.observable()
			},
			data = $el.attr('data-order');

		try {
			data = JSON.parse($el.attr('data-order'));

			console.log(data);

			data.price = ko.observable(data.price);
			data.items = ko.observable(data.items);

			VM.order(data);
		}
		catch (e) { }

		ko.applyBindings(VM, $el[0]);
	}
});
</script>