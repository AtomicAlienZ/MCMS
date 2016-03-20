<!-- Tag Cloud -->

{if $output.countItems>0}
    <noindex>
        <div class="b-tag-cloud b-tag-cloud-wide">
            {*<div class="b-plugin-title">Облако тегов</div>*}

            {assign var='menu' value=$handlers.main_menu.catalog_top}
            {section name=key loop=$output.items}
                <a href="{$output.items[key].url}"
                   class="b-tag-cloud-link b-link b-link__alter f-ib"
                   rel="nofollow"
                   style="font-size:{math equation="x * y" x=$output.items[key].fontsSize y=1.5}px;">
                    {$output.items[key].word}
                </a>
            {/section}

        </div>
    </noindex>
{/if}