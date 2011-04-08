<?php
#-------------------------------------------------------------------------
# Module: Uploads -= allow users to upload stuff, a pseudo file manager" module
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

$taboptarray = array ('mysql' => 'TYPE=MyISAM');

$current_version = $oldversion;
switch ($oldversion)
{
	case "0.1":
	{
		$db = $this->GetDb();

		$dict = NewDataDictionary($db);

		// table schema description
		$flds = "id I KEY AUTO,
				 upload_id I,
				 change_type C(25),
				 synced I1 default 0,
				 sync_date ".CMS_ADODB_DT.",
				 create_date ".CMS_ADODB_DT.",
				 modified_date ".CMS_ADODB_DT."
		";

		$taboptarray = array('mysql' => 'TYPE=MyISAM');
		$sqlarray = $dict->CreateTableSQL(cms_db_prefix()."module_uploads_sync", $flds, $taboptarray);
		$dict->ExecuteSQLArray($sqlarray);

		$this->AddEventHandler('Uploads', 'OnUpload', false);
		$this->AddEventHandler('Uploads', 'OnEditUpload', false);
		$this->AddEventHandler('Uploads', 'OnRemove', false);
	}
}
