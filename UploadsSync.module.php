<?php

class UploadsSync extends CMSModule
{
	function UploadsSync()
	{
		$this->CMSModule();
	}

	function GetName()
	{
		return 'UploadsSync';
	}
	function GetVersion()
	{
		return '0.1';
	}
	function GetAuthor()
	{
		return 'Ted Kulp';
	}
	function GetAuthorEmail()
	{
		return 'ted@cmsmadesimple.org';
	}
	function HasAdmin()
	{
		return true;
	}
	function MinimumCMSVersion()
	{
		return "1.9";
	}

	function HasCapability($capability, $params = array())
	{
		switch ($capability)
		{
			case 'restserver':
				return TRUE;
			default:
				return FALSE;
		}
	}

	function AddRestMap($rest_server)
	{
		$rest_server->addMap("GET", "/?uploadsync/categories", array(&$this, 'ListCategories'));
		$rest_server->addMap("GET", "/?uploadsync/files", array(&$this, 'ListUploads'));
		$rest_server->addMap("GET", "/?uploadsync/fielddefs", array(&$this, 'ListFieldDefs'));
	}

	function ListUploads($rest_server)
	{
		global $gCms;
		$db = $gCms->GetDb();
		$data = $db->GetAll("SELECT * FROM " . cms_db_prefix() . "module_uploads ORDER BY upload_id ASC");
		$this->MakeRowHashes($data);
		$rest_server->getResponse()->setResponse(json_encode($data));
		return $rest_server;
	}

	function ListCategories($rest_server)
	{
		global $gCms;
		$db = $gCms->GetDb();
		$data = $db->GetAll("SELECT * FROM " . cms_db_prefix() . "module_uploads_categories ORDER BY upload_category_id ASC");
		$this->MakeRowHashes($data);
		$rest_server->getResponse()->setResponse(json_encode($data));
		return $rest_server;
	}

	function ListFieldDefs($rest_server)
	{
		global $gCms;
		$db = $gCms->GetDb();
		$data = $db->GetAll("SELECT * FROM " . cms_db_prefix() . "module_uploads_fielddefs ORDER BY id ASC");
		$this->MakeRowHashes($data);
		$rest_server->getResponse()->setResponse(json_encode($data));
		return $rest_server;
	}
	
	function MakeRowHashes(&$ary)
	{
		if (is_array($ary))
		{
			foreach ($ary as &$one_item)
			{
				$one_item['sync_hash'] = sha1(serialize($one_item));
			}
		}
	}

} //end class
