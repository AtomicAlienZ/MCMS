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

<div class="sal-catView" id="shop_category_view">
	<aside class="sal-catView__aside">
		{assign var=parentCats value=$output.category->getParentsArray()}
		{assign var=childCats value=$output.category->getChildren()}
		{if count($parentCats) == 0}
			<h1 class="sal-catView__aside__title">{$output.category->getName($lang)}</h1>
		{else}
			<h1 class="sal-catView__aside__title">{$parentCats[0]->getName($lang)}</h1>
			<h2 class="sal-catView__aside__subTitle">{$output.category->getName($lang)}</h2>
		{/if}

		{if count($childCats) != 0}
			<div class="sal-catView__aside__subCategories">
				<div class="sal-catView__aside__subCategories__title">By category</div>
				{foreach from=$childCats item=item}
					<a class="sal-catView__aside__subCategories__item" href="{$output._baseURL}?id={$item->getId()}">{$item->getName($lang)}</a>
				{/foreach}
			</div>
		{/if}

		{*SORT: <!-- ko text: sort --><!-- /ko -->*}
		{*<div data-bind="foreach: sortTypes">*}
			{*<span data-bind="text: $data, click: function () { $parent.sort($data); $parent.doSort($parent.sort()); $parent.buildPage(1); }"></span>*}
		{*</div>*}
		{*<hr>*}

		<!-- ko if: filters.length -->
		<div class="sal-catView__aside__filters" data-bind="foreach: filters">
			<div class="sal-catView__aside__filter">
				<div data-bind="text: config.name" class="sal-catView__aside__filter__name"></div>
				<!-- ko if: config.type == 'Number' -->
					<!-- ko if: config.isPrice -->
						<div class="sal-catView__aside__filter__numberValues">
							<money data-bind="money: displayValues.min()"></money>
							&mdash;
							<money data-bind="money: displayValues.max()"></money>
						</div>
					<!-- /ko -->
					<!-- ko if: !config.isPrice -->
						<div class="sal-catView__aside__filter__numberValues" data-bind="html: displayValues.min()+' &mdash; '+displayValues.max()"></div>
					<!-- /ko -->
					<div data-bind="PFNumberSlider: value" class="sal-catView__aside__filter__numberSlider"></div>
				<!-- /ko -->

				<!-- ko if: config.type == 'String' -->
					<!-- ko foreach: values -->
					<div class="sal-catView__aside__filter__stringValue"
					       data-bind="css: {
			                {*'sal-catView__aside__filter__stringValue_disabled' : count() == 0,*}
			                'sal-catView__aside__filter__stringValue_checked' : $parent.value() && $parent.value().indexOf(key) >= 0
			            },
			            text: value,
			            click: function () { $parent.selectValue(key); }"></div>
					<!-- /ko -->
				<!-- /ko -->
			</div>
		</div>
		<!-- /ko -->

	</aside>
	{strip}
	<div class="sal-catView__items" data-bind="foreach: pageContent">
		<a class="sal-catView__item" href="#" data-bind="attr: { href: '{$output._baseURL}?action=item&id=' + id }">
			<div class="sal-catView__item__image" data-bind="attr: { style: 'background-image: url(\''+imageURL+'\');' }"></div>
			<div class="sal-catView__item__title" data-bind="text: name"></div>
			<money class="sal-catView__item__price" data-bind="money: price"></money>
			{*<!-- ko foreach: fields -->*}
			{*<!-- ko if: enabled -->*}
					{*<span style="display: inline-block;vertical-align: top;margin-right: 20px;">*}
						{*<b data-bind="text: name"></b>*}
						{*<!-- ko if: typeof value != 'object' -->*}
						{*<!-- ko text: value --><!-- /ko -->*}
						{*<!-- /ko -->*}

						{*<!-- ko if: typeof value == 'object' -->*}
						{*<!-- ko foreach: Object.keys(value) -->*}
						{*<!-- ko text: $parent.value[$data] --><!-- /ko -->,*}
						{*<!-- /ko -->*}
						{*<!-- /ko -->*}
					{*</span>*}
			{*<!-- /ko -->*}
			{*<!-- /ko -->*}
		</a>
	</div>

	<!-- ko if: pages() > 1 -->
	<div class="sal-catView__pagination" data-bind="foreach: new Array(pages())">
		{*PAGE: <!-- ko text: page --><!-- /ko -->*}
		<span class="sal-catView__pagination__page"
		      data-bind="
		        text: $index() + 1,
		        css: {
		            'sal-catView__pagination__page_active': $index() + 1 == $parent.page()
		         },
		        click: function () { $parent.buildPage($index() + 1); $('html,body').stop().animate({ scrollTop: 0 }, 500); }"></span>
	</div>
	<!-- /ko -->
	{/strip}
</div>
