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

} //end class
?>
