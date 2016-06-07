<div id="fieldsetForm">
	<h1>
		<span>
			{if $item}
				Редактирование набора полей
			{else}
				Новый набор полей
			{/if}
		</span>
	</h1>

	<input type="hidden" value="shop_manage" name="plg">
	<input type="hidden" value="addFieldset" name="arg[action]">

	<div>
		Name *
		<input type="text" data-bind="textInput: name">
	</div>

	<!-- ko foreach: fields -->
	<div style="border: padding: 20px 20px 20px 40px; box-shadow: 0px 2px 4px rgba(20, 20, 20, .1); margin: 20px 0;">
		<input type="hidden">
		<!-- ko foreach: Object.keys(names) -->
			<div>
				Name (<!-- ko text: $data --><!-- /ko -->)*:
				<input type="text" data-bind="textInput: $parent.names[$data]">
			</div>
		<!-- /ko -->
		<div>
			Type:
			<select data-bind="options: types, value: type, optionsText: function (name) { return name.toUpperCase(); }"></select>
		</div>

		<!-- ko if: shouldHaveOptions -->
		<div>
			<b>OPTIONS</b>
			<div data-bind="foreach: options">
				<div style="border: 1px dashed blue;padding: 10px;margin: 10px;display: inline-block;vertical-align: top;">
					<div>
						ID* (unique to field):
						<input type="text" data-bind="textInput: id">
					</div>
					<!-- ko foreach: Object.keys(names) -->
					<div>
						Name (<!-- ko text: $data --><!-- /ko -->)*:
						<input type="text" data-bind="textInput: $parent.names[$data]">
					</div>
					<!-- /ko -->
					<button data-bind="click: function () { $parent.options.remove($data); }">remove option</button>
				</div>
			</div>
			<button data-bind="click: function () { addOption() }">add option</button>
		</div>
		<!-- /ko -->

		<div>
			Required:
			<input type="checkbox" data-bind="checked: required">
		</div>
		<div>
			enabled:
			<input type="checkbox" data-bind="checked: enabled">
		</div>
		<div><b data-bind="text: isValid() ? 'VALID' : 'ERROR'"></b></div>
		<button data-bind="click: function () { $parent.fields.remove($data); }">REMOVE FIELD</button>
	</div>
	<!-- /ko -->
	<button data-bind="click:addField">ADD</button>
	<hr>
	<div><b data-bind="text: isValid() ? 'VALID' : 'ERROR'"></b></div>
	<button data-bind="click: send">!!!</button>
</div>

<form enctype="multipart/form-data" action="/admin/index.php?plg=shop_manage&cmd=fieldsets&arg[action]={if $item}editFieldset&arg[id]={$item->getId()}{else}addFieldset{/if}" method="post" id="fieldsetFormActual">
</form>
<script src="/js/knockout-3.4.0.js"></script>
<script>
	var _GLOBAL_LANGS = ['{"','"|implode:cms_core::getLanguages()}'],
		_GLOBAL_TYPES = ['{"','"|implode:Shop_Fieldset::$fieldTypes}'],
		_GLOBAL_NAME = {if $item}{$item->getName()|json_encode}{else}''{/if},
		_GLOBAL_FIELDS = {if $item}{$item->getFields()|json_encode}{else}[]{/if};

