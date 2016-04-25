{function name=TPLBuildCat}
	{if $cat->isVisible()}
	<div style="padding: 20px 20px 20px 40px;border: 1px solid red;">
		CATEGORY id: {$cat->getId()}
		<br>
		ALIAS: {$cat->getAlias()}
		<br>
		NAMES: {foreach from=$langs item=l}"{$cat->getName($l)}" ({$l}), {/foreach}
		{if $cat->getFieldset()}
			<br>
			FIELDSET: {$cat->getFieldset()->getName()}
		{/if}
		<br>
		{if $cat->isVisible()}
			<a href="?id={$cat->getId()}">VIEW</a>
		{/if}
		{foreach from=$cat->getChildren() item=child name=pewpew}
			{TPLBuildCat cat=$child}
		{/foreach}
	</div>
	{/if}
{/function}

{foreach $output.tree as $cat}{TPLBuildCat cat=$cat}{/foreach}