ko.bindingHandlers.dropDown = {
	init: function (element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
		var $this = $(element),
			$root = $this.parents('.js-common-dropDown'),
			$dropdown = $root.find('.js-common-dropDown__dropdown'),
			options = $.extend({}, {
				reposition: false,
				adjustWidth: false,
				addClass: 'sal-ui-select__toggle_open'
			}, valueAccessor() || {}),
			close = true;

		function hideDropdown (e) {
			if (
				!$(e.target).closest('.js-common-dropDown__toggle').length ||
				($dropdown.is(':visible') && close)
			) {
				$dropdown.hide();
				$this.removeClass(options.addClass);
			}

			close = true;
		}

		// Adjusting width
		if (options.adjustWidth) {
			setTimeout(function () {
				$dropdown.show();

				$this.css('min-width',$dropdown.children().eq(0).width() + ($this.outerWidth() - $this.width()) + 'px');

				$dropdown.hide();
			}, 1);
		}

		$this.on('click', function (e) {
			var $dropdown = $root.find('.js-common-dropDown__dropdown'),
				vpHeight = $(window).height(),
				vpOffset = $(document).scrollTop(), // positive
				rootHeight = $root.outerHeight(),
				rootOffset,// = $root.offset().top,
				dropHeight;

			e.preventDefault();

			if (!$dropdown.length) {
				console.log($root);
				return;
			}

			$this.addClass(options.addClass);

			close = $dropdown.is(':visible');

			$dropdown.css({top: '', bottom: ''}).show();

			if (options.reposition) {
				// Process positioning
				rootOffset = $root.offset().top
				dropHeight = $dropdown.outerHeight();

				if (
					rootOffset + rootHeight + dropHeight > vpHeight + vpOffset && // Drop is lower than bottom screen border
					rootOffset > dropHeight                                       // Drop won't be cut off by screen top
				) {
					$dropdown.css({bottom: '100%'});
				}
				else {
					$dropdown.css({top: '100%'});
				}
			}
		});

		$(document).on('click', hideDropdown);

		ko.utils.domNodeDisposal.addDisposeCallback(element, function() {
			$(document).off('click', hideDropdown);
		});
	}
};

ko.bindingHandlers.money = {
	init: function(element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {},
	update: function(element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
		var $moneyElement = $(element),
			money = valueAccessor();

		if (money) {
			$moneyElement
				.attr('currency', 'USD')
				.attr('amount', money)
				.text('$ ' + Math.ceil(money));
		}

		$moneyElement.trigger('cc:updated');
	}
}

var kohelpers = {
	
};