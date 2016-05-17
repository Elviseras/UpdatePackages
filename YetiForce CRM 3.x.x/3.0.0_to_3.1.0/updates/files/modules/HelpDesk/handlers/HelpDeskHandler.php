<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

require_once 'modules/Emails/mail.php';

class HelpDeskHandler extends VTEventHandler
{

	function handleEvent($eventName, $entityData)
	{
		$adb = PearDatabase::getInstance();
		$log = LoggerManager::getInstance();

		if ($eventName == 'vtiger.entity.aftersave.final') {
			$moduleName = $entityData->getModuleName();
			if ($moduleName == 'HelpDesk') {
				$ticketId = $entityData->getId();
				$sql = 'UPDATE `vtiger_troubletickets` SET `from_portal` = 0 WHERE `ticketid` = ?';
				$adb->pquery($sql, [$ticketId]);
				HelpDesk_Record_Model::updateTicketRangeTimeField($entityData);
			}
		} else if ($eventName == 'vtiger.entity.link.after') {
			if (in_array($entityData['destinationModule'], ['Calendar', 'Events', 'Activity', 'ModComments']) && $entityData['sourceModule'] == 'HelpDesk') {
				HelpDesk_Record_Model::updateTicketRangeTimeField($entityData['entityData'], true);
			}
			if ($entityData['destinationModule'] == 'Contacts' && $entityData['sourceModule'] == 'HelpDesk' && isRecordExists($entityData['destinationRecordId'])) {
				$ticketId = $entityData['sourceRecordId'];
				$contactId = $entityData['destinationRecordId'];
				$log->debug("Entering HelpDeskHandler:vtiger.entity.link.after");

				$ticketRecord = Vtiger_Record_Model::getInstanceById($ticketId, 'HelpDesk');
				if ($ticketRecord->get('ticketstatus') == 'Closed')
					return true;

				$mail = false;
				if (isRecordExists($contactId)) {
					$contactRecord = Vtiger_Record_Model::getInstanceById($contactId, 'Contacts');
					if ($contactRecord->get('emailoptout') == 1) {
						$mail = $contactRecord->get('email');
					}
				}

				if ($mail) {
					$data = [
						'sysname' => 'NotifyContactOnTicketCreate',
						'to_email' => $mail,
						'module' => 'HelpDesk',
						'record' => $ticketId
					];

					$recordModel = Vtiger_Record_Model::getCleanInstance('OSSMailTemplates');
					if ($recordModel->sendMailFromTemplate($data)) {
						$log->debug('HelpDeskHandler:vtiger.entity.link.after');
						return true;
					}
				}

				$log->debug('HelpDeskHandler:vtiger.entity.link.after');
				return false;
			}
		}
	}
}
