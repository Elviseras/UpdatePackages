<?php
/* +*******************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ******************************************************************************* */
require_once 'config/config.php';
require_once 'config/debug.php';
require_once 'config/performance.php';
require_once 'include/Webservices/Retrieve.php';
require_once 'include/Webservices/Create.php';
require_once 'include/Webservices/Delete.php';
require_once 'include/Webservices/DescribeObject.php';
require_once 'include/Loader.php';
require_once('include/ConfigUtils.php');
vimport('include.runtime.Globals');
vimport('include.runtime.BaseModel');

function vtws_convertlead($entityvalues, $user)
{
	$adb = PearDatabase::getInstance();
	$log = vglobal('log');
	$log->debug('Start ' . __CLASS__ . ':' . __FUNCTION__);
	if (empty($entityvalues['assignedTo'])) {
		$entityvalues['assignedTo'] = vtws_getWebserviceEntityId('Users', $user->id);
	}
	if (empty($entityvalues['transferRelatedRecordsTo'])) {
		$entityvalues['transferRelatedRecordsTo'] = 'Accounts';
	}


	$leadObject = VtigerWebserviceObject::fromName($adb, 'Leads');
	$handlerPath = $leadObject->getHandlerPath();
	$handlerClass = $leadObject->getHandlerClass();

	require_once $handlerPath;

	$leadHandler = new $handlerClass($leadObject, $user, $adb, $log);


	$leadInfo = vtws_retrieve($entityvalues['leadId'], $user);
	$sql = "select converted from vtiger_leaddetails where converted = 1 and leadid=?";
	$leadIdComponents = vtws_getIdComponents($entityvalues['leadId']);
	$result = $adb->pquery($sql, array($leadIdComponents[1]));
	if ($result === false) {
		$log->error('Error converting a lead: ' . vtws_getWebserviceTranslatedString('LBL_' . WebServiceErrorCode::$DATABASEQUERYERROR));
		throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR, vtws_getWebserviceTranslatedString('LBL_' .
			WebServiceErrorCode::$DATABASEQUERYERROR));
	}
	$rowCount = $adb->num_rows($result);
	if ($rowCount > 0) {
		$log->error('Error converting a lead: Lead is already converted');
		throw new WebServiceException(WebServiceErrorCode::$LEAD_ALREADY_CONVERTED, "Lead is already converted");
	}

	require_once("include/events/include.inc");
	$em = new VTEventsManager($adb);

	// Initialize Event trigger cache
	$em->initTriggerCache();
	$entityData = VTEntityData::fromEntityId($adb, $leadIdComponents[1]);
	$em->triggerEvent('entity.convertlead.before', [$entityvalues, $user, $leadInfo]);

	$entityIds = [];
	$availableModules = ['Accounts', 'Contacts', 'Potentials'];

	if (!(($entityvalues['entities']['Accounts']['create']) || ($entityvalues['entities']['Contacts']['create']))) {
		return null;
	}

	foreach ($availableModules as $entityName) {
		if ($entityvalues['entities'][$entityName]['create']) {
			$entityvalue = $entityvalues['entities'][$entityName];
			$entityObject = VtigerWebserviceObject::fromName($adb, $entityvalue['name']);
			$handlerPath = $entityObject->getHandlerPath();
			$handlerClass = $entityObject->getHandlerClass();

			require_once $handlerPath;

			$entityHandler = new $handlerClass($entityObject, $user, $adb, $log);

			$entityObjectValues = array();
			$entityObjectValues['assigned_user_id'] = $entityvalues['assignedTo'];
			$entityObjectValues = vtws_populateConvertLeadEntities($entityvalue, $entityObjectValues, $entityHandler, $leadHandler, $leadInfo);

			//update potential related to property
			if ($entityvalue['name'] == 'Potentials') {
				if (!empty($entityIds['Accounts'])) {
					$entityObjectValues['related_to'] = $entityIds['Accounts'];
				}
				if (!empty($entityIds['Contacts'])) {
					$entityObjectValues['contact_id'] = $entityIds['Contacts'];
				}
			}

			//update the contacts relation
			if ($entityvalue['name'] == 'Contacts') {
				if (!empty($entityIds['Accounts'])) {
					$entityObjectValues['parent_id'] = $entityIds['Accounts'];
				}
			}

			try {
				$create = true;
				if ($entityvalue['name'] == 'Accounts' && $entityvalue['convert_to_id'] && is_int($entityvalue['convert_to_id'])) {
					$entityIds[$entityName] = vtws_getWebserviceEntityId('Accounts', $entityvalue['convert_to_id']);
					$create = false;
				}
				if ($create) {
					$entityRecord = vtws_create($entityvalue['name'], $entityObjectValues, $user);
					$entityIds[$entityName] = $entityRecord['id'];
				}
			} catch (Exception $e) {
				$log->error('Error converting a lead: ' . $e->getMessage());
				throw new WebServiceException(WebServiceErrorCode::$UNKNOWNOPERATION, $e->getMessage() . ' : ' . $entityvalue['name']);
			}
		}
	}


	try {
		$accountIdComponents = vtws_getIdComponents($entityIds['Accounts']);
		$accountId = $accountIdComponents[1];

		$contactIdComponents = vtws_getIdComponents($entityIds['Contacts']);
		$contactId = $contactIdComponents[1];

		if (!empty($accountId) && !empty($contactId) && !empty($entityIds['Potentials'])) {
			$potentialIdComponents = vtws_getIdComponents($entityIds['Potentials']);
			$potentialId = $potentialIdComponents[1];
			$sql = "insert into vtiger_contpotentialrel values(?,?)";
			$result = $adb->pquery($sql, array($contactId, $potentialIdComponents[1]));
			if ($result === false) {
				throw new WebServiceException(WebServiceErrorCode::$FAILED_TO_CREATE_RELATION, "Failed to related Contact with the Potential");
			}
		}

		$transfered = vtws_convertLeadTransferHandler($leadIdComponents, $entityIds, $entityvalues);

		$relatedIdComponents = vtws_getIdComponents($entityIds[$entityvalues['transferRelatedRecordsTo']]);
		vtws_getRelatedActivities($leadIdComponents[1], $accountId, $contactId, $relatedIdComponents[1]);
		vtws_updateConvertLeadStatus($entityIds, $entityvalues['leadId'], $user);
		if ($em) {
			$em->triggerEvent('entity.convertlead.after', [$entityvalues, $user, $leadInfo, $entityIds]);
		}
	} catch (Exception $e) {
		$log->error('Error converting a lead: ' . $e->getMessage());
		foreach ($entityIds as $entity => $id) {
			vtws_delete($id, $user);
		}
		return null;
	}
	$log->debug('End ' . __CLASS__ . ':' . __FUNCTION__);
	return $entityIds;
}
/*
 * populate the entity fields with the lead info.
 * if mandatory field is not provided populate with '????'
 * returns the entity array.
 */

