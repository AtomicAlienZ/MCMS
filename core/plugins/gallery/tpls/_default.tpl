{if $lang eq 'ru'}
	{assign var='author' value="Автор"}
	{assign var='source' value="Источник"}
	{assign var='page' value="Стр."}
	{assign var='notFound' value="Материал не найден"}
	{assign var='back' value="к списку"}

	{assign var='prev' value="Предыдущая"}
	{assign var='next' value="Следующая"}
{elseif $lang eq 'ua'}
	{assign var='author' value="Автор"}
	{assign var='source' value="Джерело"}
	{assign var='page' value="Стор."}
	{assign var='notFound' value="Матеріал не знайдений"}
	{assign var='back' value="до списку"}

	{assign var='prev' value="Предыдущая"}
	{assign var='next' value="Следующая"}
{elseif $lang eq 'en'}
	{assign var='author' value="Author"}
	{assign var='source' value="Source"}
	{assign var='page' value="Page"}
	{assign var='notFound' value="Material is not found"}
	{assign var='back' value="back to list"}

	{assign var='prev' value="Предыдущая"}
	{assign var='next' value="Следующая"}
{/if}

<!-- Photo -->

<div class="b-photo-gallery">

	{if $output.item.gallery_id>0}

		{assign var='item' value=$output.item}

	{literal}
		<script>
			$('.js-third-line__actual').addClass('js-third-line__hidden');
			$('.js-third-line__expand').removeClass('js-third-line__hidden');
			$('.b-marketing').hide();
		</script>
	{/literal}

		{if $item.type neq 0}

			{*<script type="text/javascript" src="/js/prototype_reduced.js"></script>*}
			{*<script type="text/javascript" src="/js/jquery.js"></script>*}
			{*<script type="text/javascript" src="/js/filters.js"></script>*}
			{*<script type="text/javascript" src="/js/gallery.js"></script>*}

			{if $item.img>''}

				<div class="b-photo-gallery-photo">

					<div class="b-photo-gallery-photo-title b-plugin-title">
						<h1>{$item.title}</h1>
					</div>

					<a class="b-photo-gallery-photo-image js-full-photo-expand js-tooltip" href="#full_photo" title="Просмотр на полный экран">
						<img src="{$item.img}" alt="{$item.title}" />
					</a>

					<div id="full_photo" class="b-photo-gallery-photo-full-image-wrapper js-full-photo mfp-hide">
						<div class="b-photo-gallery-photo-full-image">
							<img src="{$item.img}" alt="{$item.title}" />
							<div class="b-photo-gallery-photo-full-image-title">{$item.title}</div>
						</div>
					</div>

					<div class="b-photo-gallery-photo-nav">
						{if $output.prev_url>''}<a class="b-photo-gallery-photo-nav-left" href="{$path[1].url}{$output.prev_url}">{$prev}</a>{/if}
						<div class="b-photo-gallery-photo-nav-share">
							{literal}
								<noindex>
									<script type="text/javascript">(function() {
											if (window.pluso)if (typeof window.pluso.start == "function") return;
											if (window.ifpluso==undefined) { window.ifpluso = 1;
												var d = document, s = d.createElement('script'), g = 'getElementsByTagName';
												s.type = 'text/javascript'; s.charset='UTF-8'; s.async = true;
												s.src = ('https:' == window.location.protocol ? 'https' : 'http')  + '://share.pluso.ru/pluso-like.js';
												var h=d[g]('body')[0];
												h.appendChild(s);
											}})();</script>
									<div class="pluso" data-background="none;" data-options="small,square,line,horizontal,counter,sepcounter=1,theme=14" data-services="vkontakte,odnoklassniki,facebook,twitter,pinterest,livejournal,google"></div>
								</noindex>
							{/literal}
						</div>
						{if $output.next_url>''}<a class="b-photo-gallery-photo-nav-right" href="{$path[1].url}{$output.next_url}">{$next}</a>{/if}
					</div>

					<div class="b-photo-gallery-photo-info">

						<div class="b-photo-gallery-photo-info-title">Информация о снимке</div>

						{if $item.author>''}
							<div class="b-photo-gallery-photo-info-author">Автор: {$item.author}</div>
						{/if}

						{if $item.source>''}

							<div class="b-photo-gallery-photo-info-source">
								Источник:
								{if $item.source_url}<a href="{$item.source_url}">{/if}
									{$item.source}
									{if $item.source_url}</a>{/if}
							</div>

						{/if}

						{if $item.descr>''}
							<div class="b-photo-gallery-photo-info-description">
								{$item.descr}
							</div>
						{/if}

					</div>

				</div>

			{/if}

		{/if}

	{/if}
	<!-- !Photo -->

	<!-- Comments -->

	{if $item.type != 0}

		<div class="b-comments">
			<div class="b-plugin-title">Комментарии</div>
			<div class="b-comments-form">

				{if $output.user_data.uid > 0 }

					{if $output.error > 0}
						<div class="b-comments-form-error">
							Вы неправильно ввели код.
						</div>
					{/if}

					{assign var=send value=$output.send}

					<noindex>

						<form method="post" name="message" enctype="multipart/form-data">

							<input type="hidden" value="0" id="parent-comment" name="parent-comment" />

							<div class="b-comments-form-input">
								<textarea cols="50" rows="7" class="req" name="comment"></textarea>
							</div>

							<div class="b-comments-form-field">
								<div class="b-comments-form-field-group">
									<div class="b-comments-form-field-label"><img src="{$output.protect_img}" id="antispam" alt="Защитный код"></div>
									<div class="b-comments-form-field-input"><input type="text" class="req" name="code" value="" id="captcha"></div>
								</div>
								<div class="b-comments-form-field-send"><button class="b-button" type="submit"><div class="b-button-inner"><span class="b-button-label">Отправить</span></div></div>
							</div>

						</form>

					</noindex>

				{else}

					<div class="b-comments-form-register">
						Только зарегистрированные пользователи могут оставлять комментарии.
					</div>

				{/if}

			</div>

			{if $output.commentsCount > 0}
				<div class="b-comments-list">
					{assign var='comments' value=$output.comments}
					{assign var='stripe' value=0}

					{section loop=$comments name=item}
						<div class="b-comments-list-item">
							<div class="f-flex">
								<div class="b-comments-list-item-author">Написал {$comments[item].name}</div>
								<div class="b-comments-list-item-time">{$comments[item].time}</div>
							</div>
							<div class="b-comments-list-item-content">{$comments[item].comment}</div>

							<div class="b-comments-list-item-actions f-flex">
								{if $output.user_data.access_level > 0}
									<div class="b-comments-list-item-actions-reply" onclick="CommentReply({$comments[item].comment_id});">
										Ответить
									</div>
								{/if}

								{if $output.user_data.access_level > 50}
									<div class="b-comments-list-item-actions-delete">
										<a href="?del={$comments[item].comment_id}">Удалить</a>
									</div>
								{/if}
							</div>

							<div class="b-comments-list-item _{$comments[key].comment_id}">

							</div>

							<div class="b-comments-list-item-replies">
								{section loop=$comments name=reply}
									{if $comments[item].comment_id == $comments[reply].parent_id}
										<div class="b-comments-list-item">
											<div class="b-comments-list-item-time">{$comments[reply].time}</div>
											<div class="b-comments-list-item-author">{$comments[reply].name}</div>
											<div class="b-comments-list-item-content">{$comments[reply].comment}</div>
										</div>
										{if $output.user_data.access_level > 50}
											<div class="b-comments-list-item-delete">
												<a href="?del={$comments[reply].comment_id}">Удалить</a>
											</div>
										{/if}
									{/if}
								{/section}
							</div>

						</div>

					{/section}

				</div>
			{/if}
		</div>

	{/if}

	<!-- !Comments -->

	<!-- Galleries -->

	{if $output.countSections > 0}
		{if $output.item.parent_id neq 0}

			<!-- Subalbum -->

			<div class="b-gallery">
				{if $output.item.title > ''}<h1 class="b-plugin-title">{$output.item.title}</h1>{/if}

				<div class="b-gallery-subalbums">

					{assign var='albums' value=$output.sections}
					{section loop=$albums name=item}

						<div class="b-gallery-subalbums-album">
							<a href="{$albums[item].url}"><img class="b-gallery-subalbums-album-image" src="{$albums[item].img_sm}" /></a>
							<a href="{$albums[item].url}" class="b-gallery-subalbums-album-title">{$albums[item].title}</a>
							<div class="b-gallery-subalbums-album-description">{$albums[item].descr}</div>
						</div>

					{/section}

				</div>

			</div>

		{else}

			<!-- Albums -->

			<div class="b-gallery">
				{if $output.item.title > ''}<h1 class="b-plugin-title">{$output.item.title}</h1>{/if}

				<div class="b-gallery-albums">
					{assign var='albums' value=$output.sections}
					{section loop=$albums name=item}

						<a href="{$albums[item].url}" class="b-gallery-albums-album" style="background-image: url({$albums[item].img})">
							<div class="b-gallery-albums-album-title">{$albums[item].title}</div>
						</a>

					{/section}
				</div>

			</div>

		{/if}
	{/if}

	<!-- !галереи -->

	<!-- альбом с фотографиями -->

	{if $output.countItems > 0}

		<div class="b-gallery">
			{if $output.item.title > ''}<h1 class="b-plugin-title">{$output.item.title}</h1>{/if}

			<div class="b-gallery-album">
				{assign var='albums' value=$output.items}
				{section loop=$albums name=item}

					<a href="{$albums[item].url}" class="b-gallery-album-photo" style="background-image: url({$albums[item].img})">
						<div class="b-gallery-album-photo-title">{$albums[item].title}</div>
					</a>

				{/section}
			</div>

		</div>

	{/if}

	<!-- !альбом с фотографиями -->

	<!-- постраничка -->

	{*Paginator*}

	{* If there's more than 1 page and navigation is on *}
	{if $output.navi.pages_total > 1}

		{* Paginator begins on the 1st page... *}
		{if $output.navi.page < 6}
			{assign var=start value=0}
		{else}
			{* or current page - 5 *}
			{assign var=start value=$output.navi.page-5}
		{/if}

		{* Here we set the paginator with the number of pages forth *}
		{if $output.navi.pages_total - $output.navi.page >= 5}
			{assign var=max value=5}
		{else}
			{* or set it forth to the last page *}
			{assign var=max value=$output.navi.pages_total}
		{/if}

		{* Links to next and previous pages*}
		{assign var=prev value=$output.navi.page-1}
		{assign var=next value=$output.navi.page+1}

		<div class="b-paginator">

			{assign var='pages' value=$output.navi.pages}
			{section name=page start=$start loop=$pages max=$max}
				{if $pages[page].title neq $output.navi.page}
					{* Link *}
					<a class="b-paginator-item f-ib b-paginator-item__active" href="{$pages[page].url}">
						{$pages[page].title}
					</a>
				{else}
					{* Current Page *}
					<span class="b-paginator-item f-ib b-paginator-item__current">
                        {$pages[page].title}
                    </span>
				{/if}
			{/section}

		</div>
	{/if}


	<!-- !постраничка -->

</div>