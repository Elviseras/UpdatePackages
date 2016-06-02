<?php

/**
 * Get modules list action class
 * @package YetiForce.WebserviceAction
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class API_Products_GetProducts extends BaseAction
{

	protected $requestMethod = ['GET'];
	private $moduleName = 'Products';

	private function getTemplate()
	{
		$moduleId = Vtiger_Functions::getModuleId($this->moduleName);
		$db = PearDatabase::getInstance();
		$query = 'SELECT templateid FROM vtiger_trees_templates WHERE module = ?';
		return $db->getSingleValue($db->pquery($query, [$moduleId]));
	}

	private function getCategoryName($categoryId)
	{
		static $categoryCache = [];
		if (!empty($categoryCache[$categoryId])) {
			return $categoryCache[$categoryId];
		}
		$db = PearDatabase::getInstance();
		$result = $db->pquery('SELECT name FROM vtiger_trees_templates_data WHERE templateid = ? AND tree = ?', [$this->getTemplate(), $categoryId]);
		$categoryName = $db->getSingleValue($result);
		$categoryCache[$categoryId] = $categoryName;
		return $categoryName;
	}

	private function getInfo($recordModel)
	{
		$image = $recordModel->getImageDetails();
		$imagesUrl = '';
		foreach ($image as $img) {
			$imagesUrl[] = '/Products/GetImage/' . $img['id'];
		}
		$recordModel->set('categoryName', $this->getCategoryName($recordModel->get('pscategory')));
		$recordModel->set('imageUrl', $imagesUrl);
	}

	public function get()
	{
		$db = PearDatabase::getInstance();
		$query = 'SELECT vtiger_products.productname,
					vtiger_crmentity.description,
					vtiger_products.pscategory,
					vtiger_products.productid,
					vtiger_products.pos,
					vtiger_products.unit_price,
					vtiger_products.imagename
					FROM vtiger_products
					INNER JOIN vtiger_crmentity ON  vtiger_products.productid = vtiger_crmentity.crmid
					WHERE vtiger_crmentity.deleted = ?
					AND vtiger_products.pos LIKE ?
					AND vtiger_products.discontinued = ?';
		$results = $db->pquery($query, [0, '%' . $this->api->app['id'] . '%', 1]);
		$records = [];
		while ($product = $db->getRow($results)) {
			$poses = explode(',', $product['pos']);
			if (in_array($this->api->app['id'], $poses)) {
				unset($product['pos']);
				$recordModel = Vtiger_Record_Model::getCleanInstance($this->moduleName);
				$recordModel->setData($product)->set('id', $product['productid']);
				$this->getInfo($recordModel);
				$records[$product['productid']] = $recordModel->getData();
			}
		}
		return $records;
	}
}