function vtws_populateConvertLeadEntities($entityvalue, $entity, $entityHandler, $leadHandler, $leadinfo)
{
	$adb = PearDatabase::getInstance();
	$log = vglobal('log');
	$column;
	$entityName = $entityvalue['name'];
	$sql = "SELECT * FROM vtiger_convertleadmapping";
	$result = $adb->pquery($sql, array());
	if ($adb->num_rows($result)) {
		switch ($entityName) {
			case 'Accounts':$column = 'accountfid';
				break;
			case 'Contacts':$column = 'contactfid';
				break;
			case 'Potentials':$column = 'potentialfid';
				break;
			default:$column = 'leadfid';
				break;
		}

		$leadFields = $leadHandler->getMeta()->getModuleFields();
		$entityFields = $entityHandler->getMeta()->getModuleFields();
		$row = $adb->fetch_array($result);
		$count = 1;
		do {
			$entityField = vtws_getFieldfromFieldId($row[$column], $entityFields);
			if ($entityField == null) {
				//user doesn't have access so continue.TODO update even if user doesn't have access
				continue;
			}
			$leadField = vtws_getFieldfromFieldId($row['leadfid'], $leadFields);
			if ($leadField == null) {
				//user doesn't have access so continue.TODO update even if user doesn't have access
				continue;
			}
			$leadFieldName = $leadField->getFieldName();
			$entityFieldName = $entityField->getFieldName();
			$entity[$entityFieldName] = $leadinfo[$leadFieldName];
			$count++;
		} while ($row = $adb->fetch_array($result));

		foreach ($entityvalue as $fieldname => $fieldvalue) {
			if (!empty($fieldvalue)) {
				$entity[$fieldname] = $fieldvalue;
			}
		}

		$entity = vtws_validateConvertLeadEntityMandatoryValues($entity, $entityHandler, $leadinfo, $entityName);
	}
	return $entity;
}

