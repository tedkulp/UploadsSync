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

$endpoint_url = trim(trim($this->GetPreference('remote_server', '')), '/') . '/index.php';
$has_endpoint = $endpoint_url != '';
$username = $this->GetPreference('remote_server_username', '');
$password = $this->GetPreference('remote_server_password', '');

if ($has_endpoint)
{
	global $lang_fn;
	$lang_fn = 'lang';

	include_once(cms_join_path($config['root_path'], 'lib', 'test.functions.php'));

	$categories = array();
	$field_defs = array();
	$files = array();

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

	$files_url = $endpoint_url . '/uploadsync/files';
	$remote_test = testRemoteFile(0, lang('test_remote_url'), $categories_url, lang('test_remote_url_failed'));
	if ($remote_test->continueon)
	{
		$files = $this->curl_get_file_contents($files_url, $username, $password);
		if ($files == 'Unauthorized')
		{
			echo "<p><strong>" . $this->Lang('username_password_invalid') . '</strong></p>';
			return;
		}
		else
		{
			//Ok, we're good.  Continue on.
			$files = json_decode($files);
		}
	}

	echo '<pre>';
	var_dump($files);
	var_dump($categories);
	var_dump($field_defs);
	echo '</pre>';
}
