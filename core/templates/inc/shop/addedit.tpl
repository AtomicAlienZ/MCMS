{assign var=fieldset value=$output.category->getFieldset()}

<script>
	_GLOBAL_SHOP_LANGS = {$langs|json_encode};
	_GLOBAL_SHOP_LANG = '{$lang}';
	_GLOBAL_SHOP_ITEM_DATA = {};
	_GLOBAL_SHOP_FIELDCONFIG = {if $fieldset}{$fieldset->getFields()|json_encode}{else}[]{/if};
</script>
<script src="/js/shop/item_addedit.js"></script>

<h1>
	Add item to category {$output.category->getName($lang)}
</h1>

<form action="" method="POST" id="shop_item_addedit" data-bind="attr: { 'style': isValid() ? '' : 'background-color: rgba(255,0,0,0.1);' }">
	<!-- ko foreach: Object.keys(name) -->
		<fieldset>
			<legend>Name (<b data-bind="text: $data"></b>) *</legend>
			<input type="text" data-bind="textInput: $parent.name[$data]">
			<span style="font-weight: bold;color: #ff0000;" data-bind="text: $parent.nameErrors[$data]"></span>
		</fieldset>

		<fieldset>
			<legend>Desc (<b data-bind="text: $data"></b>) *</legend>
			<textarea rows="10" cols="80" data-bind="textInput: $parent.desc[$data]"></textarea>
			<span style="font-weight: bold;color: #ff0000;" data-bind="text: $parent.descErrors[$data]"></span>
		</fieldset>
	<!-- /ko -->

	<fieldset>
		<legend>Active</legend>
		<input type="checkbox" data-bind="checked: is_active">
	</fieldset>

	<fieldset>
		<legend>Media</legend>
		<b style="color: red;">TODO</b>
	</fieldset>

	<fieldset>
		<legend>Price *</legend>
		<input type="text" data-bind="textInput: price">
		<span style="font-weight: bold;color: #ff0000;" data-bind="text: priceError()"></span>
	</fieldset>

	<!-- ko if: fields.length -->
	<h2>
		CATEGORY FIELDS
	</h2>
	<div data-bind="foreach: fields">
		<fieldset>
			<legend data-bind="text: names[$root.lang] + ' (' + type + ') ' + (required ? '*' : '')"></legend>
			<!-- ko if: type == 'string' || type == 'int' || type == 'float' -->
			<input type="text" data-bind="textInput: value">
			<!-- /ko -->

			{* TODO DATE *}

			<!-- ko if: type == 'text' || type == 'html' -->
			<textarea cols="30" rows="10" data-bind="textInput: value"></textarea>
			<!-- /ko -->

			<!-- ko if: type == 'select' -->
			<select data-bind="
				options: options,
				optionsText: function (item) {
					return item.names[$root.lang];
				},
				optionsValue: function (item) {
					return item.id;
				},
				optionsCaption: 'Select a value',
				value: value
			"></select>
			<!-- /ko -->

			<!-- ko if: type == 'multiselect' -->
			<select multiple="multiple" data-bind="
				options: options,
				optionsText: function (item) {
					return item.names[$root.lang];
				},
				optionsValue: function (item) {
					return item.id;
				},
				selectedOptions: value
			"></select>
			<!-- /ko -->

			<!-- ko if: type == 'date' -->
			<input type="text" data-bind="textInput: value">
			<!-- /ko -->

			<span style="font-weight: bold;color: #ff0000;" data-bind="text: error"></span>
		</fieldset>
	</div>
	<!-- /ko -->

	<button type="button" data-bind="click: save">SAVE</button>
</form>