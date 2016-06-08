{function name=TPLBuildCat}
	<div style="padding: 20px; box-shadow: 0px 2px 4px rgba(20, 20, 20, .1); margin: 20px 0;">
		{$cat->getAlias()}<br>
		Category id: {$cat->getId()} <a href="?plg=shop_manage&arg[action]=editCat&arg[id]={$cat->getId()}">EDIT</a>
		{foreach from=$cat->getChildren() item=child name=pewpew}
			{TPLBuildCat cat=$child}
		{/foreach}
	</div>
{/function}

<a href="?plg=shop_manage&arg[action]=addCat">ADD</a>
{foreach $tree as $cat}{TPLBuildCat cat=$cat}{/foreach}