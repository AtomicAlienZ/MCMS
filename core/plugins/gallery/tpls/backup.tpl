{if $lang eq 'ru'}
    {assign var='author' value="�����"}
    {assign var='source' value="��������"}
    {assign var='page' value="���."}
    {assign var='notFound' value="�������� �� ������"}
    {assign var='back' value="� ������"}

    {assign var='prev' value="����������"}
    {assign var='next' value="���������"}
{elseif $lang eq 'ua'}
    {assign var='author' value="�����"}
    {assign var='source' value="�������"}
    {assign var='page' value="����."}
    {assign var='notFound' value="������� �� ���������"}
    {assign var='back' value="�� ������"}

    {assign var='prev' value="����������"}
    {assign var='next' value="���������"}
{elseif $lang eq 'en'}
    {assign var='author' value="Author"}
    {assign var='source' value="Source"}
    {assign var='page' value="Page"}
    {assign var='notFound' value="Material is not found"}
    {assign var='back' value="back to list"}

    {assign var='prev' value="����������"}
    {assign var='next' value="���������"}
{/if}

<!-- ���������� -->

{if $output.item.gallery_id>0}
    {assign var='item' value=$output.item}
    {if $item.type neq 0}

        <script type="text/javascript" src="/js/prototype_reduced.js"></script>
        <script type="text/javascript" src="/js/filters.js"></script>
        <script type="text/javascript" src="/js/gallery.js"></script>

        {if $item.img>''}
            <div class="photo-not-full">
            <div class="photo-main-bg">
                <div id="photo-box-inner">
                    <h1 class="gal-title">{$item.title}</h3>
                        <link rel="stylesheet" type="text/css" href="/css/marker.css" />

                        <div class="centrer">
                            <div id="filterDiv" style="z-index: 0; position: absolute; background-color: #000000; left: 0px; top: 0px; width: 1px; height: 1px; display: block; opacity: 0.5"></div>
                            <div id="preview"></div>
                            <div id="mainpicDiv">
                                <div class="color"></div>
                                <img id="mainpic" style="z-index: 1;" src="{$item.img_sh}" />
                                <img id="mainpic-off" style="z-index: 1;" src="{$item.img}" />
                            </div>
                        </div>

                        <div class="clr">&nbsp;</div>

                </div>
            </div>

            <div class="photo-info-box">
            <div class="photo-navigation">
                {if $output.prev_url>''}<div class="prev-link"><a href="{$path[1].url}{$output.prev_url}">{$prev}</a></div>{/if}
                {if $output.next_url>''}<div class="next-link"><a href="{$path[1].url}{$output.next_url}">{$next}</a></div>{/if}
            </div>

            <div class="func-inner">

                <div class="background-slider">
                    <div class="filter-description">
                        ���� ����
                    </div>

                    <div class="slider-bg _1">
                        <div class="slider-canvas">
                            <div class="slider">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="b-button" onclick="Crop();return false;">
                    <div class="b-button-label"><div class="b-button-label-text">������������</div></div><div class="b-button-right"></div>
                </div>
                {if $output.user_data.uid > 0 }
                    <div class="b-button" onclick="insertCrop();return false;">
                        <div class="b-button-label"><div class="b-button-label-text">��������</div></div><div class="b-button-right"></div>
                    </div>
                {/if}
                <div class="b-button" onclick="Grid();return false;">
                    <div class="b-button-label"><div class="b-button-label-text">������� �������</div></div><div class="b-button-right"></div>
                </div>
                <div class="b-button" onclick="desaturate();return false;">
                    <div class="b-button-label"><div class="b-button-label-text">��������� � �/�</div></div><div class="b-button-right"></div>
                </div>

                <!--
                <a href="#" onclick="flipHor();return false;">
                    <img src='/img/filter-hor.png' alt='�������� ������' class="fbutton" />
                </a>

                <a href="#" onclick="flipVert();return false;">
                    <img src='/img/filter-vert.png' alt='�������� ������' class="fbutton" />
                </a>
                -->
                <div class="color-filter b-button">
                    <div class="b-button-label" onclick="filterexpand();return false;"><div class="b-button-label-text filter-switcher">�������� ������</div></div><div class="b-button-right"></div>

                    <div class="filtertable">
                        <div class="filter-description">
                            ������������
                        </div>

                        <div class="slider-bg _2">
                            <div class="slider-canvas-filter">
                                <div class="slider"></div>
                            </div>
                        </div>

                        <div class="filter-description">
                            ����
                        </div>

                        <div id="palette-box">
                            <div class="uc">&nbsp;</div>
                            <img id="palette" src="/img/palette/palette.png" />
                            <div id="palettebw"><img src="/img/palette/palettebw.png" /></div>
                            <div id="pointer"><img src="/img/palette/pointer.png" /></div>
                            <div id="pointer-s"><img src="/img/palette/pointer.png" /></div>
                        </div>
                    </div>
                </div>

                <div class="b-button">
                    <div class="b-button-label"><div class="b-button-label-text fullsize">������ ������</div></div><div class="b-button-right"></div>
                </div>

                <!--<div class="fullsize-div"><a href="#" class="smallsize"><img src='/img/filter-800.png' class="fbutton" alt='�������� ������' /></a></div>-->
                <!--<div class="fullsize-div"><a href="#" class="fullsize"><img src='/img/filter-full.png' class="fbutton" alt='�������� ������' /></a></div>-->

                <div class="clr">&nbsp;</div>

            </div>

        {/if}

        <div class="photo-info">
            <h3 class="gal-title-2">{$item.title}</h3>
            <div>{$item.descr}</div>
            {if $item.author>''}<div class="author">{$author}: {$item.author}</div>{/if}
            {if $item.source>''}<p>{$source}: {if $item.source_url}<a href="{$item.source_url}">{/if}{$item.source}{if $item.source_url}</a>{/if}</p>{elseif $item.source_url}<p>{$source}: <a href="{$item.source_url}">{$item.source_url}</a></p>{/if}
            <div class="clr">&nbsp;</div>
        </div>
        </div>

        </div>
    {/if}

