<div class="form">
{*	<div class="label">{if $user_data.uid>0}Редактирование данных{else}Регистрация{/if}</div>*}
{*	<div class="main_ttl"><h1>{$title}</h1></div><div class="clr">&nbsp;</div>*}
	<div class="main_ttl"><h1>{if $user_data.uid>0}Редактирование регистрационных данных{else}Регистрация на сайте Проекта ФотоТур{/if}</h1></div><div class="clr">&nbsp;</div>
	{if $output.messageSend == 1}
<a name="register_rez"></a>
<div style="border: 5px solid green; padding: 5px;">
<table width="100%"><tr>
<td width="80"><img src="/img/correct_60a.png" border="0" width="60" height="60"></td>
<td>
{if $lang eq 'ru'}<div class="enrollh3"><br><h3 style="font-weight: bold; font-size:14pt;">Поздравляем, регистрация прошла успешно!<br>
На указанный Вами e-mail выслан логин и пароль для авторизации.</h3></div>
<span style="font-size:14pt; color:red; font-weight: bold">Внимание!</span> <span style="font-size:11pt;">Если Вы <b style="color:red">не получили письма</b> с регистрационными данными в течение нескольких минут, то, пожалуйста, загляните в папочку спам, возможно, наше письмо попало по ошибке туда.</span><br>
<br><span style="font-size:11pt;">Если вдруг, по каким-либо причинам, <b style="color:red">Вы не получили от нас письмо</b> с подтверждением - пожалуйста, отправьте сообщение об этом с указанием Вашего мейл адреса, с которого Вы проводили регистрацию, на мейл: <a href="mailto:il@phototour.pro">il@phototour.pro</a>
{/if}
</td>
</tr></table>
</div>
	{else}
		{if $output.messageSend == 2}
