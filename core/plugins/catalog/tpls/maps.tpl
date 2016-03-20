{assign var='tpl' value=$output.pathTemplate|cat:'maps/'|cat:$output.tpl|cat:'.tpl'}
{include file=$tpl}
