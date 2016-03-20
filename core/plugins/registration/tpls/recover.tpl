<div class="form">
	<div class="main_ttl"><h1>Восстановление пароля</h1></div><div class="clr">&nbsp;</div>
	{if $output.error == -1}
		<p>На указанный Вами e-mail выслан логин и пароль для авторизации.</p>
	{else}
		{if $output.error == 1}
			<div class="error">Заполните поле E-mail</div>
		{/if}
		{if $output.error == 2}
			<div class="error">Поле E-mail заполнено неверно</div>
		{/if}
		{if $output.error == 3}
			<div class="error">Нет пользователя с таким E-mail</div>
		{/if}
		{if $output.error == 4}
			<div class="error">Вы неверно ввели капчу</div>
		{/if}
		<form method="post" enctype="multipart/form-data" name="recover">
			<table cellpadding="0" cellspacing="0">
				<tr>
					<td class="field-name">Ваш E-mail:</td>
					<td class="form-text">
						<div class="fieldC"><div class="fieldL"><div class="fieldR">
							<input type="text" class="text" name="send[email]">
						</div></div></div>
					</td>
				</tr>
				<tr>
					<td class="field-name">
						<a name="protect_code"></a><a href="#protect_code" onclick="d = new Date(); document.getElementById('antispam').src = '{$output.protect_img}?r=' + d.getTime(); return false"><img src="{$output.protect_img}" id="antispam" alt="Защитный код" title="Обновить" align="absmiddle"></a>
					</td>
					<td class="form-text">
						<div class="fieldC"><div class="fieldL"><div class="fieldR">
							<input type="Text"  class="text code" name="send[code]" maxlength="4" value="">
						</div></div></div>
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
						<div class="btnC"><div class="btnL"><div class="btnR">
							<a href="#" onclick="document.forms.recover.submit(); return false">Напомнить</a>
						</div></div></div>
					</td>
				</tr>
			</table>
		</form>
	{/if}
</div>