{/if}
<!-- !���������� -->

<!-- ����������� -->
{if $output.commentsCount > 0}

    <div class="photo-info">
        <div class="new-label">�����������:</div>

        {assign var='comments' value=$output.comments}
        {assign var='stripe' value=0}
        {section loop=$comments name=key}
            {if $comments[key].parent_id == 0}

                {if $stripe == 0}
                    {assign var='stripe' value=1}
                {else}
                    {assign var='stripe' value=0}
                {/if}
                <div class="comment {if $stripe == 1}stripe{/if}">

                    <div class="comment-time">{$comments[key].time}</div>
                    <div class="comment-author">,&nbsp;{$comments[key].name}</div>

                    <div class="clr">&nbsp;</div>
                    <div class="comment-content">{$comments[key].comment}</div>

                    {section loop=$comments name=key_parent}
                        {if $comments[key].comment_id == $comments[key_parent].parent_id}
                            <div class="comment-replies">
                                <div class="comment-time">{$comments[key_parent].time}</div>
                                <div class="comment-author">,&nbsp;{$comments[key_parent].name}</div>
                                <div class="clr">&nbsp;</div>
                                <div class="comment-content">{$comments[key_parent].comment}</div>
                                {if $output.user_data.access_level > 50}
                                    <div class="comment-delete">
                                        <a href="?del={$comments[key_parent].comment_id}">�������</a>
                                    </div>
                                {/if}
                            </div>
                        {/if}
                    {/section}

                    <div class="clr">&nbsp;</div>

                    {if $output.user_data.access_level > 50}
                        <div class="comment-delete">
                            <a href="?del={$comments[key].comment_id}">�������</a>
                        </div>
                    {/if}

                    {if $output.user_data.access_level > 0}
                        <div class="comment-reply" onclick="CommentReply({$comments[key].comment_id});">
                            ��������
                        </div>
                    {/if}

                    <div class="reply-form _{$comments[key].comment_id}">

                    </div>

                </div>
            {/if}
        {/section}

    </div>
    <div class="clr">&nbsp;</div>
{/if}

