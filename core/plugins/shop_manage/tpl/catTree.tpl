{function name=TPLBuildCat}
	<div style="padding: 20px 20px 20px 40px;border: 1px solid red;">
		CATEGORY id: {$cat->getId()} <a href="?plg=shop_manage&arg[action]=editCat&arg[id]={$cat->getId()}">EDIT</a>
		{foreach from=$cat->getChildren() item=child name=pewpew}
			{TPLBuildCat cat=$child}
		{/foreach}
	</div>
{/function}

<a href="?plg=shop_manage&arg[action]=addCat">ADD</a>
{foreach $tree as $cat}{TPLBuildCat cat=$cat}{/foreach}