{literal}
	function Field (data) {
		if (!data) {
			data = {};
		}

		this.id = 0;

		if (data.id) {
			this.id = data.id;

			if (this.id > Field.prototype.idCounter) {
				Field.prototype.idCounter = this.id;
			}
		}
		else {
			this.id = Field.prototype.idCounter;
		}

		Field.prototype.idCounter++;

		this.names = {};
		for (var i = 0; i < _GLOBAL_LANGS.length; i++) {
			this.names[_GLOBAL_LANGS[i]] = ko.observable(data.names ? data.names[_GLOBAL_LANGS[i]] || '' : '');
		}

		this.types = _GLOBAL_TYPES;
		this.type = ko.observable(data.type && _GLOBAL_TYPES.indexOf(data.type) >= 0 ? data.type : _GLOBAL_TYPES[0]);
		this.required = ko.observable(data.required);
		this.enabled = ko.observable(data.enabled);

		// TODO FILL OPTIONS
		this.options = ko.observableArray([]);

		this.shouldHaveOptions = ko.computed(function () {
			return this.type() == 'select' || this.type() == 'multiselect';
		}, this);

		this.isValid = ko.computed(function () {
			var ret = true;
			for (var i in this.names) {
				if (this.names.hasOwnProperty(i)) {
					ret = ret && ($.trim(this.names[i]()) != '')
				}
			}

			// Checking options if needed
			if (this.shouldHaveOptions()) {
				var options = this.options(),
						ids = {},
						uniqueIDs = 0;

				ret = ret && options.length > 0;

				for (var i = 0; i < options.length; i++) {
					var tmp = $.trim(options[i].id());

					ret = ret && (tmp != '');

					for (var j in options[i].names) {
						if (options[i].names.hasOwnProperty(j)) {
							ret = ret && ($.trim(options[i].names[j]()) != '')
						}
					}

					if (!ids[tmp]) {
						ids[tmp] = true;
						uniqueIDs++;
					}
				}

				ret = ret && (options.length == uniqueIDs);
			}

			return ret;
		}, this);

		if (data.options) {
			for (var i in data.options) {
				if (data.options.hasOwnProperty(i)) {
					this.addOption(data.options[i]);
				}
			}
		}
	}

	Field.prototype.addOption = function (data) {
		if (!data) {
			data = {};
		}

		if (this.shouldHaveOptions()) {
			var t = {
				id: ko.observable(data.id || ''),
				names: {}
			};

			for (var i = 0; i < _GLOBAL_LANGS.length; i++) {
				t.names[_GLOBAL_LANGS[i]] = ko.observable(data.names ? data.names[_GLOBAL_LANGS[i]] || '' : '');
			}

			this.options.push(t);
		}
	};

	Field.prototype.toStructure = function () {
		ret = {
			id: this.id,
			names: {}
		};

		for (var i in this.names) {
			if (this.names.hasOwnProperty(i)) {
				ret.names[i] = this.names[i]();
			}
		}
		for (var i in this) {
			if (this.hasOwnProperty(i) && ko.isObservable(this[i]) && i != 'options' && !ko.isComputed(this[i])) {
				ret[i] = this[i]();

				if (i == 'type' && this.shouldHaveOptions()) {
					var o = this.options();

					ret.options = [];

					for (var j = 0; j < o.length; j++) {
						var tmp = {
							id: o[j].id(),
							names: {}
						};

						for (var k in o[j].names) {
							if (o[j].names.hasOwnProperty(k) && ko.isObservable(o[j].names[k])) {
								tmp.names[k] = o[j].names[k]();
							}
						}

						ret.options.push(tmp);
					}
				}
			}
		}

		return ret;
	}

	Field.prototype.idCounter = 0;

	$(function () {
		var viewModel = {
			name: ko.observable(''),
			fields: ko.observableArray(),
			send: function () {
				if (this.isValid()) {
					function buildStuff (node, path) {
						var ret = [];

						path = path || '';

						for (var i in node) {
							if (node.hasOwnProperty(i)) {
								if (typeof node[i] == 'object' && node[i] != null) {
									buildStuff(node[i], path+'['+i+']');
								}
								else {
									form.push({
										name: path+'['+i+']',
										value: node[i]
									});
								}
							}
						}
					}

					var t = this.fields(),
						form = [
								{
									name: '[name]',
									value: this.name()
								}
							],
							$form = $('#fieldsetFormActual');

					for (var i = 0; i < t.length; i++) {
						buildStuff(t[i].toStructure(),'[fields]['+i+']');
					}

					for (var i = 0; i < form.length; i++) {
						var $tmp = $('<input name="FORM'+form[i].name+'" type="hidden">');
						$tmp.val($.trim(form[i].value));

						$form.append($tmp);
					}

					$form.submit();
				}
			},
			addField: function () {
				this.fields.push(new Field());
			}
		};

		viewModel.isValid = ko.computed(function () {
			var fields = this.fields(),
				ret = fields.length > 0 && $.trim(this.name()) != '';

			for (var i = 0; i < fields.length; i++) {
				ret = ret && fields[i].isValid();
			}

			return ret;
		}, viewModel);

		viewModel.name(_GLOBAL_NAME);
		for (var i = 0; i < _GLOBAL_FIELDS.length; i++) {
			viewModel.fields.push(new Field(_GLOBAL_FIELDS[i]));
		}

		ko.applyBindings(viewModel, document.getElementById('fieldsetForm'));
	});
{/literal}
</script>