<!-- ����� ����������� -->
{if $item.type != 0}

    <div class="photo-info">
        <div class="form-back" onclick="CommentPost();">
            �������� ����������� � ������
        </div>

        <div class="comments-form">

            {if $output.user_data.uid > 0 }
                {if $output.error > 0}
                    <div class="error"><p>�� ����������� ����� �����.</p></div>
                {/if}
                {assign var=send value=$output.send}
                <div class="form">
                    <div class="new-label com-label">�������� �����������</div>
                    <noidex>
                        <form class="comments-form" method="post" name="message" enctype="multipart/form-data">
                            <input type="hidden" value="0" id="parent-comment" name="parent-comment" />
                            <table cellpadding="4" cellspacing="0" width="580">
                                <tr height="88">
                                    <td class="field-name">��� �����������</td>
                                    <td class="form-text" valign="top"><textarea cols="50" rows="7" class="req" name="comment"></textarea></td>
                                </tr>
                                <tr height="24">
                                    <td class="field-name">
                                        <img src="{$output.protect_img}" id="antispam" alt="�������� ���">
                                    </td>
                                    <td class="form-text">
                                        <input type="text" class="req" name="code" value="" id="captcha">
                                    </td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td align="left"><input type="submit" id="button" value="���������" align="right"></td>
                                </tr>
                            </table>
                        </form>
                        </noindex>
                </div>
                <div class="break"></div>
            {else}
                <div class="warning">
                    <p>������ ������������������ ������������ ����� ��������� �����������.</p>
                </div>
            {/if}
        </div>

    </div>
{/if}

<!-- !����� ����������� -->
<!-- !����������� -->

<!-- ������� -->
{if $output.countSections > 0}
    {if $output.item.parent_id neq 0}
        <div class="gallery">
            <h1>{$output.item.title}</h1>
            <div class="sections">
                {assign var='items' value=$output.sections}
                {section loop=$items name=key}
                    <div class="album-item"><div class="album-item-inner">
                            <div class="album-thumb">
                                {if $items[key].img_sm>''}
                                    <!-- ��� ������ ������� �������, �������� ��� ��� �� �������� -->
                                    <a href="{$items[key].url}"><img src="{$items[key].img_sm}" alt="{$items[key].title|escape:'html'}" title="{$items[key].title|escape:'html'}"></a>
                                {/if}
                            </div>
                            <div class="album-name">
                                <a href="{$items[key].url}">{$items[key].title}</a>
                            </div>
                            <div class="album-description">
                                {$items[key].descr_ru}
                            </div>
                        </div></div>
                    <div class="clr">&nbsp;</div>
                {/section}
            </div>
        </div>
        <div class="clr">&nbsp;</div>
    {else}
        <div class="gallery">
            <h1>{$output.item.title}</h1>
            <div class="sections">
                {assign var='items' value=$output.sections}
                {section loop=$items name=key}
                    <div class="section-item"><div class="section-item-inner">
                            <div class="section-thumb">
                                {if $items[key].img_sm>''}
                                    <!-- ��� ������ ������� �������, �������� ��� ��� �� �������� -->
                                    <a href="{$items[key].url}"><img src="{$items[key].img_sm}" alt="{$items[key].title|escape:'html'}" title="{$items[key].title|escape:'html'}"></a>
                                {/if}
                            </div>
                            <div class="section-name">
                                <a href="{$items[key].url}">{$items[key].title}</a>
                            </div>
                        </div></div>

                {/section}
            </div>
        </div>
        <div class="clr">&nbsp;</div>
    {/if}
{/if}

<!-- !������� -->

<!-- ������ � ������������ -->

{if $output.countItems > 0}
    <div class="gallery">
        <h1>{$output.item.title}</h1>
        <div class="sections">
            {assign var='items' value=$output.items}
            {section loop=$items name=key}
                <div class="section-item"><div class="section-item-inner">
                        <div class="section-thumb">
                            {if $items[key].img_sm>''}
                                <!-- ��� ������ ������� �������, �������� ��� ��� �� �������� -->
                                <a href="{$items[key].url}" rel="{$items[key].img}" title="{$items[key].title|escape:'html'}" >
                                    <img src="{$items[key].img_sm}" />
                                </a>
                            {/if}
                        </div>
                        <div class="photo-name">
                            <a href="{$items[key].url}">{$items[key].title}</a>
                        </div>
                    </div></div>
            {/section}
        </div>
    </div>
    <div class="clr">&nbsp;</div>
{/if}

<!-- !������ � ������������ -->

<!-- ����������� -->

{if $output.navi.pages_total > 1}
    <div class="pages">
        {if $output.countItems == $output.navi.items_total}
            <div class="all">{if $output.showSorting==1}<a href="{$request_url}">���������� �� ���������</a>{/if}</div>
        {else}
            <!--{$page}:-->
            {assign var='items' value=$output.navi.pages}
            {section loop=$items name=key}
                {if $items[key].title neq $output.navi.page}

                    <a href="{$items[key].url}"><div class="page">{$items[key].title}</div></a>
                {else}
                    <div class="current-page">{$items[key].title}</div>
                {/if}
            {/section}
        {/if}
        <div class="clr">&nbsp;</div>
    </div></div>
{/if}

<!-- !����������� -->