<?php

/**
 * Get modules list action class
 * @package YetiForce.WebserviceAction
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class API_Base_GetModulesList extends BaseAction
{

	protected $requestMethod = 'GET';

	public function getModulesList()
	{
		$modules = ['HelpDesk' => 'HelpDesk','Accounts' => 'Accounts'];
		
		return $modules;
	}
}
