{assign var=fieldset value=$output.category->getFieldset()}

<script>
	_GLOBAL_SHOP_LANGS = {$langs|json_encode};
	_GLOBAL_SHOP_LANG = '{$lang}';
	_GLOBAL_SHOP_ITEM_DATA = {if isset($output.item)}{$output.item->toArray()|json_encode}{else}{ }{/if};
	_GLOBAL_SHOP_FIELDCONFIG = {if $fieldset}{$fieldset->getFields()|json_encode}{else}[]{/if};
</script>
<script src="/js/shop/item_addedit.js"></script>

<h1>
	Add item to category {$output.category->getName($lang)}
</h1>

<form action="" enctype="multipart/form-data" method="POST" id="shop_item_addedit" data-bind="attr: { 'style': isValid() ? '' : 'background-color: rgba(255,0,0,0.1);' }">
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
		<!-- ko foreach: media -->
		<div style="display: inline-block;vertical-align: top;padding: 20px;margin: 10px;border: 1px dashed blue;position: relative;">
			<div data-bind="text: mediaId" style="background: blue;color: #fff;position: absolute;top: 0;left: 0;width: 20px;height: 20px;line-height: 20px;text-align: center;font-size: 13px;"></div>
			<!-- ko if: typeof _new  != 'undefined' -->
				<label>
					<input type="radio" data-bind="checked: type" value="image">
					IMAGE
				</label>
				<label>
					<input type="radio" data-bind="checked: type" value="video">
					VIDEO
				</label>

				<!-- ko if: type() == 'image' -->
				<input type="file" data-bind="attr: { name: 'FORM[media]['+mediaId+']' }">
				<!-- /ko -->
				<!-- ko if: type() == 'video' -->
				YOUTUBE URL: <input type="text" data-bind="textInput: url">
				<!-- /ko -->
			<!-- /ko -->


			<!-- ko if: typeof _new  == 'undefined' -->
			<!-- ko if: data.type == 'video' -->
			VIDEO: <a data-bind="text: data.url, attr: { href: data.url }" target="_blank"></a>
			<!-- /ko -->

			<!-- ko if: data.type != 'video' -->
			IMAGE: <!-- ko text: data.originalname --><!-- /ko --><br>
			<img style="max-width: 200px;max-height: 200px;" data-bind="attr: { src: data.url }">
			<br>
			<!-- ko text: data.width + 'x' + data.height --><!-- /ko -->
			<br>
			<a data-bind="attr: { href: data.originalurl }" target="_blank">
				original <!-- ko text: data.originalwidth + 'x' + data.originalheight --><!-- /ko -->
			</a>
			<br>
			<a data-bind="attr: { href: data.miniurl }" target="_blank">
				miniature
			</a>
			<!-- /ko -->

			<!-- /ko -->
			<hr style="margin: 10px -20px;width: auto;max-width: 10000vw;">
			Active: <input type="checkbox" data-bind="checked: active"><br>
			Order: <input type="text" data-bind="textInput: order" size="5"><br>
			<button type="button" data-bind="click: function () { $parent.media.remove($data); }">DELETE</button>
		</div>
		<!-- /ko -->
		<br>
		<button type="button" data-bind="click: addMediaItem">ADD MEDIA</button>
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