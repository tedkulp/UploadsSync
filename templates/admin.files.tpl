<h3>Files Not On Remote Server</h3>

{$start_form}
<table>
	<tr>
		<th>&nbsp;</th>
		<th>File Name</th>
		<th>Size</th>
		<th>Uploaded On</th>
	</tr>
{foreach from=$changed_files item='one_file'}
	<tr>
		<td>{$one_file.checkbox}</td>
		<td>{$one_file.upload_name}</td>
		<td>{$one_file.upload_size}</td>
		<td>{$one_file.upload_date|date_format}</td>
	</tr>
{foreachelse}
	<tr>
		<td colspan="4"><strong>No New Entries!</strong></td>
	</tr>
{/foreach}
</table>
<p style="margin-top: 20px;">
{$submit}
</p>
{$end_form}
