<!-- Tag Cloud -->
{if $output.countItems>0}
<noindex>
    <div class="b-tag-cloud b-tag-cloud-widjet">
        <div class="b-plugin-title">Облако тегов</div>

        {assign var='menu' value=$handlers.main_menu.catalog_top}
        {section name=key loop=$output.items}
            <a href="{$output.items[key].url}"
               class="b-tag-cloud-link b-link"
               rel="nofollow"
               style="font-size:{$output.items[key].fontsSize}px;">
                {$output.items[key].word}
            </a>
        {/section}

    </div>
</noindex>
{/if}