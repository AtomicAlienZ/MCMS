{*{$output|var_dump}*}
{*{$output.item->getMedia()|var_dump}*}
<div>
	<h1>{$output.item->getName($lang)} ({$output.item->getPrice()} денег)</h1>
	<button style="width: 300px;height: 100px;background: #ff0000;color: #ffffff;font-weight: bold;">I WANT IT NOW</button>
	<fieldset>
		<legend>MEDIA</legend>
		{foreach from=$output.item->getMedia() item=media}
			{if $media.type == 'video'}
				<a href="{$media.url}">
					YOUTUBE VIDEO
				</a>
			{else}
				<a href="{$media.originalurl}">
					<img src="{$media.url}" alt="">
				</a>
			{/if}
		{/foreach}
	</fieldset>
	<fieldset>
		<legend>DESCRIPTION</legend>
		{$output.item->getDesc($lang)|nl2br}
	</fieldset>
	<fieldset>
		<legend>FIELDS</legend>
		{foreach from=$output.item->getFields($lang) item=field}
			<div>
				<b>{$field.name}</b>
				{if is_array($field.value)}
					{foreach from=$field.value item=valuepart}
						{$valuepart},
					{/foreach}
				{else}
					{$field.value}
				{/if}
			</div>
		{/foreach}
	</fieldset>
</div>
