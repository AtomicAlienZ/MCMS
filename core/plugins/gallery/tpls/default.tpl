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

<!-- альбом с фотографиями -->

{if $output.countItems > 0}

    <div class="q-gallery q-content"><div class="q-content__inner">

        {*{if $output.item.title > ''}<h1 class="b-plugin-title">{$output.item.title}</h1>{/if}*}

        <div class="q-gallery-album fotorama" data-loop="true" data-width="100%" data-height="500px" data-transition="dissolve">
            {assign var='photos' value=$output.items}
            {section loop=$photos name=item}

                <div class="q-gallery-photo" style="background-image: url({$photos[item].img});">
                    {*<img src="" />*}
                    <div class="q-gallery-photo-description">{$photos[item].descr_ru}</div>
                </div>

                {*<a href="{$albums[item].url}" class="b-gallery-album-photo" style="background-image: url({$albums[item].img})">
                    <div class="b-gallery-album-photo-title">{$albums[item].title}</div>
                </a>*}

            {/section}
        </div>

    </div></div>

{/if}

<!-- !альбом с фотографиями -->