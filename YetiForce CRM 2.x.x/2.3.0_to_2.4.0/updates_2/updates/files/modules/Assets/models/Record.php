<?php

/**
 * Record Class for Assets
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Assets_Record_Model extends Vtiger_Record_Model
{

	protected $privileges = ['editFieldByModal' => true];

	public function getFieldToEditByModal()
	{
		return [
			'addClass' => 'btn-danger',
			'iconClass' => 'glyphicon-modal-window',
			'listViewClass' => 'danger-color',
			'titleTag' => 'LBL_SET_RECORD_STATUS',
			'name' => 'assetstatus',
		];
	}
}
