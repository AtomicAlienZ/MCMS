$(function () {
	function Item (data, fieldconfig, lang) {
		if (typeof data != 'object') {
			data = {};
		}

		var self = this;

		this.lang = lang;

		this.name = {};
		this.desc = {};

		this.nameErrors = {};
		this.descErrors = {};

		for (var i = 0; i < _GLOBAL_SHOP_LANGS.length; i++) {
			this.name[_GLOBAL_SHOP_LANGS[i]] = ko.observable(data['name_'+_GLOBAL_SHOP_LANGS[i]] ? data['name_'+_GLOBAL_SHOP_LANGS[i]] : '');
			this.desc[_GLOBAL_SHOP_LANGS[i]] = ko.observable(data['desc_'+_GLOBAL_SHOP_LANGS[i]] ? data['desc_'+_GLOBAL_SHOP_LANGS[i]] : '');

			//this.nameErrors[_GLOBAL_SHOP_LANGS[i]] = ko.observable('');
			//this.descErrors[_GLOBAL_SHOP_LANGS[i]] = ko.observable('');
		}

		this.is_active = ko.observable(!!data['is_active']);

		this.price = ko.observable(data.price || '');
		this.priceError = ko.computed(function () {
			var price = parseFloat(this.price());

			if (price <= 0 || isNaN(price)) {
				return 'requred';
			}

			return '';
		}, this);

		this.media = ko.observableArray([]);

		this.errors = ko.observable();

		this.fields = [];

		for (var i = 0; i < fieldconfig.length; i++) {
			var tmp = fieldconfig[i];

			// Todo value setting
			if (tmp.type == 'miltiselect') {
				tmp.value = ko.observableArray([]);
			}
			else {
				tmp.value = ko.observable('');
			}

			if (
				tmp.type == 'string' ||
				tmp.type == 'text' ||
				tmp.type == 'html' ||
				tmp.type == 'int' ||
				tmp.type == 'float'
			) {
				tmp.value.subscribe(function (value) {
					var newValue = value;

					switch (this.type) {
						case 'int':
							newValue = newValue.toString().replace(/\s/g, '');
							newValue = parseInt(newValue, 10);
							break;
						case 'float':
							newValue = newValue.toString().replace(/\s/g, '');
							newValue = parseFloat(newValue);
							break;
					}

					if (
						(this.type == 'int' || this.type == 'float') &&
						isNaN(newValue)
					) {
						newValue = '';
					}

					if (value != newValue && value != '-') {
						this.value(newValue);
					}
				}, tmp);
			}

			tmp.error = ko.computed(function () {
				var value = this.value();

				if (this.required) {
					if (
						(this.type == 'miltiselect' && value.length == 0) ||
						((this.type == 'int' || this.type == 'float')  && value == 0) ||
						((this.type == 'string' || this.type == 'text' || this.type == 'html')  && !$.trim(value)) ||
						!value
					) {
						return 'required';
					}
				}

				return '';
			}, tmp);

			this.fields.push(tmp);
		}

		this.price.subscribe(function (value) {
			var correctValue = value.toString().replace(',','.');
			correctValue = parseFloat(correctValue);

			if (isNaN(correctValue)) {
				correctValue = '';
			}
			else {
				correctValue = correctValue.toString().split('.');
				if (correctValue[1] && correctValue[1].length > 2) {
					correctValue[1] = correctValue[1].substr(0, 2);
				}
				correctValue = parseFloat(correctValue.join('.'));
			}

			if (value != correctValue) {
				this.price(correctValue);
			}
		}, this);

		// Validation
		this._validateString = function (value) {
			if ($.trim(value) == '') {
				return 'required';
			}

			return '';
		};

		for (var i = 0; i < _GLOBAL_SHOP_LANGS.length; i++) {
			//this.name[_GLOBAL_SHOP_LANGS[i]].subscribe(this._validateString, this.nameErrors[_GLOBAL_SHOP_LANGS[i]]);
			//this.desc[_GLOBAL_SHOP_LANGS[i]].subscribe(this._validateString, this.descErrors[_GLOBAL_SHOP_LANGS[i]]);
			this.nameErrors[_GLOBAL_SHOP_LANGS[i]] = ko.computed(function () {
				return self._validateString(this());
			}, this.name[_GLOBAL_SHOP_LANGS[i]]);

			this.descErrors[_GLOBAL_SHOP_LANGS[i]] = ko.computed(function () {
				return self._validateString(this());
			}, this.desc[_GLOBAL_SHOP_LANGS[i]]);
		}

		this.isValid = ko.computed(function () {
			var ret = !!this.priceError();

			for (var i = 0; i < _GLOBAL_SHOP_LANGS.length; i++) {
				ret = ret || !!this.nameErrors[_GLOBAL_SHOP_LANGS[i]]();
				ret = ret || !!this.descErrors[_GLOBAL_SHOP_LANGS[i]]();
			}

			for (var i = 0; i < this.fields.length; i++) {
				ret = ret || !!this.fields[i].error();
			}

			return !ret;
		}, this);

		this.save = function() {
			if (this.isValid()) {
				var fields = {
						'[price]': this.price(),
						'[is_active]': this.is_active() ? 'y' : 'n'
					},
					$form = $('#shop_item_addedit');

				// Localized data
				for (var i = 0; i < _GLOBAL_SHOP_LANGS.length; i++) {
					fields['[name]['+_GLOBAL_SHOP_LANGS[i]+']'] = this.name[_GLOBAL_SHOP_LANGS[i]]();
					fields['[desc]['+_GLOBAL_SHOP_LANGS[i]+']'] = this.desc[_GLOBAL_SHOP_LANGS[i]]();
				}

				// Fields
				for (var i = 0; i < this.fields.length; i++) {
					var fieldVal = this.fields[i].value();
					if (this.fields[i].type == 'multiselect') {
						for (var j = 0; j < fieldVal.length; j++) {
							fields['[fields][' + this.fields[i].id + '][' + j + ']'] = fieldVal[j];
						}
					}
					else {
						fields['[fields][' + this.fields[i].id + ']'] = fieldVal;
					}
				}

				for (var i in fields) {
					if (fields.hasOwnProperty(i)) {
						var $tmp = $('<input type="hidden" name="FORM' + i + '">');

						$tmp.val(fields[i]);

						$form.append($tmp);
					}
				}

				$form.submit();
			}
		}
	}

	ko.applyBindings(new Item (_GLOBAL_SHOP_ITEM_DATA, _GLOBAL_SHOP_FIELDCONFIG, _GLOBAL_SHOP_LANG), document.getElementById('shop_item_addedit'));
});