{if $output.items}

    {if $output.type neq 'item'}

		<h1 class="b-plugin-title">{$output.title}</h1>

		<div class="b-filters b-common-cutin">
			<p class="b-filters-disclaimer">Ниже вы можете выбрать те особенности тура, которые вам нужны. Список подходящих туров будет мгновенно показываться на экране.</p>

			{foreach from=$output.for_filters item=filter}
                {if ($filter.type eq 'multiple') or ($filter.type eq 'select')}
                    <div class="b-filters-filter">
                        <div class="b-filters-filter-label">{$filter.field}</div>
                        <div class="b-filters-filter-values">
                            {foreach from=$filter.value item=value}
                                <label class="b-filters-filter-values-value" data-filter="{$value}"><input
                                            type="checkbox">{$value}</label>
                            {/foreach}
                        </div>
                    </div>
                {/if}
            {/foreach}
		</div>


		<div class="b-store">
			<p class="b-store-disclaimer">К сожалению, не найдено ни одного тура по вашему запросу. Попробуйте
			<span class="b-store-clear b-button"><span class="b-button-inner"><span class="b-button-label">обнулить</span></span></span> фильтр.</p>

			<div class="b-store-tours">

                {foreach from=$output.items item=tour key=k}
                    {if $tour.state neq 1}
                        <div class="b-store-tour"
                             data-filter="{foreach from=$tour.additional item=filter}{foreach from=$filter.value item=value}{$value},{/foreach}{/foreach}">

                            <div class="b-store-tour-image"><a href="{$tour.url}"><img src="{$tour.img}" title="{$tour.title}"/></a>
                            </div>

                            <h3 class="b-store-tour-title"><a href="{$tour.url}">{$tour.title}</a></h3>

                            {*<div class="b-store-tour-info">
                                <div class="b-store-tour-duration"><span>Тур длится:</span><span class="b-store-tour-duration-quantity">{$tour.duration}</span></div><br />
                                <div class="b-store-tour-duration"><span>Тур проходит:</span><span class="b-store-tour-duration-quantity">{$tour.season}</span></div><br />
                                <div class="b-store-tour-price"><span>Цена:</span><span><i class="b-dollar">$</i>{$tour.price|string_format:"%d"}</span></div>
                            </div>*}

                            <div>
                            {foreach from=$tour.additional item=add}
                                {if $add.value neq ''}
                                    <div class="b-store-tour-additional">
                                        <span class="b-store-tour-additional-field">{$add.field}:</span>

                                        <span class="b-store-tour-additional-value">
                                            {foreach from=$add.value item=value name=name}
                                                {$value}{if $smarty.foreach.name.last}{else},{/if}
                                            {/foreach}
                                        </span>
                                    </div>
                                {/if}
                            {/foreach}
                            </div>

                            <div class="b-store-tour-description mfp-hide b-common-popup" id="js-more-{$k}">
                                <div class="b-store-tour-description">{$tour.short_description}</div>
                                {*<a href="#" class="show-more">Краткое описание</a>*}
                            </div>

                            <div class="b-store-tour-buy">
                                <a class="b-button" href="{$tour.url}">
                                    <div class="b-button-inner">
                                        <div class="b-button-label">Забронировать</div>
                                    </div>
                                </a>
                                <a class="b-more js-store-more" href="#js-more-{$k}">Подробнее о туре</a>
                            </div>

                        </div>
                    {/if}
                {/foreach}

            </div>
        </div>

    {else}

        {* Show 1 tour *}

        {literal}
            <script src="/ckeditor/ckeditor.js"></script>
        {/literal}

        <div class="b-store">
            {foreach from=$output.items item=tour}
                {if $tour.state neq 1}

                    <h1 class="b-plugin-title">«{$tour.title}»</h1>

                    <div class="b-store-tourpage">

                        <div class="b-store-tourpage-image">
                            <img src="{$tour.img}" title="Фототур «{$tour.title}»" />
                        </div>



                        <div class="b-store-tourpage-additional">
                            {foreach from=$tour.additional item=add}
                                {if $add.value neq ''}
                                    <div class="b-store-tour-additional">
                                        <div class="b-store-tour-additional-field">{$add.field}:</div>
                                        <div class="b-store-tour-additional-value">
                                            {foreach from=$add.value item=value name=name}
                                                {$value}{if $smarty.foreach.name.last}{else},{/if}
                                            {/foreach}
                                        </div>
                                    </div>
                                {/if}
                            {/foreach}
                        </div>

                        <div class="b-store-down">
                            <a href="#mail-form-res" class=""><button type="submit" class="b-button b-button__buy"><div class="b-button-inner"><span class="b-button-label">Забронировать</span></div></button></a>
                        </div>

						<!-- pluso.ru buttons -->

						{literal}
							<script type="text/javascript">(function(w,doc) {
									if (!w.__utlWdgt ) {
										w.__utlWdgt = true;
										var d = doc, s = d.createElement('script'), g = 'getElementsByTagName';
										s.type = 'text/javascript'; s.charset='UTF-8'; s.async = true;
										s.src = ('https:' == w.location.protocol ? 'https' : 'http')  + '://w.uptolike.com/widgets/v1/uptolike.js';
										var h=d[g]('body')[0];
										h.appendChild(s);
									}})(window,document);
							</script>

							<noindex>
								<div class="b-share-left">
									Поделиться туром:
									<div data-background-alpha="0.0" data-buttons-color="#ffffff" data-counter-background-color="#ffffff" data-share-counter-size="14" data-top-button="false" data-share-counter-type="common" data-share-style="13" data-mode="share" data-like-text-enable="false" data-mobile-view="true" data-icon-color="#ffffff" data-orientation="horizontal" data-text-color="#000000" data-share-shape="round-rectangle" data-sn-ids="fb.vk.tw.ok.gp." data-share-size="40" data-background-color="#ffffff" data-preview-mobile="false" data-mobile-sn-ids="fb.vk.tw.wh.ok.gp.ps.lj.gt." data-pid="1404185" data-counter-background-alpha="1.0" data-following-enable="false" data-exclude-show-more="false" data-selection-enable="true" class="uptolike-buttons" ></div>
								</div>
							</noindex>
						{/literal}

						<!-- !pluso.ru buttons -->

                        <div class="b-store-tourpage-details">
                            {$output.items[0].description}
                        </div>

                        <h2 id="mail-form-res" class="b-plugin-title">Форма бронирования</h2>

                        <div class="b-store-tourpage-form">
                            <noindex>
                                <form action="/enroll/" name="form_feedback" method="post" enctype="multipart/form-data">

                                    <input type="hidden" name="shop" value="1">
                                    <input type="hidden" name="shop_tour_id" value="{$tour.id}">

                                    <div class="b-common-form">


                                        <div class="b-common-form-block">
                                            <div class="b-common-form-field b-common-form-field{if $output.errors.email neq ''}b-common-form-field__error{/if}">

                                                <div class="b-common-form-field-label">Ваше имя</div>
                                                <div class="b-common-form-field-input">
                                                    <input class="text" type="text" name="fio" value="{$output.send.fio}" >
                                                </div>
                                                <div class="b-common-form-field-error">
                                                    {if $output.errors.fio == 1}Необходимо заполнить данное поле
                                                    {elseif $output.errors.fio == 2}Кажется, это не e-mail
                                                    {elseif $output.errors.fio == 3}Такое e-mail уже зарегистрирован
                                                    {/if}
                                                </div>

                                            </div>

                                            <div class="b-common-form-field b-common-form-field{if $output.errors.email neq ''}b-common-form-field__error{/if}">

                                                <div class="b-common-form-field-label">Ваш e-mail</div>
                                                <div class="b-common-form-field-input">
                                                    <input class="text" type="text" name="email" value="{$output.send.email}" >
                                                </div>
                                                <div class="b-common-form-field-error">
                                                    {if $output.errors.email == 1}Необходимо заполнить данное поле
                                                    {elseif $output.errors.email == 2}Кажется, это не e-mail
                                                    {elseif $output.errors.email == 3}Такое e-mail уже зарегистрирован
                                                    {/if}
                                                </div>

                                            </div>

                                            <div class="b-common-form-field b-common-form-field{if $output.errors.country neq ''}b-common-form-field__error{/if}">

                                                <div class="b-common-form-field-label">Страна</div>
                                                <div class="b-common-form-field-input">
                                                    <input class="text" type="text" name="country" value="{$output.send.country}" >
                                                </div>
                                                <div class="b-common-form-field-error">
                                                    {if $output.errors.country == 1}Необходимо заполнить данное поле
                                                    {elseif $output.errors.country == 2}Кажется, это не e-mail
                                                    {elseif $output.errors.country == 3}Такое e-mail уже зарегистрирован
                                                    {/if}
                                                </div>

                                            </div>

                                            <div class="b-common-form-field b-common-form-field{if $output.errors.city neq ''}b-common-form-field__error{/if}">

                                                <div class="b-common-form-field-label">Город</div>
                                                <div class="b-common-form-field-input">
                                                    <input class="text" type="text" name="city" value="{$output.send.city}" >
                                                </div>
                                                <div class="b-common-form-field-error">
                                                    {if $output.errors.city == 1}Необходимо заполнить данное поле
                                                    {elseif $output.errors.city == 2}Кажется, это не e-mail
                                                    {elseif $output.errors.city == 3}Такое e-mail уже зарегистрирован
                                                    {/if}
                                                </div>

                                            </div>
                                        </div>


                                        <div class="b-common-form-block">
                                            <div class="b-common-form-field b-common-form-field{if $output.errors.phone1 neq ''}b-common-form-field__error{/if}">

                                                <div class="b-common-form-field-label">Телефон</div>
                                                <div class="b-common-form-field-input">
                                                    <input class="text" type="text" name="phone1" value="{$output.send.phone1}" >
                                                </div>
                                                <div class="b-common-form-field-error">
                                                    {if $output.errors.phone1 == 1}Необходимо заполнить данное поле
                                                    {elseif $output.errors.phone1 == 2}Кажется, это не e-mail
                                                    {elseif $output.errors.phone1 == 3}Такое e-mail уже зарегистрирован
                                                    {/if}
                                                </div>

                                            </div>

                                            <div class="b-common-form-field b-common-form-field{if $output.errors.when1 neq ''}b-common-form-field__error{/if}">

                                                <div class="b-common-form-field-label">Когда лучше звонить</div>
                                                <div class="b-common-form-field-input">
                                                    <input class="text" type="text" name="when1" value="{$output.send.when1}" >
                                                </div>
                                                <div class="b-common-form-field-error">
                                                    {if $output.errors.when1 == 1}Необходимо заполнить данное поле
                                                    {elseif $output.errors.when1 == 2}Кажется, это не e-mail
                                                    {elseif $output.errors.when1 == 3}Такое e-mail уже зарегистрирован
                                                    {/if}
                                                </div>

                                            </div>

                                            <div class="b-common-form-field b-common-form-field{if $output.errors.skype neq ''}b-common-form-field__error{/if}">

                                                <div class="b-common-form-field-label">Skype</div>
                                                <div class="b-common-form-field-input">
                                                    <input class="text" type="text" name="skype" value="{$output.send.skype}" >
                                                </div>
                                                <div class="b-common-form-field-error">
                                                    {if $output.errors.skype == 1}Необходимо заполнить данное поле
                                                    {elseif $output.errors.skype == 2}Кажется, это не e-mail
                                                    {elseif $output.errors.skype == 3}Такое e-mail уже зарегистрирован
                                                    {/if}
                                                </div>

                                            </div>

                                            <div class="b-common-form-field b-common-form-field{if $output.errors.pay neq ''}b-common-form-field__error{/if}">

                                                <div class="b-common-form-field-label">Способ предоплаты</div>
                                                <div class="b-common-form-field-input">
                                                    <input class="text" type="text" name="pay" value="{$output.send.pay}" >
                                                </div>
                                                <div class="b-common-form-field-error">
                                                    {if $output.errors.pay == 1}Необходимо заполнить данное поле
                                                    {elseif $output.errors.pay == 2}Кажется, это не e-mail
                                                    {elseif $output.errors.pay == 3}Такое e-mail уже зарегистрирован
                                                    {/if}
                                                </div>

                                            </div>

                                        </div>

                                        <div class="b-common-form-field b-common-form-field__vertical b-common-form-field{if $output.errors.query neq ''}b-common-form-field__error{/if}">

                                            <div class="b-common-form-field-label">Сообщение (дополнительные пожелания, вопросы)</div>
                                            <div class="b-common-form-field-input">
                                                <textarea id="message" class="textarea textarea__wide" name="query"></textarea>
                                            </div>
                                            <div class="b-common-form-field-error">
                                                {if $output.errors.pay == 1}Необходимо заполнить данное поле
                                                {elseif $output.errors.pay == 2}Кажется, это не e-mail
                                                {elseif $output.errors.pay == 3}Такое e-mail уже зарегистрирован
                                                {/if}
                                            </div>

                                        </div>

                                        <div class="b-common-form-block">

                                            <div class="b-common-form-field b-common-form-field">

                                                <div class="b-common-form-field-label">
                                                    <img onclick="d = new Date(); document.getElementById('antispam').src = '{$output.protect_img}?r=' + d.getTime(); return false" src="{$output.protect_img}" id="antispam" alt="Защитный код" title="Обновить"/>
                                                </div>

                                                <div class="b-common-form-field-input">
                                                    <input class="text code" type="text" name="code" maxlength="4" value="">
                                                </div>

                                                <div class="b-common-form-field-error">
                                                    {if $output.errors.code == 1}Необходимо заполнить данное поле
                                                    {elseif $output.errors.code == 2}Неправильный код
                                                    {/if}
                                                </div>
                                            </div>

                                        </div>

                                        <div class="b-common-form-block">

                                            <div class="b-common-form-submit">
                                                <div href="#" class="b-button b-button__buy" onclick="document.forms.form_feedback.submit(); return false;"><div class="b-button-inner"><span class="b-button-label">Забронировать</span></div></div>
                                            </div>

                                        </div>

                                    </div>

                                </form>
                            </noindex>
                        </div>



                    </div>

                {/if}
            {/foreach}
        </div>

	{/if}

{/if}



{* catalogue *}

{if $output.items}


	{* catalogue folders *}
	{if $output.MainCatalogPage}
		<h1 class="b-plugin-title">{$output.title}</h1>
	{/if}

	{* catalogue items *}
	{if $output.type eq 'items'}

	{/if}

	{* catalogue item *}
	{if $output.type eq 'item'}

	{/if}

{/if}