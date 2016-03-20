<!DOCTYPE html>
<html>

<head>

	{assign var='tpl' value=$pathToIncTemplate|cat:'head.tpl'}
	{include file=$tpl}

</head>

<body>

{*{assign var='tpl' value=$pathToIncTemplate|cat:'block/b-header.tpl'}
{include file=$tpl}*}
<div class="mj-wrapper">

	<div class="mj-header">

		<a href="/{$lang}/" class="mj-header__logo"></a>

		{if $handlers.main_menu.countTop > 0}
			{assign var='menu' value=$handlers.main_menu.top}

			<nav class="mj-header__menu">

				{section name=item loop=$menu}
					{if $structure.s_id neq $menu[item].s_id}
						<div class="mj-header__menu__item">
							<a class="mj-link" href="{$menu[item].url}">{$menu[item].title}</a>
						</div>
					{else}
						<div class="mj-header__menu__item">
							{$menu[item].title}
						</div>
					{/if}
				{/section}

			</nav>
		{/if}

		{assign var='languages' value=$langs}

		<nav class="mj-header__menu mj-header__menu_languages">
			{section name=item loop=$languages}

				{if $languages[item] neq $lang}

					<div class="mj-header__menu__item">
						<a class="mj-link" href="/{$languages[item]}/">{$languages[item]}</a>
					</div>

				{/if}

			{/section}
		</nav>

	</div>

	<div class="mj-body">


		<div class="mj-body__content">
			{section name=container loop=$containers.main}
				{$containers.main[container].output}
			{/section}
		</div>



	</div>

	<div class="mj-footer">
		© 2015 Mjolnir LLC
	</div>

</div>

</body>

</html>