<div class="geo">

{if $errors|@count}
<div class="errormessage">
	{foreach from=$errors item=error}
	<div>
		<img class="errormessage" align="absmiddle" width='16' height='16' src="/www/admin/assets/images/padlock-closed.gif">
		{t str=FormImportErrorRequirements}: {$error}
	</div>
	{/foreach}
</div>
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

{if $list|@count}
<table class="table scheduling">
	<thead>
	<tr>
		<th colspan="7">{t str=TableScheduledTitle}</th>
	</tr>
	<tr>
		<th>ID</th>
		<th>{t str=TableScheduledStatusTitle}</th>
		<th>{t str=TableScheduledTaskCreatedTitle}</th>
		<th>{t str=TableScheduledTaskOpenedTitle}</th>
		<th>{t str=TableScheduledTaskClosedTitle}</th>
		<th>{t str=TableScheduledAuthorTitle}</th>
		<th width="18"></th>
	</tr>
	</thead>
	<tbody>
	{foreach from=$list item=task}
	<tr class="{$task.job_status|escape}" data-role="task" data-bind-for="{$task.job_id}">
		<td>{$task.job_id}</td>
		<td data-role="task.status">
			<span data-role="status" class="status_{$task.job_status|escape}">{$task.job_status|escape}</span>
			{if $task.job_status == 'run'}
				<span data-role="percent" class="progress"><span>
			{/if}
		</td>
		<td data-role="task.created">{$task.job_scheduled|escape}</td>
		<td data-role="task.opened">{$task.job_started|escape}</td>
		<td data-role="task.closed">{$task.job_completed|escape}</td>
		<td>{$task.author_email|escape} &lt;{$task.job_author|escape}&gt;</td>
		<td>
			{if $task.job_status == 'wait'}
			<a href="?action=cancel&amp;id={$task.job_id}" title="{t str=CancelTaskHint}" data-confirm-text="{t str=CancelTaskConfirm}" class="button button-cancel"></a>
			{/if}
		</td>
	</tr>
	{/foreach}
	</tbody>
</table>
{/if}

</div>

{phpAds_ShowBreak}