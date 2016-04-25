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
	<div data-bind="foreach: pageContent">
		<div style="padding: 20px;margin: 20px;border: 1px solid;">
			<!-- ko text: name --><!-- /ko --><br>
			<!-- ko text: price --><!-- /ko -->
		</div>
	</div>
</div>