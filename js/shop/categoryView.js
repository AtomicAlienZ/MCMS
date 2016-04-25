function extendModel (what, withWhatArray) {
	for (var i = 0; i < withWhatArray.length; i++) {
		for (var j in withWhatArray[i].prototype) {
			what.prototype[j] = withWhatArray[i].prototype[j];
		}
	}

	what.prototype.constructor = what;
}

ko.bindingHandlers.PFNumberSlider = {
	init: function(element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
		var $element = $(element),
			values = viewModel.values(),
			value = viewModel.value(),
			type = viewModel.type == 'range' || viewModel.type;

		$element.addClass('nemo-ui-slider_' + viewModel.type).slider({
			range: type,
			min: values.min,
			max: values.max,
			values: viewModel.type == 'range' ? [ value.min, value.max ] : (viewModel.type == 'min' ? value.max : value.min),
			slide: function( event, ui ) {
				console.log(viewModel);
				if (viewModel.type == 'range') {
					viewModel.displayValues.min(ui.values[0]);
					viewModel.displayValues.max(ui.values[1]);
				}
				else if (viewModel.type == 'min') {
					viewModel.displayValues.max(ui.value);
				}
				else {
					viewModel.displayValues.min(ui.value);
				}
			},
			change: function( event, ui ) {
				if (event.originalEvent) {
					switch (viewModel.type) {
						case 'range':
							valueAccessor()({
								min: ui.values[0],
								max: ui.values[1]
							});
							break;
						case 'min':
							valueAccessor()({
								min: viewModel.values().min,
								max: ui.value
							});
							break;
						case 'max':
							valueAccessor()({
								min: ui.value,
								max: viewModel.values().max
							});
							break;
					}
				}
			}
		});

		// Do not forget to add destroy callbacks
		ko.utils.domNodeDisposal.addDisposeCallback(element, function() {
			try {
				$element.slider('destroy');
			}
			catch (e) {}
		});
	},

	update: function(element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
		var value = viewModel.value(),
			values = viewModel.values();

		if (value.min != value.max) {
			$(element).slider(
				viewModel.type == 'range' ? 'values' : 'value',
				viewModel.type == 'range' ? [ value.min, value.max ] : (viewModel.type == 'min' ? value.max : value.min)
			);
		}
	}
};