function vtws_validateConvertLeadEntityMandatoryValues($entity, $entityHandler, $leadinfo, $module)
{

	$mandatoryFields = $entityHandler->getMeta()->getMandatoryFields();
	foreach ($mandatoryFields as $field) {
		if (empty($entity[$field])) {
			$fieldInfo = vtws_getConvertLeadFieldInfo($module, $field);
			if (($fieldInfo['type']['name'] == 'picklist' || $fieldInfo['type']['name'] == 'multipicklist' || $fieldInfo['type']['name'] == 'date' || $fieldInfo['type']['name'] == 'datetime') && ($fieldInfo['editable'] == true)) {
				$entity[$field] = $fieldInfo['default'];
			} else {
				$entity[$field] = '????';
			}
		}
	}
	return $entity;
}

function vtws_getConvertLeadFieldInfo($module, $fieldname)
{
	$adb = PearDatabase::getInstance();
	$log = vglobal('log');
	$describe = vtws_describe($module, vglobal('current_user'));
	foreach ($describe['fields'] as $index => $fieldInfo) {
		if ($fieldInfo['name'] == $fieldname) {
			return $fieldInfo;
		}
	}
	return false;
}

//function to handle the transferring of related records for lead
function vtws_convertLeadTransferHandler($leadIdComponents, $entityIds, $entityvalues)
{
	try {
		$entityidComponents = vtws_getIdComponents($entityIds[$entityvalues['transferRelatedRecordsTo']]);
		vtws_transferLeadRelatedRecords($leadIdComponents[1], $entityidComponents[1], $entityvalues['transferRelatedRecordsTo']);
	} catch (Exception $e) {
		return false;
	}
	return true;
}

function vtws_updateConvertLeadStatus($entityIds, $leadId, $user)
{
	$adb = PearDatabase::getInstance();
	$log = vglobal('log');
	$leadIdComponents = vtws_getIdComponents($leadId);
	if ($entityIds['Accounts'] != '' || $entityIds['Contacts'] != '') {
		$sql = "UPDATE vtiger_leaddetails SET converted = 1 where leadid=?";
		$result = $adb->pquery($sql, array($leadIdComponents[1]));
		if ($result === false) {
			throw new WebServiceException(WebServiceErrorCode::$FAILED_TO_MARK_CONVERTED, "Failed mark lead converted");
		}
		//updating the campaign-lead relation --Minnie
		$sql = "DELETE FROM vtiger_campaignleadrel WHERE leadid=?";
		$adb->pquery($sql, array($leadIdComponents[1]));

		$sql = "DELETE FROM vtiger_tracker WHERE item_id=?";
		$adb->pquery($sql, array($leadIdComponents[1]));

		//update the modifiedtime and modified by information for the record
		$leadModifiedTime = $adb->formatDate(date('Y-m-d H:i:s'), true);
		$crmentityUpdateSql = "UPDATE vtiger_crmentity SET modifiedtime=?, modifiedby=? WHERE crmid=?";
		$adb->pquery($crmentityUpdateSql, array($leadModifiedTime, $user->id, $leadIdComponents[1]));
	}
	$moduleArray = array('Accounts', 'Contacts', 'Potentials');

	foreach ($moduleArray as $module) {
		if (!empty($entityIds[$module])) {
			$idComponents = vtws_getIdComponents($entityIds[$module]);
			$id = $idComponents[1];
			$webserviceModule = vtws_getModuleHandlerFromName($module, $user);
			$meta = $webserviceModule->getMeta();
			$fields = $meta->getModuleFields();
			$field = $fields['isconvertedfromlead'];
			$tablename = $field->getTableName();
			$tableList = $meta->getEntityTableIndexList();
			$tableIndex = $tableList[$tablename];
			$adb->pquery("UPDATE $tablename SET isconvertedfromlead = ? WHERE $tableIndex = ?", array(1, $id));
		}
	}
}
