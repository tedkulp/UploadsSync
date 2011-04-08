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
		return '0.2';
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

	function GetDependencies()
	{
		return array('CMSForms' => '0.0.8');
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
		$rest_server->addMap("POST", "/?uploadsync/files", array(&$this, 'HandleNewFile'));
	}

	function GetListUploads()
	{
		global $gCms;
		$db = $gCms->GetDb();
		$uploads = $this->GetModuleInstance("Uploads");

		$result = $db->GetAll("SELECT * FROM " . cms_db_prefix() . "module_uploads ORDER BY upload_id ASC");
		foreach ($result as &$one_row)
		{
			unset($one_row['description']);
			$one_row['md5'] = '';
			$dir = $uploads->_categoryPath($uploads->getCategoryPathFromID($one_row['upload_category_id']));
			if ($dir && is_dir($dir))
			{
				$filename = cms_join_path($dir, $one_row['upload_name']);
				if (is_file($filename))
				{
					$one_row['md5'] = md5_file($filename);
				}
			}
		}
		return $result;
	}

	function ListUploads($rest_server)
	{
		$data = $this->GetListUploads();
		$rest_server->getResponse()->setResponse(json_encode($data));
		return $rest_server;
	}

	function GetListCategories()
	{
		global $gCms;
		$db = $gCms->GetDb();
		$data = $db->GetAll("SELECT * FROM " . cms_db_prefix() . "module_uploads_categories ORDER BY upload_category_id ASC");
		$this->MakeRowHashes($data);
		return $data;
	}

	function ListCategories($rest_server)
	{
		$data = $this->GetListCategories();
		$rest_server->getResponse()->setResponse(json_encode($data));
		return $rest_server;
	}

	function GetListFieldDefs()
	{
		global $gCms;
		$db = $gCms->GetDb();
		$data = $db->GetAll("SELECT * FROM " . cms_db_prefix() . "module_uploads_fielddefs ORDER BY id ASC");
		$this->MakeRowHashes($data);
		return $data;
	}

	function ListFieldDefs($rest_server)
	{
		$data = $this->GetListFieldDefs();
		$rest_server->getResponse()->setResponse(json_encode($data));
		return $rest_server;
	}

	function GetFileListFromQueue()
	{
		global $gCms;
		$db = $gCms->GetDb();
		$ids = array();

		$uploads = $this->GetModuleInstance("Uploads");

		$result = $db->GetAll("SELECT u.*, us.change_type FROM " . cms_db_prefix() . "module_uploads_sync us INNER JOIN " . cms_db_prefix() . "module_uploads u ON u.upload_id = us.upload_id WHERE synced = 0 ORDER BY create_date ASC");

		foreach ($result as &$one_row)
		{
			unset($one_row['description']);
			$one_row['md5'] = '';
			$dir = $uploads->_categoryPath($uploads->getCategoryPathFromID($one_row['upload_category_id']));
			if ($dir && is_dir($dir))
			{
				$filename = cms_join_path($dir, $one_row['upload_name']);
				if (is_file($filename))
				{
					$one_row['md5'] = md5_file($filename);
				}
			}
		}

		//Make the results act like the remote ones by running
		//it through json
		return json_decode(json_encode($result));
	}

	function HandleNewFile($rest_server)
	{
		$post = $rest_server->getRequest()->getPost();
		$files = $rest_server->getRequest()->getFiles();

		global $gCms;
		$db = $gCms->GetDb();

		@ob_start();

		//Test for proper content
		if (isset($post['upload_name']) && isset($post['upload_category_name']))
		{
			$uploads = $this->GetModuleInstance("Uploads");
			if ($uploads)
			{
				//Because categories may not be synced up by id, we get it by name
				$category = $uploads->getCategoryFromName($post['upload_category_name']);
				if (!$category)
				{
					//No category?  No problem... we just add it
					$dir = $uploads->_categoryPath($post['upload_category_path']);
					if (is_dir($dir))
					{
						//Error?!?
						$rest_server->getResponse()->setResponse('is_dir - false');
					}
					else
					{
						if (!mkdir($dir, 0777, true))
						{
							//Error?!?
							$rest_server->getResponse()->setResponse('mkdir - false');
						}
						else
						{
							// create an index.html file (empty)
							if ($uploads->GetPreference('create_dummy_index_html'))
							{
								touch($dir.DIRECTORY_SEPARATOR."index.html");
							}

							$catid = $db->GenID(cms_db_prefix()."module_uploads_categories_seq");
							$query =
								"INSERT INTO ".cms_db_prefix()."module_uploads_categories 
								(upload_category_id, upload_category_name, upload_category_description,
								upload_category_path, upload_category_listable, upload_category_deletable,
								upload_category_expires_hrs, 
								upload_category_scannable,
								upload_category_groups)
								VALUES (?,?,?,?,?,?,?,?,?)";
							$dbresult =
								$db->Execute($query,
									array($catid,
									$post["upload_category_name"],
									$post["upload_category_description"],
									$post["upload_category_path"],
									$post["upload_category_listable"],
									$post["upload_category_deletable"],
									$post['upload_category_expires_hrs'],
									$post['upload_category_scannable'],
									$post['upload_category_groups']));

							if (!$dbresult)
							{
								//Error?!?
								$rest_server->getResponse()->setResponse('dbresult - false');
							}
							else
							{
								if( $do_scan )
								{
									// category is added, and the directory already existed... so now
									// we're gonna scan the directory and add files to the database
									$uploads->ScanDirectory($catid, $dir);
								}

								// send an event
								$parms = array();
								$parms['name'] = $post['upload_category_name'];
								$parms['description'] = $post['upload_category_description'];
								$parms['path'] = $post['upload_category_path'];
								$parms['listable'] = $post['upload_category_listable'];
								$parms['deletable'] = $post['upload_category_deletable'];
								$parms['expires_hrs'] = $post['upload_category_expires_hrs'];
								$uploads->SendEvent('OnCreateCategory', $parms);

								$category = $uploads->load_category_by_id($catid);

								//$rest_server->getResponse()->setResponse('true');
							}
						}
					}
				}

				if ($category)
				{
					$result = null;

					if (isset($post['upload_id']))
					{
						$result = array(FALSE, $post['upload_id'], '');
						$handle_result = array(true);

						if (isset($_FILES['file']))
						{
							$dir = $uploads->_categoryPath($category['upload_category_path']);
							$uploads->_handleUpload($dir, 'file', false, $post['upload_name'], true, true, '');
						}

						if ($handle_result && $handle_result[0])
						{
							//We have an update -- and since the API doesn't handle this, we
							//have to recreate it all.  Good times!
							$newdesc = trim($post['upload_description']);
							$newsummary = trim($post['upload_summary']);
							$newauthor = trim($post['upload_author']);
							$newcategory = $category['upload_category_id'];

							$query = "UPDATE ".cms_db_prefix()."module_uploads SET
											 upload_author = ?,
											 upload_summary = ?,
											 upload_description = ?,
											 upload_category_id = ?
									   WHERE upload_id = ?";
							$dbresult = $db->Execute($query, array($post['upload_author'], $post['upload_summary'], $post['upload_description'], $category['upload_category_id'], $post['upload_id']));

							// delete any existing custom fields
							$query = 'DELETE FROM '.cms_db_prefix().'module_uploads_fieldvals WHERE upload_id = ?';
							$db->Execute($query, array($post['upload_id']));

							$result[0] = TRUE;
						}
					}
					else
					{
						//Ok, we're good.  Now let's "upload" the file through
						//the module
						$params = array();
						$params['field_name'] = 'file'; //So it know's where to find the file in $_FILES
						$params['input_destname'] = $post['upload_name'];
						$params['input_author'] = $post['upload_author'];
						$params['input_summary'] = $post['upload_summary'];
						$params['input_description'] = $post['upload_description'];
						$params['category_id'] = $category['upload_category_id'];
						$result = $uploads->AttemptUpload('', $params, 'cntnt01');
					}

					if ($result && $result[0])
					{
						//Custom fields
						if (isset($post['custom_fields']))
						{
							$custom_fields = unserialize($post['custom_fields']);
							foreach($custom_fields as $one_row)
							{
								$field_def_id = $db->GetOne("SELECT id FROM ".cms_db_prefix()."module_uploads_fielddefs where name = ?", array($one_row['name']));
								if (!$field_def_id)
								{
									if ($db->Execute("INSERT INTO ".cms_db_prefix()."module_uploads_fielddefs (name, type, attribs, iorder, public) values (?,?,?,?,?)", array($one_row['name'], $one_row['type'], $one_row['attribs'], $one_row['iorder'], $one_row['public'])))
									{
										$field_def_id = $db->Insert_ID();
									}
								}
								if ($field_def_id)
								{
									$db->Execute("INSERT INTO ".cms_db_prefix()."module_uploads_fieldvals (upload_id, fld_id, value) VALUES (?,?,?)", array($result[1], $field_def_id, $one_row['value']));
								}
							}
						}
						$rest_server->getResponse()->setResponse('true');
					}
					else
					{
						$rest_server->getResponse()->setResponse('result - false - ' . serialize($result));
					}
				}
				else
				{
					$rest_server->getResponse()->setResponse('category - false');
				}
			}
		}
		else
		{
			$rest_server->getResponse()->setResponse('uploads - false');
		}

		/*
		$out2 = @ob_get_contents();
		@ob_end_clean();

		$rest_server->getResponse()->setResponse($out2);
		*/

		return $rest_server;
	}
	
	function MakeRowHashes(&$ary)
	{
		if (is_array($ary))
		{
			foreach ($ary as &$one_item)
			{
				$copy = $one_item;

				//if (isset($copy['upload_category_id']) && !isset($copy['upload_id'])) unset($copy['upload_category_id']);
				if (array_key_exists('upload_category_id', $copy)) unset($copy['upload_category_id']);
				if (array_key_exists('upload_id', $copy)) unset($copy['upload_id']);
				if (array_key_exists('upload_ip', $copy)) unset($copy['upload_ip']);
				if (array_key_exists('upload_thumbnail', $copy)) unset($copy['upload_thumbnail']);
				if (array_key_exists('upload_summary', $copy)) unset($copy['upload_summary']);
				if (array_key_exists('upload_description', $copy)) unset($copy['upload_description']);
				if (array_key_exists('upload_key', $copy)) unset($copy['upload_key']);
				if (array_key_exists('upload_size', $copy)) unset($copy['upload_size']);
				if (array_key_exists('id', $copy)) unset($copy['id']);
				if (array_key_exists('upload_date', $copy)) unset($copy['upload_date']);
				if (array_key_exists('upload_author', $copy)) unset($copy['upload_author']);
				if (array_key_exists('create_date', $copy)) unset($copy['create_date']);
				if (array_key_exists('modified_date', $copy)) unset($copy['modified_date']);

				$one_item['sync_hash'] = sha1(serialize($copy));
			}
		}
	}

	function CompareFiles($local_files, $remote_files)
	{
		$changed_files = array();

		//Get a list of changed hashes.  These are files that are either not on the server,
		//not on local, or have changed details.  The hash includes filesize and filename,
		//so we should be able to account for everything.
		$changed_files = array_udiff($local_files, $remote_files, array($this, 'FilesCompare'));

		//Now find new files (ones on local that aren't on the server)
		$new_files = array_udiff($changed_files, $remote_files, array($this, 'FilesCompare'));

		//TODO: Since changed files don't work on Uploads anyway, we're only looking
		//at new files (not on the remote server) for now.
		return $new_files;
	}

	function FilesCompare($a, $b)
	{
		$hash_a = $hash_b = '';

		if (is_object($a))
			$hash_a = $a->sync_hash;
		else
			$hash_a = $a['sync_hash'];

		if (is_object($b))
			$hash_b = $b->sync_hash;
		else
			$hash_b = $b['sync_hash'];

		return strcmp($hash_a, $hash_b);
	}

	function curl_get_file_contents($URL, $username = '', $password = '')
	{
		$c = curl_init();
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_URL, $URL);
		if ($username != '' && $password != '')
		{
			curl_setopt($c, CURLOPT_USERPWD, "$username:$password");
			curl_setopt($c, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		}
		$contents = curl_exec($c);
		curl_close($c);

		if ($contents) return $contents;
		else return FALSE;
	}

	function CopyFileOverWire($url, $upload_id, $username = '', $password = '', $remote_id = -1, $md5 = '')
	{
		$log = array();

		global $gCms;
		$db = $gCms->GetDb();
		$config = $gCms->GetConfig();

		$query = "select u.*, c.* from " . cms_db_prefix() . "module_uploads u inner join " . cms_db_prefix() . "module_uploads_categories c on c.upload_category_id = u.upload_category_id where upload_id = ?";
		$row = $db->GetRow($query, array($upload_id));

		$uploads = $this->GetModuleInstance("Uploads");
		if ($uploads)
		{
			$path_to_file = $config['uploads_path'] . DIRECTORY_SEPARATOR . $row['upload_category_path'] . DIRECTORY_SEPARATOR . $row['upload_name'];
			if (is_file($path_to_file))
			{
				$c = curl_init();

				if (!isset($md5) || empty($md5) || $md5 != md5_file($path_to_file))
					$data = array('file' => '@' . $path_to_file);

				foreach ($row as $k=>$v)
				{
					$data[$k] = $v;
				}

				$data['custom_fields'] = serialize($db->GetAll("select fv.*, fd.* from cms_module_uploads_fieldvals fv inner join cms_module_uploads_fielddefs fd ON fd.id = fv.fld_id where fv.upload_id = ?", array($upload_id)));

				if ($remote_id > -1)
					$data['upload_id'] = $remote_id;

				curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($c, CURLOPT_URL, $url);
				curl_setopt($c, CURLOPT_POST, 1);
				curl_setopt($c, CURLOPT_POSTFIELDS, $data);
				if ($username != '' && $password != '')
				{
					curl_setopt($c, CURLOPT_USERPWD, "$username:$password");
					curl_setopt($c, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
				}
				$contents = curl_exec($c);

				if (!$contents)
				{
					$email_address = $uploads->GetPreference('email_address', '');
					if ($email_address != '')
					{
						$this->EmailError($email_address, $row['upload_name'], curl_error($c));
					}
				}

				curl_close($c);

				return $contents;
			}
		}
	}

	function EmailError($email_address, $filename, $error_message)
	{
		$cmsmailer = $this->GetModuleInstance('CMSMailer');
		if ($cmsmailer)
		{
			$cmsmailer->AddAddress($email_address);
			$cmsmailer->SetBody('Problem File: ' . $filename . '<br />Error: ' . $error_message);
			$cmsmailer->IsHTML(true);
			$cmsmailer->SetSubject('Error Syncing Upload');
			$cmsmailer->Send();
		}
	}

	function AddToQueue($upload_id, $type)
	{
		global $gCms;
		$db = $gCms->GetDb();
		$prefix = cms_db_prefix();

		//Remove any old ones
		$this->RemoveFromQueue($upload_id);

		if ($type == 'edit')
		{
			$count = $db->GetOne("SELECT count(*) FROM {$prefix}module_uploads_sync WHERE upload_id = ? AND synced = 1", array($upload_id));
			if ($count == 0)
			{
				$type = 'add';
			}
		}

		$db->Execute("INSERT INTO {$prefix}module_uploads_sync (upload_id, change_type, synced, create_date, modified_date)
			VALUES (?,?,0,{$db->DBTimeStamp(time())},{$db->DBTimeStamp(time())})", array($upload_id, $type));
	}

	function RemoveFromQueue($upload_id, $all = false)
	{
		global $gCms;
		$db = $gCms->GetDb();
		$prefix = cms_db_prefix();

		if ($all == true)
			$db->Execute("DELETE FROM {$prefix}module_uploads_sync WHERE upload_id = ?", array($upload_id));
		else
			$db->Execute("DELETE FROM {$prefix}module_uploads_sync WHERE upload_id = ? and synced = 0", array($upload_id));
	}

	function DoEvent($originator, $eventname, &$params)
	{
		if ($originator == 'Uploads')
		{
			switch ($eventname)
			{
				case 'OnUpload':
					$this->AddToQueue($params['upload_id'], 'add');
					break;

				case 'OnEditUpload':
					$this->AddToQueue($params['upload_id'], 'edit');
					break;

				case 'OnRemove':
					$this->RemoveFromQueue($params['id'], true);
					break;
			}
		}
	}

} //end class
