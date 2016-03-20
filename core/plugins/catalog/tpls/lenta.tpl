{if $output.NewsItemsCount > 0}
	<div id="new">
		<div class="label">новинки</div>
		<div id="arrow-left"><img src="/img/arrow-left.png" /></div>
		<div id="new-box">
			<div id="nb-inner">
				{assign var='items' value=$output.NewsItems}
				{section loop=$items name=key}
					{assign var='item' value=$items[key]}
					<div class="new-i">
						<div class="new-i-img">
							<img src="{$item.img}" />
						</div>
						<a class="new-i-name" href="{$item.full_relative_url}">{$item.title}</a>
						<div class="price">
							<div class="q">{$item.price}&nbsp;</div>
							<div class="c">uah</div>
						</div>
					</div>
				{/section}
			</div>
		</div>
		<div id="arrow-right"><img src="/img/arrow-right.png" /></div>
	</div>
{/if}