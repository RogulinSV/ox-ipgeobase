<div class="geo">

	{if $errors|@count}
	<table border='0' width='100%' cellpadding='0' cellspacing='0'>
		<tr>
			<td width='30'>&nbsp;</td><td height='10' colspan='2'>
			<table width='100%' border='0' cellspacing='0' cellpadding='0'>
				</tr>
				<td width='22' valign='top'><img src='{$assetPath}/images/error.gif' width='16' height='16'>&nbsp;&nbsp;</td>
				<td valign='top'>
					<font color='#AA0000'><b>
						{foreach from=$errors item=error}
						{$error}<br />
						{/foreach}
					</b></font>
				</td>
				</tr>
			</table>
		</td>
		</tr>

		<tr>
			<td height='10' width='30'>&nbsp;</td>
			<td height='10' width='200'><img src='{$assetPath}/images/spacer.gif' width='200' height='1'></td>
			<td height='10' width='100%'>&nbsp;</td><td height='10' width='30'>&nbsp;</td>
		</tr>
		<tr>
			<td height='14' width='30'><img src='{$assetPath}/images/spacer.gif' height='1' width='100%'></td>
			<td height='14' width='200'><img src='{$assetPath}/images/break-l.gif' height='1' width='200' vspace='6'></td>
			<td height='14' width='100%'>&nbsp;</td><td height='14' width='30'><img src='{$assetPath}/images/spacer.gif' height='1' width='100%'>
		</tr>
	</table>
	{/if}

	{if $messages|@count}
	<div class="infomessage" style="margin-bottom: 10px">
		{foreach from=$messages key=idx item=message}
		{$message}
		<br />
		{/foreach}
	</div>
	{/if}

	{*oxToolBoxForm form*}
	{include file=$oaTemplateDir|cat:'form/form.html' form=$form}
	{*/oxToolBoxForm form*}

</div>

{phpAds_ShowBreak}