$(function () {
	function Item (data) {
		for (var i in data) {
			if (data.hasOwnProperty(i)) {
				this[i] = data[i];
			}
		}

		this.date = new CommonDate(this.date);

		// Processing fields
		this.fieldsById = {};

		if (this.fields instanceof Array) {
			for (var i = 0; i < this.fields.length; i++) {
				this.fieldsById[this.fields[i].id] = this.fields[i];
			}
		}


		this.isFiltered = false;
	}

	var ViewModel = {
		items: [],
		itemsById: {},
		visibleItems: [],
		pageContent: ko.observableArray(),
		filters: [],
		page: ko.observable(1),
		pages: ko.observable(1),
		perPage: 20,
		buildPage: function (page) {
			console.log('buildPage:', page);
			this.visibleItems = [];
			for (var i = 0; i < this.items.length; i++) {
				if (!this.items[i].isFiltered) {
					this.visibleItems.push(this.items[i]);
				}
			}

			this.pages(Math.ceil(this.visibleItems.length / this.perPage));
			page = Math.max(1, Math.min(parseInt(page), this.pages()));
			this.page(page);
			this.pageContent(this.visibleItems.slice(
				(this.page() - 1) * this.perPage,
				this.page() * this.perPage
			));
		},
		buildFilters: function () {
			var self = this;

			for (var i = 0; i < this.items.length; i++) {
				var item = this.items[i];

				this.itemsById[item.id] = item;
			}

			var tmp = new PostFilterNumber({
				config: {
					name: 'PRICE',
					type: 'Number',
					getter: function (obj) {
						return Math.ceil(parseFloat(obj.price));
					},
					options: {
						onInit: function (initParams) {
							this.displayValues = {};
							this.displayValues.min = ko.observable();
							this.displayValues.max = ko.observable();
						},
						onValuesUpdate: function (newValue) {
							this.displayValues.min(newValue.min);
							this.displayValues.max(newValue.max);
						}
					}
				},
				items: this.itemsById,
				onChange: function () {
					self.PFChanged.apply(self, arguments);
				}
			});

			if (tmp.isActive()) {
				this.filters.push(tmp);
			}

			// Constructing PFs configs
			for (var i in _GLOBAL_SHOP_FIELDSET) {
				if (_GLOBAL_SHOP_FIELDSET.hasOwnProperty(i)) {
					var field = _GLOBAL_SHOP_FIELDSET[i];
					//console.log(_GLOBAL_SHOP_FIELDSET[i]);
					if (field.enabled && (field.type == 'select' || field.type == 'multiselect' || field.type == 'int' || field.type == 'float')) {
						var tmp = {
								name: field.names[_GLOBAL_LANG],
								type: field.type == 'select' || field.type == 'multiselect' ? 'String' : 'Number',
								options: {
									// Filter-specific options here
								}
							},
							pfc;

						if (field.type == 'select' || field.type == 'multiselect') {
							tmp.getter = function (field) {
								return function (obj) {
									var ret = [],
										fieldValue = obj.fieldsById[field.id].value;

									if (typeof fieldValue == 'object' && fieldValue !== null) {
										for (var i in fieldValue) {
											if (fieldValue.hasOwnProperty(i)) {
												ret.push([i, fieldValue[i]]);
											}
										}
									}

									return ret;//[[tmp, tmp]];
								}
							} (field);

							tmp.valuesSorter = function (a, b) {
								return a.value.localeCompare(b.value);
							};
						}
						else {
							tmp.getter = function (field) {
								return function (obj) {
									return Math.ceil(parseFloat(obj.fieldsById[field.id].value));
								}
							} (field);

							tmp.options.onInit = function (initParams) {
								this.displayValues = {};
								this.displayValues.min = ko.observable();
								this.displayValues.max = ko.observable();
							};

							tmp.options.onValuesUpdate = function (newValue) {
								this.displayValues.min(newValue.min);
								this.displayValues.max(newValue.max);
							};
						}

						switch (tmp.type) {
							case 'String':
								pfc = PostFilterString;
								break;
							case 'Number':
								pfc = PostFilterNumber;
								break;
						}

						tmp = new pfc({
							config: tmp,
							items: this.itemsById,
							onChange: function () {
								self.PFChanged.apply(self, arguments);
							}
						});

						if (tmp.isActive()) {
							this.filters.push(tmp);
						}
					}
				}
			}

		},
		PFChanged: function (filter) {
			//console.log('CHANGED', arguments);
			var filterResults = {},
				result,
				tmp;

			function intersectFilterResults(filterResults, skipIndex) {
				var result;

				for (var i in filterResults) {
					if (filterResults.hasOwnProperty(i) && (typeof skipIndex == 'undefined' || i != skipIndex)) {
						if (typeof result == 'undefined') {
							result = filterResults[i].slice(0);
						}
						else {
							result = result.filter(function (elt) {
								return filterResults[i].indexOf(elt) != -1
							});
						}
					}
				}

				return result;
			}

			for (i = 0; i < this.filters.length; i++) {
				if (this.filters[i].hasValue()) {
					var t = [];

					for (j = 0; j < this.items.length; j++) {
						if (this.filters[i].filter(this.items[j])) {
							t.push(this.items[j].id);
						}
					}

					filterResults[i] = t;
				}
			}

			// Intersecting filter results
			result = intersectFilterResults(filterResults);

			console.log(result);

			for (var i = 0; i < this.items.length; i++) {
				this.items[i].isFiltered = !(typeof result == 'undefined' || result.indexOf(this.items[i].id) >= 0);
			}

			this.buildPage(1);
		},
		sortTypes: ['price_asc', 'price_desc', 'times_ordered', 'newness'],
		sort: ko.observable('price_asc'),
		doSort: function (type) {
			console.log('doSort:', type);

			var sortfunc;

			switch (type) {
				case 'price_asc':
				case 'price_desc':
					sortfunc = function (a, b) {
						return (type == 'price_desc' ? -1 : 1)*(a.price - b.price);
					};
					break;
				case 'times_ordered':
					sortfunc = function (a, b) {
						var ret = a.times_ordered - b.times_ordered;

						if (ret == 0) {
							ret = a.price - b.price
						}

						return ret;
					};
					break;
				case 'newness':
					sortfunc = function (a, b) {
						var ret = a.date.getTimestamp() - b.date.getTimestamp();

						if (ret == 0) {
							ret = a.price - b.price
						}

						return ret;
					};
					break;
			}

			if (typeof sortfunc == 'function') {
				this.items.sort(sortfunc);
			}
			else {
				console.log('NO SORT FUNCTION');
			}

			//for(var i = 0; i < this.items.length; i++) {
			//	console.log(this.items[i].price);
			//}
		}
	};

	for (var i = 0; i < _GLOBAL_SHOP_ITEMS.length; i++) {
		ViewModel.items.push(new Item(_GLOBAL_SHOP_ITEMS[i]));
	}

	ViewModel.buildFilters();
	ViewModel.doSort(ViewModel.sort());
	ViewModel.buildPage(ViewModel.page());

	ko.applyBindings(ViewModel, document.getElementById('shop_category_view'));
});