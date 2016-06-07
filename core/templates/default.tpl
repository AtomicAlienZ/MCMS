<!DOCTYPE html>
<html>

<head>

	{assign var='tpl' value=$pathToIncTemplate|cat:'head.tpl'}
	{include file=$tpl}

</head>

<body>

<body class="sal-body">


<header class="sal-header">
	<div class="sal-header__inner">

		<nav class="sal-header__menu">
			<div class="sal-header__menu__item">

				<div class="sal-currency__menu">
				</div>

			</div>

			<div class="sal-header__menu__item">

				<div class="sal-language__menu">
				</div>

			</div>

			{section name=container loop=$containers.additional_1}
				{$containers.additional_1[container].output}
			{/section}


		</nav>

		<nav class="sal-mainLine">

			<a href="/" class="sal-logo"></a>

			<div class="sal-search">
				<span class="sal-search__phrase">I'm looking for</span>
				<input class="sal-search__input" type="text">
				<a href="#" class="sal-ui__button">Find it!</a>
			</div>

			<div class="sal-mainLine__controls">

				<div class="sal-ui__button sal-ui__button_light i-heart">
					<span>Whislist</span>
					<span>No items</span>
				</div>

				<div class="sal-ui__button sal-ui__button_light i-cart">
					<span>My cart</span>
					<span>1050 items</span>
				</div>

			</div>

		</nav>

		<nav class="sal-categories">

			{if $handlers.main_menu.countTop > 0}
				{assign var='menu' value=$handlers.main_menu.top}

					{section name=item loop=$menu}
						{if $structure.s_id neq $menu[item].s_id}
								<a class="sal-categories__item" href="{$menu[item].url}">{$menu[item].title}</a>
						{else}
							<div class="sal-categories__item">
								{$menu[item].title}
							</div>
						{/if}
					{/section}

			{/if}
		</nav>

	</div>
</header>

<div class="sal-content">

			{section name=container loop=$containers.main}
				{$containers.main[container].output}
			{/section}

</div>

<footer class="sal-footer">

	<div class="sal-footer__block">

		<nav class="sal-footer__menu">

			<span class="sal-footer__menu__title">For customers</span>

			<div class="sal-footer__menu__item">
				<a href="/register/" >Register</a>
			</div>

			<div class="sal-footer__menu__item">
				<a href="/feedback/" >Leave Feedback</a>
			</div>

		</nav>

		<nav class="sal-footer__menu">

			<span class="sal-footer__menu__title">About</span>

			<div class="sal-footer__menu__item">
				<a href="/about-us/" >About us</a>
			</div>

			<div class="sal-footer__menu__item">
				<a href="/privacy-policy/" >Privacy Policy</a>
			</div>

			<div class="sal-footer__menu__item">
				<a href="/news/" >News</a>
			</div>

		</nav>

	</div>

	<div class="sal-footer__block">

	</div>

	<div class="sal-credits">
		<p>© CyBy 2016</p>
		<p>Any content on this site can be used only by owners permission.</p>
	</div>

</footer>

</body>

</html>