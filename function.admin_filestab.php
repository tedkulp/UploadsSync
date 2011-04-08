<?php
#-------------------------------------------------------------------------
# Module: UploadSync
# Author: Ted Kulp <ted@cmsmadesipmle.org>
#
#-------------------------------------------------------------------------
# CMS - CMS Made Simple is (c) 2005 by Ted Kulp (wishy@cmsmadesimple.org)
# This project's homepage is: http://www.cmsmadesimple.org
#
#-------------------------------------------------------------------------
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: http:	//www.gnu.org/licenses/licenses.html#GPL
#
#-------------------------------------------------------------------------
#-------------------------------------------------------------------------
if (!isset($gCms)) exit;

$endpoint_url = trim(trim($this->GetPreference('remote_server', '')), '/') . '/index.php?q=';
$has_endpoint = $endpoint_url != '';
$username = $this->GetPreference('remote_server_username', '');
$password = $this->GetPreference('remote_server_password', '');

if ($has_endpoint)
{
	$local_files = $this->GetListUploads();
	$local_categories = $this->GetListCategories();
	$local_field_defs = $this->GetListFieldDefs();

	$smarty->assign('local_files', $local_files);
	$smarty->assign('local_categories', $local_categories);
	$smarty->assign('local_field_defs', $local_field_defs);

	if (isset($params['submit_sync']) && isset($params['checked']))
	{
		foreach($params['checked'] as $k => $v)
		{
			if ($v == '1')
			{
				$ret = null;
				$return_id = -1;
				$local_id = $k;
				$md5 = '';
				if (strpos($k, '-') !== false)
				{
					list($local_id, $the_remote_id, $md5) = explode('-', $k);
					if (is_numeric($local_id) && is_numeric($the_remote_id))
					{
						$return_id = $the_remote_id;
					}
				}

				$send_file = true;
				$ret = $this->CopyFileOverWire($endpoint_url . '/uploadsync/files', $k, $username, $password, $return_id, $md5);
				if ($ret && trim($ret) == 'true')
				{
					$db->Execute("UPDATE ".cms_db_prefix()."module_uploads_sync SET synced = 1 WHERE upload_id = ? AND synced = 0", array($local_id));
				}
			}
		}
	}

	global $lang_fn;
	$lang_fn = 'lang';

	include_once(cms_join_path($config['root_path'], 'lib', 'test.functions.php'));

	$categories = array();
	$field_defs = array();
	$remote_files = array();

	$categories_url = $endpoint_url . '/uploadsync/categories';
	$remote_test = testRemoteFile(0, lang('test_remote_url'), $categories_url, lang('test_remote_url_failed'));
	if ($remote_test->continueon)
	{
		$categories = $this->curl_get_file_contents($categories_url, $username, $password);
		if ($categories == 'Unauthorized')
		{
			echo "<p><strong>" . $this->Lang('username_password_invalid') . '</strong></p>';
			return;
		}
		else
		{
			//Ok, we're good.  Continue on.
			$categories = json_decode($categories);
		}
	}

	$field_defs_url = $endpoint_url . '/uploadsync/fielddefs';
	$remote_test = testRemoteFile(0, lang('test_remote_url'), $categories_url, lang('test_remote_url_failed'));
	if ($remote_test->continueon)
	{
		$field_defs = $this->curl_get_file_contents($field_defs_url, $username, $password);
		if ($field_defs == 'Unauthorized')
		{
			echo "<p><strong>" . $this->Lang('username_password_invalid') . '</strong></p>';
			return;
		}
		else
		{
			//Ok, we're good.  Continue on.
			$field_defs = json_decode($field_defs);
		}
	}

	$local_files = $this->GetFileListFromQueue();
	$files_url = $endpoint_url . '/uploadsync/files';
	$remote_test = testRemoteFile(0, lang('test_remote_url'), $categories_url, lang('test_remote_url_failed'));
	if ($remote_test->continueon)
	{
		$remote_files = $this->curl_get_file_contents($files_url, $username, $password);
		if ($remote_files == 'Unauthorized')
		{
			echo "<p><strong>" . $this->Lang('username_password_invalid') . '</strong></p>';
			return;
		}
		else
		{
			//Ok, we're good.  Continue on.
			$remote_files = json_decode($remote_files);
		}
	}

	$smarty->assign('local_files', $local_files);
	$smarty->assign('remote_files', $remote_files);
	$smarty->assign('categories', $categories);
	$smarty->assign('field_defs', $field_defs);

	//$changed_files = $this->CompareFiles($local_files, $remote_files);

	//foreach ($changed_files as &$one_file)
	foreach ($local_files as &$one_file)
	{
		$found = false;
		foreach ($remote_files as $remote_file)
		{
			if ($remote_file->upload_name == $one_file->upload_name)
			{
				$one_file->checkbox = $this->CreateInputHidden($id, 'checked[' . $one_file->upload_id . '-' . $remote_file->upload_id . '-' . $remote_file->md5 . ']', '0') . $this->CreateInputCheckbox($id, 'checked[' . $one_file->upload_id . '-' . $remote_file->upload_id . '-' . $remote_file->md5 . ']', '1', '0');
				$found = true;
				break;
			}
		}

		if (!$found)
			$one_file->checkbox = $this->CreateInputHidden($id, 'checked[' . $one_file->upload_id . ']', '0') . $this->CreateInputCheckbox($id, 'checked[' . $one_file->upload_id . ']', '1', '0');
	}

	$smarty->assign('changed_files', $local_files);

	$smarty->assign('start_form', $this->CreateFormStart($id, 'defaultadmin', $returnid));
	$smarty->assign('submit', $this->CreateInputSubmit($id, 'submit_sync', $this->Lang('submit'), '', '', $this->Lang('areyousuresync')));
	$smarty->assign('end_form', $this->CreateFormEnd());

	echo $this->ProcessTemplate('admin.files.tpl');
}
else
{
	echo "<p><strong>" . $this->Lang('endpoint_only') . "</strong></p>";
}
