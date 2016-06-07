<script>
	var _GLOBAL_SHOP_ITEMS = {$output.list|json_encode};
	var _GLOBAL_SHOP_FIELDSET = {if $output.category->getFieldset()}{$output.category->getFieldset()->getFields()|json_encode}{else}[]{/if};
	var _GLOBAL_LANG = '{$lang}';
</script>
<link rel="stylesheet" href="/js/jquery-ui-1.11.4.custom/jquery-ui.css">
<script src="/js/jquery-ui-1.11.4.custom/jquery-ui.js"></script>
<script src="/js/shop/categoryView.js"></script>
<script src="/js/shop/vm/Date.js"></script>
<script src="/js/shop/vm/postFilter/Abstract.js"></script>
<script src="/js/shop/vm/postFilter/Flag.js"></script>
<script src="/js/shop/vm/postFilter/Number.js"></script>
<script src="/js/shop/vm/postFilter/String.js"></script>

<div id="shop_category_view">
	SORT: <!-- ko text: sort --><!-- /ko -->
	<div data-bind="foreach: sortTypes">
		<span data-bind="text: $data, click: function () { $parent.sort($data); $parent.doSort($parent.sort()); $parent.buildPage(1); }"></span>
	</div>
	<hr>
	PAGE: <!-- ko text: page --><!-- /ko -->
	<!-- ko if: pages() > 1 -->
	<div data-bind="foreach: new Array(pages())">
		<span data-bind="text: $index() + 1, click: function () { $parent.buildPage($index() + 1); }"></span>
	</div>
	<!-- /ko -->
	<hr>
	FILTERS<br>
	<!-- ko foreach: filters -->
	<div style="padding: 20px;margin: 20px;border: 1px dashed blue;display: inline-block;vertical-align: top; width: 250px;">
		<h2 data-bind="text: config.name"></h2>
		<!-- ko if: config.type == 'Number' -->
		min: <!-- ko text: values().min --><!-- /ko --><br>
		max: <!-- ko text: values().max --><!-- /ko --><br><br>

		set min: <!-- ko text: displayValues.min --><!-- /ko --><br>
		set max: <!-- ko text: displayValues.max --><!-- /ko -->
		<div data-bind="PFNumberSlider: value"></div>
		<!-- /ko -->

		<!-- ko if: config.type == 'String' -->
			<div data-bind="
				css: { 'nemo-common-postFilters__filterList__filter__valuesBlock__values__value_checked' : (!(value())) || (value() == '') },
				click: clear,
				attr: { style: (!(value())) || (value() == '') ? 'color: red;' : '' }
			">
				NO MATTER
			</div>

			<!-- ko foreach: values -->
			<div class="nemo-common-postFilters__filterList__filter__valuesBlock__values__value"
			       data-bind="css: {
	                'nemo-common-postFilters__filterList__filter__valuesBlock__values__value_disabled' : count() == 0,
	                'nemo-common-postFilters__filterList__filter__valuesBlock__values__value_checked' : $parent.value() && $parent.value().indexOf(key) >= 0
	            },
	            text: value,
				attr: { style: $parent.value() && $parent.value().indexOf(key) >= 0 ? 'color: red;' : '' },
	            click: function () { $parent.selectValue(key); }"></div>
			<!-- /ko -->
		<!-- /ko -->
	</div>
	<!-- /ko -->
	<hr>

	<div class="sal-shop sal-shop_popular">

		<span class="sal-shop__label">Category view</span>

		<div class="sal-shop__container" data-bind="foreach: pageContent">



			<a class="sal-shop__item" href="{$output._baseURL}?action=view&id=" data-bind="attr: { href: '{$output._baseURL}?action=item&id=' + id }">
				<div class="sal-shop__item__thumb">

					<div class="sal-shop__item__special">-40%</div>

				</div>
				<div class="sal-shop__item__title">
					<!-- ko text: name --><!-- /ko -->

					<!-- ko foreach: fields -->
					<!-- ko if: enabled -->
						<span style="display: inline-block;vertical-align: top;margin-right: 20px;">
							<b data-bind="text: name"></b>
							<!-- ko if: typeof value != 'object' -->
								<!-- ko text: value --><!-- /ko -->
							<!-- /ko -->

							<!-- ko if: typeof value == 'object' -->
								<!-- ko foreach: Object.keys(value) -->
									<!-- ko text: $parent.value[$data] --><!-- /ko -->,
								<!-- /ko -->
							<!-- /ko -->
						</span>
					<!-- /ko -->
					<!-- /ko -->

				</div>

				<div class="sal-shop__item__prices">
					<div class="sal-shop__item__prices__price">
						<span class="sal-shop__item__prices__price__old"><!-- ko text: price --><!-- /ko --></span>
						<span class="sal-shop__item__prices__price__actual"><!-- ko text: price --><!-- /ko --></span>
						<span class="sal-shop__item__prices__price__description">Buy now</span>
					</div>
					<div class="sal-shop__item__prices__price">
						<span class="sal-shop__item__prices__price__actual"><!-- ko text: price --><!-- /ko --></span>
						<span class="sal-shop__item__prices__price__description">Current bid</span>
					</div>
				</div>

			</a>
		</div>

	</div>


</div>