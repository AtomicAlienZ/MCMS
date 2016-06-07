{function name=TPLBuildCat}
	{if $cat->isVisible()}
	<div style="padding: 20px 20px 20px 40px; box-shadow: 0px 2px 4px rgba(20, 20, 20, .1); margin: 20px 0;">
		Category id: {$cat->getId()}
		<br>
		Alias: {$cat->getAlias()}
		<br>
		Names: {foreach from=$langs item=l}"{$cat->getName($l)}" ({$l}), {/foreach}
		{if $cat->getFieldset()}
			<br>
			Fieldset: {$cat->getFieldset()->getName()}
		{/if}
		<br>
		{if $cat->isVisible()}
			<a class="sal-ui__button" style="margin-top: 20px;" href="{$output._baseURL}?id={$cat->getId()}">VIEW</a>
		{/if}
		{foreach from=$cat->getChildren() item=child name=pewpew}
			{TPLBuildCat cat=$child}
		{/foreach}
	</div>
	{/if}
{/function}

{foreach $output.tree as $cat}{TPLBuildCat cat=$cat}{/foreach}