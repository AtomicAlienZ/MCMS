{if $output.countItems>0}
<div class="center_bg">
	<div class="wtext">
		<div class="top"><img class="png" src="/img/px.gif" style="background: url(/img/txt/top.png)" width="642" height="32"/></div>
		<div class="center">
			{section name=key loop=$output.items}
				<div class="itm">
					<div class="ttl"><a href="{$output.items[key].relative_url}">{$output.items[key].title}</a></div>
					{if $output.items[key].content>''}<div class="descr">{$output.items[key].content|strip_tags|truncate:200}</div>{/if}
				</div><br>
			{/section}
		</div>
		<div class="bottom"><img class="png" src="/img/px.gif" style="background: url(/img/txt/bottom.png)" width="642" height="33"/></div>
	</div>
</div>
<div class="center_text_bg_bottom">&nbsp;</div>
<div class="polka">&nbsp;</div>
{/if}