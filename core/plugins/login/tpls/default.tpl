
{if $output.user_data.uid >0}
	<div class="login">
<noindex>
	<div class="hi">
		Добро пожаловать, <span>{$output.user_data.name}</span>
	</div>

	<div class="additional_1"><a href="{$path.0.url}registration/">Моя страница</a></div>
	<div class="enter"><a href="?logout=1">Выйти</a></div>
</noindex>
</div>
{else}
<a name="login"></a>
<div class="login">
	<noindex><form class="auth" method="post">
		<div class="inputs">
                        <div class="it" style="color: #b00; font-size: 0.85em;">{if $output.user.error neq ''}Вы ввели неправильные данные.{/if}</div>
			<div class="it"><div class="itt">Логин</div><div class="iti"><input name="form[login]" type="text" class="nobrdr"></div><div class="clr">&nbsp;</div></div>
			<div class="it"><div class="itt">Пароль</div><div class="iti"><input name="form[pass]" type="password" class="nobrdr"></div><div class="clr">&nbsp;</div></div>
			<div class="it rel">
				<div class="itch abs"><input name="form[remember]" type="checkbox"></div>
				<div class="ittc abs">Запомнить меня</div>
				<div class="enter abs"><input type="image" src="/img/login/enter_{$lang}.gif"></div>
			</div>
		</div>
	</form></noindex>
	<div class="additional">
		<div class="member"><a href="/{$lang}/registration/member/">Забыли пароль?</a></div>
		<div class="reg"><a href="/{$lang}/registration/">Зарегистрироваться</a></div>
	</div>
</div>
{/if}
