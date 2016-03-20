<div class="q-contacts">
	<div class="q-contacts__info">
		<h2 class="q-contacts__header">
			Контактная информация
		</h2>
		<p class="q-contacts__info__text">
			Мы отвечаем на звонки с 10:00 до 18:00 в рабочие дни.
		</p>

		<p class="q-contacts__info__phone">
			(068) 000 32 00
		</p>

		<p class="q-contacts__info__phone">
			(097) 444 73 44
		</p>
		<p class="q-contacts__info__text">
			Также мы с радостью прочитаем ваше письмо и ответим вам.
		</p>
		<p class="q-contacts__info__mail">
			<a href="mailto:boss@quartzetto.com.ua" class="q-contacts__info__link">boss@quartzetto.com.ua</a>
		</p>
		{*<p class="q-contacts__info__text">
			Заезжайте к нам в
			<a href="#" class="q-contacts__info__link">шоурум</a>
		</p>*}
	</div>

	<noindex>
		<div class="q-contacts__mail">
			<h2 class="q-contacts__header">
				Написать письмо с сайта
			</h2>
			<form class="q-contacts__mail__form" id="mail-form" name="form_feedback" method="post" enctype="multipart/form-data">
				<div class="q-contacts__mail__form__name">
					<label class="q-contacts__mail__form__label" for="name">Пишет вам</label>
					<input placeholder="Имя" class="q-contacts__mail__form__input" type="text" name="fio" value="{$output.send.fio}" id="name" >
				</div>
				<div class="q-contacts__mail__form__text">
					<textarea id="message" class="textarea q-contacts__mail__form__text__textarea" name="query">{$output.send.query}</textarea>
				</div>
				<div class="q-contacts__mail__form__email">
					<label class="q-contacts__mail__form__label" for="mail">Ответьте мне</label>
					<input placeholder="E-mail, телефон"  class="q-contacts__mail__form__input" type="text" name="email" value="{$output.send.email}" id="name" >
				</div>
				<div class="q-contacts__mail__form__submit">
					<button onclick="document.forms.form_feedback.submit(); class="q-contacts__mail__form__submit__button" type="submit">Отправить</button>
				</div>
			</form>
		</div>
	</noindex>
</div>

