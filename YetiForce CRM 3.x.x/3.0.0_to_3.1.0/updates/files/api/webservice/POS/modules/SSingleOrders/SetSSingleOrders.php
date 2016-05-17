<?php

/**
 * Action for adding orders
 * @package YetiForce.WebserviceAction
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class API_SSingleOrders_SetSSingleOrders extends BaseAction
{

	protected $requestMethod = ['POST'];
	private $inventoryMapping = [
		'name' => 'id_product',
		'price' => 'price',
		'qty' => 'qty',
	];

	private function hasPermissionToStorage($storageId)
	{
		if (empty($storageId)) {
			return false;
		} else {
			$storageModel = Vtiger_Record_Model::getInstanceById($storageId, 'IStorages');
			return in_array($this->api->app['id'], explode(',', $storageModel->get('pos')));
		}
	}

	public function post()
	{
		vglobal('current_user', Users_Privileges_Model::getInstanceById($this->user['user_id']));
		$orders = func_get_args();
		$idsToReturn = [];
		foreach ($orders as $offer) {
			if ($this->hasPermissionToStorage($offer['storage'])) {
				$moduleName = 'SSingleOrders';
				$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
				$recordModel->set('subject', $offer['date'] . ' - ' .  $offer['brutto']);
				$recordModel->set('ssingleorders_status', $offer['status']);
				$recordModel->set('date_start', $offer['date']);
				$recordModel->set('pos', $this->api->app['id']);
				$recordModel->set('table', $offer['tableNo']);
				$recordModel->set('istoragesid', $offer['storage']);
				$recordModel->set('seat', $offer['seat']);
				$recordModel->set('sum_gross', $offer['brutto']);
				$recordModel->set('ssingleorders_source', 'PLL_POS');
				$recordModel->set('description', $offer['description']);
				$recordModel->set('accountid', $this->api->app['accounts_id']);
				$recordModel->set('assigned_user_id', $this->user['user_id']);
				$recordModel->set('mode', '');
				$countInventoryData = 0;
				$defaultCurrency = Vtiger_Functions::getDefaultCurrencyInfo()['id'];
				$inventory = Vtiger_InventoryField_Model::getInstance($moduleName);
				$fields = $inventory->getColumns();
				foreach ($offer['items'] as $rowInInventory) {
					$countInventoryData++;
					foreach ($fields as $columnName) {
						if ($columnName == 'total' || $columnName == 'gross' || $columnName == 'net') {
							AppRequest::set($columnName . $countInventoryData, $rowInInventory['qty'] * $rowInInventory['price']);
						} else {
							if (key_exists($columnName, $this->inventoryMapping)) {
								AppRequest::set($columnName . $countInventoryData, $rowInInventory[$this->inventoryMapping[$columnName]]);
							}
						}
					}
					AppRequest::set('seq' . $countInventoryData, $countInventoryData);
					AppRequest::set('currency' . $countInventoryData, $defaultCurrency);
				}
				AppRequest::set('inventoryItemsNo', $countInventoryData);
				$recordModel->save();
				$idsToReturn[$offer['id']] = $recordModel->getid();
			} else {
				throw new APIException('ERR_NO_PERMISSION_TO_STORAGE', 500);
			}
		}
		return $idsToReturn;
	}
}