<a name="register_rez"></a>
<div style="border: 5px solid green; padding: 5px;">
<table width="100%"><tr>
<td width="80"><img src="/img/correct_60a.png" border="0" width="60" height="60"></td>
<td style="vertical-align:middle"><span  class="enrollh3" style="font-weight: bold"><h3>Изменения сохранены успешно!</h3></span></td>
</tr></table>
</div><br>
		{/if}  
		{if $output.countError > 0}
		<a name="register_rez"></a>
		<div style="border:5px solid red; padding:5px; font-size: 14pt;">
		<div><table width="100%"><tr><td width="80"><img src="/img/incorrect_60a.png" border="0" width="60" height="60"></td><td align="center" style="vertical-align:middle"><font style="color:red; font-size:32pt; text-align:center;">Внимание!</font></td></tr></table><strong>{if $lang eq 'ru'}{if !$output.values.uid}Ваша регистрация, к сожалению, отклонена. {/if}Вы не заполнили некоторые обязательные поля, либо введенные вами данные не корректны.{elseif $lang eq 'ua'}Ви не заповнили обов'язковi поля, або їх значення не коректне.{elseif $lang eq 'en'}{if !$output.values.uid}Your registration did not accepted. {/if}You do not fill all the required fields, or entered incorrect data.{/if}</strong></div>
		<br><span>{if $lang eq 'ru'}Пожалуйста, заполните или исправьте указанные поля и отправьте регистрационные данные заново.{elseif $lang eq 'en'}Please fill in the fields and send your registration data again.{/if}</span><br>
			{if $lang eq 'ru'}
			{*<br><span style="font-size:11pt;">Если вдруг - по каким-либо причинам у Вас не получается воспользоваться этой формой - пожалуйста, отправьте Вашу заявку о регстрации письмом (произвольная форма) - на мейл: <a href="mailto:il@phototour.pro">il@phototour.pro</a></span>*}
			{/if}
		</div><br>
		{/if}
		<form method="post" enctype="multipart/form-data" name="register">
			{if $output.values.uid}
				<input type="hidden" name="uid" value="{$output.values.uid}">
				<input type="hidden" name="send[uid]" value="{$output.values.uid}">
				<input type="hidden" name="send[login]" value="{$output.values.login}">
				<input type="hidden" name="send[email]" value="{$output.values.email}">
			{/if}
			<table cellpadding="0" cellspacing="0">
				<tr>
					<td class="field-name">E-mail (логин){if !$output.values.uid}<small class="req"> *</small>{/if}:</td>
					<td class="form-text">
						{if $output.values.uid>0}
							{$output.values.email}
						{else}
							<div class="fieldC"><div class="fieldL"><div class="fieldR">
								<input type="text" class="text" name="send[email]" value="{$output.values.email}">
							</div></div></div>
							<span class="req">Пожалуйста, указывайте действующий email,<br>на него прийдёт Ваш пароль.</span>
						{/if}
					</td>
					<td class="form-req">{if $output.errors.email == 1}<div class="req">Необходимо заполнить данное поле</div>{elseif $output.errors.email == 2}<div class="req">Формат поля не соответствует E-Mail адресу</div>{elseif $output.errors.email == 3}<div class="req">Такой E-Mail уже существует</div>{/if}&nbsp;</td>
				</tr>
                                {if !isset($output.messageSend) || $output.values.uid>0}
				<tr>
					<td class="field-name">Пароль{if !$output.values.uid} <small class="req"> *</small>{/if}:</td>
					<td class="form-text">
						<div class="fieldC"><div class="fieldL"><div class="fieldR">
							<input type="password" class="text" name="send[password]" value="">
						</div></div></div>
						<span class="req">Заполняйте это поле только,<br>если хотите сменить пароль.</span>
					</td>
					<td class="form-req">{if $output.errors.password == 1}<div class="req">Необходимо заполнить данное поле</div>{elseif $output.errors.password == 2}<div class="req">Неверный формат</div>{elseif $output.errors.password == 3}<div class="req">Количество символов должно быть более пяти</div>{/if}&nbsp;</td>
				</tr>
				<tr>
					<td class="field-name">Подтверждение пароля{if !$output.values.uid} <small class="req"> *</small>{/if}:</td>
					<td class="form-text">
						<div class="fieldC"><div class="fieldL"><div class="fieldR">
							<input type="password" class="text" name="send[re_password]">
						</div></div></div>
					</td>
					<td class="form-req">{if $output.errors.re_password == 1}<div class="req">Необходимо заполнить данное поле</div>{elseif $output.errors.re_password == 2}<div class="req">Пароли не совпадают</div>{/if}&nbsp;</td>
				</tr>
				{/if}
				<tr>
					<td class="field-name">Никнейм:</td>
					<td class="form-text">
						<div class="fieldC"><div class="fieldL"><div class="fieldR">
							<input type="text" class="text" name="send[last_name]" value="{$output.values.last_name}">
						</div></div></div>
					</td>
					<td class="form-req">&nbsp;</td>
				</tr>
				<tr>
					<td class="field-name">ФИО:<small class="req"> *</small>:</td>
					<td class="form-text">
						<div class="fieldC"><div class="fieldL"><div class="fieldR">
							<input type="text" class="text" name="send[name]" value="{$output.values.name}">
						</div></div></div>
					</td>
					<td class="form-req">{if $output.errors.name == 1}<div class="req">Необходимо заполнить данное поле</div>{/if}&nbsp;</td>
				</tr>
				<tr>
					<td class="field-name">Страна:</td>
					<td class="form-text">
						<div class="fieldC"><div class="fieldL"><div class="fieldR">
							<input type="text" class="text" name="send[country]" value="{$output.values.country}">
						</div></div></div>
					</td>
					<td class="form-req">&nbsp;</td>
				</tr>
				<tr>
					<td class="field-name">Город:</td>
					<td class="form-text">
						<div class="fieldC"><div class="fieldL"><div class="fieldR">
							<input type="text" class="text" name="send[city]" value="{$output.values.city}">
						</div></div></div>
					</td>
					<td class="form-req">&nbsp;</td>
				</tr>
				<tr>
					<td class="field-name">Контактный телефон:</td>
					<td class="form-text">
						<div class="fieldC"><div class="fieldL"><div class="fieldR">
							<input type="text" class="text" name="send[phone]" value="{$output.values.phone}">
						</div></div></div>
					</td>
					<td class="form-req">&nbsp;</td>
				</tr>
				<tr>
					<td class="field-name"><label for="send[subscribe]">Подписаться на новости фототуров</label></td>
					<td class="form-text">
						<input type="checkbox" name="send[subscribe]"{if $output.values.subscribe eq 1 || !isset($output.values.subscribe)} checked{/if}>
					</td>
					<td class="form-req">&nbsp;</td>
				</tr>
				<tr>
					<td class="field-name">
						<a name="protect_code"></a><a href="#protect_code" onclick="d = new Date(); document.getElementById('antispam').src = '{$output.protect_img}?r=' + d.getTime(); return false"><img src="{$output.protect_img}" id="antispam" alt="Защитный код" title="Обновить" align="absmiddle"></a>
					</td>
					<td class="form-text">
						<div class="fieldC"><div class="fieldL"><div class="fieldR">
							 <input type="Text" class="text code" name="send[code]" maxlength="4" value="">
						</div></div></div>
					</td>
					<td class="form-req">{if $output.errors.code==1}<div class="req">Необходимо заполнить данное поле</div>{/if}&nbsp;</td>
				</tr>
				<tr>
					<td class="field-name" colspan="3"><small class="req">*</small> — поля, обязательные для заполнения.</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td align="right">
						<div class="btnC"><div class="btnL"><div class="btnR">
							<a href="#" onclick="document.forms.register.submit(); return false">{if $output.values.uid>0}Сохранить{else}Зарегистрироваться{/if}</a>
						</div></div></div>
					</td>
					<td class="form-req">&nbsp;</td>
				</tr>
			</table>
			<input style="position: absolute; display: none;" type="image" src="/img/px.gif">
		</form>
	{/if}
</div>
<div class="clr">&nbsp;</div>
<script type="text/javascript">
if ({$output.messageSend} == 1 || {$output.messageSend} == 2 || {$output.countError} > 0) location.hash="#register_rez";
</script>
