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

echo $this->StartTabHeaders();

if ($this->GetPreference('remote_server', '') != '')
{
	echo $this->SetTabHeader('files', $this->Lang('files'));
}
echo $this->SetTabHeader('settings', $this->Lang('settings'));

echo $this->EndTabHeaders();

echo $this->StartTabContent();

if ($this->GetPreference('remote_server', '') != '')
{
	echo $this->StartTab('files');
	include(dirname(__FILE__).'/function.admin_filestab.php');
	echo $this->EndTab();
}

echo $this->StartTab('settings');

$form_settings = new CMSForm($this->GetName(), $id.'settings', 'defaultadmin', $returnid);
$form_settings->setLabel('submit', $this->lang('save'));

$form_settings->setFieldset($this->lang('sync_settings'));
$form_settings->getFieldset($this->lang('sync_settings'))->setWidget('remote_server', 'text', array('preference' => 'remote_server', 'tips' => $this->Lang('remote_server_tips')));
$form_settings->getFieldset($this->lang('sync_settings'))->setWidget('remote_server_username', 'text', array('preference' => 'remote_server_username',));
$form_settings->getFieldset($this->lang('sync_settings'))->setWidget('remote_server_password', 'password', array('preference' => 'remote_server_password'));

$form_settings->setFieldset($this->lang('email_settings'));
$form_settings->getFieldset($this->lang('email_settings'))->setWidget('email_address', 'text', array('preference' => 'email_address'));

if ($form_settings->isPosted())
{
	$form_settings->process();
	return $this->Redirect($id, 'defaultadmin', $returnid, array('tab' => 'settings'));
}

$this->smarty->assign('form_settings', $form_settings);
echo $this->ProcessTemplate('admin.settings.tpl');

echo $this->EndTab();

echo $this->EndTabContent();
