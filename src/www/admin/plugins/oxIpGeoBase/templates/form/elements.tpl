{include file=$oaTemplateDir|cat:'form/elements.html'}

{if $_e.type == 'file' || $_e.type == 'hierselect'}
<tr>
	<td width='30'>&nbsp;</td><td width='170'><label for="{$_e.id|default:$_e.name|escape}">{$_e.label} {if $_e.required}<font color="red">*</font>{/if}</label></td>
	<td width='100%'>
		{include file=$oaTemplateDir|cat:'form/element-raw.html' elem=$_e} {if $_e.error}<label class="error" for="{$_e.id|default:$_e.name|escape}">{$_e.error}</label>{/if}
	</td>
</tr>
{/if}