<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */
require_once 'include/utils/utils.php';

class DateTimeField
{

	static protected $databaseTimeZone = null;
	protected $datetime;
	private static $cache = [];

	/**
	 *
	 * @param type $value
	 */
	public function __construct($value)
	{
		if (empty($value)) {
			$value = date("Y-m-d H:i:s");
		}
		$this->date = null;
		$this->time = null;
		$this->datetime = $value;
	}

	/** Function to set date values compatible to database (YY_MM_DD)
	 * @param $user -- value :: Type Users
	 * @returns $insert_date -- insert_date :: Type string
	 */
	function getDBInsertDateValue($user = null)
	{
		$log = vglobal('log');
		$log->debug('Start ' . __CLASS__ . ':' . __FUNCTION__ . '(' . $this->datetime . ')');
		$value = explode(' ', $this->datetime);
		if (count($value) == 2) {
			$value[0] = self::convertToUserFormat($value[0]);
		}
		$insert_date = '';
		if (!empty($value[1])) {
			$date = self::convertToDBTimeZone($this->datetime, $user);
			$insert_date = $date->format('Y-m-d');
		} else {
			$insert_date = self::convertToDBFormat($value[0]);
		}
		$log->debug('End ' . __CLASS__ . ':' . __FUNCTION__);
		return $insert_date;
	}

	/**
	 *
	 * @param Users $user
	 * @return String
	 */
	public function getDBInsertDateTimeValue($user = null)
	{
		$log = vglobal('log');
		$log->debug(__CLASS__ . ':' . __FUNCTION__);
		return $this->getDBInsertDateValue($user) . ' ' . $this->getDBInsertTimeValue($user);
	}

	public function getDisplayDateTimeValue($user = null)
	{
		$log = vglobal('log');
		$log->debug(__CLASS__ . ':' . __FUNCTION__);
		return $this->getDisplayDate($user) . ' ' . $this->getDisplayTime($user);
	}

	public function getFullcalenderDateTimevalue($user = null)
	{
		return $this->getDisplayDate($user) . ' ' . $this->getFullcalenderTime($user);
	}

	/**
	 *
	 * @global Users $current_user
	 * @param type $date
	 * @param Users $user
	 * @return type
	 */
	public static function convertToDBFormat($date, $user = null)
	{
		global $current_user, $log;
		$log->debug('Start ' . __CLASS__ . ':' . __FUNCTION__ . ' ' . serialize($date));
		if (empty($user)) {
			$user = $current_user;
		}

		$format = $current_user->date_format;
		if (empty($format)) {
			$format = 'yyyy-mm-dd';
		}
		$return = self::__convertToDBFormat($date, $format);
		$log->debug('End ' . __CLASS__ . ':' . __FUNCTION__);
		return $return;
	}

	/**
	 *
	 * @param type $date
	 * @param string $format
	 * @return string
	 */
	public static function __convertToDBFormat($date, $format)
	{
		$log = vglobal('log');
		$log->debug('Start ' . __CLASS__ . ':' . __FUNCTION__ . ' ' . serialize($date) . ' | ' . $format);
		if (empty($date)) {
			$log->debug('End ' . __CLASS__ . ':' . __FUNCTION__);
			return $date;
		}
		if ($format == '') {
			$format = 'yyyy-mm-dd';
		}
		$dbDate = '';
		switch ($format) {
			case 'dd-mm-yyyy': list($d, $m, $y) = explode('-', $date);
				break;
			case 'mm-dd-yyyy': list($m, $d, $y) = explode('-', $date);
				break;
			case 'yyyy-mm-dd': list($y, $m, $d) = explode('-', $date);
				break;
			case 'dd.mm.yyyy': list($d, $m, $y) = explode('.', $date);
				break;
			case 'mm.dd.yyyy': list($m, $d, $y) = explode('.', $date);
				break;
			case 'yyyy.mm.dd': list($y, $m, $d) = explode('.', $date);
				break;
			case 'dd/mm/yyyy': list($d, $m, $y) = explode('/', $date);
				break;
			case 'mm/dd/yyyy': list($m, $d, $y) = explode('/', $date);
				break;
			case 'yyyy/mm/dd': list($y, $m, $d) = explode('/', $date);
				break;
		}

		if (!$y || !$m || !$d) {
			if (strpos($date, '-') !== false) {
				$separator = '-';
			} elseif (strpos($date, '.') !== false) {
				$separator = '.';
			} elseif (strpos($date, '/') !== false) {
				$separator = '/';
			}
			$formatToConvert = str_replace(array('/', '.'), array('-', '-'), $format);
			$dateToConvert = str_replace($separator, '-', $date);
			switch ($formatToConvert) {
				case 'dd-mm-yyyy': list($d, $m, $y) = explode('-', $dateToConvert);
					break;
				case 'mm-dd-yyyy': list($m, $d, $y) = explode('-', $dateToConvert);
					break;
				case 'yyyy-mm-dd': list($y, $m, $d) = explode('-', $dateToConvert);
					break;
			}
			$dbDate = $y . '-' . $m . '-' . $d;
		} elseif (!$y && !$m && !$d) {
			$dbDate = '';
		} else {
			$dbDate = $y . '-' . $m . '-' . $d;
		}
		$log->debug('End ' . __CLASS__ . ':' . __FUNCTION__);
		return $dbDate;
	}

	/**
	 *
	 * @param Mixed $date
	 * @return Array
	 */
	public static function convertToInternalFormat($date)
	{
		if (!is_array($date)) {
			$date = explode(' ', $date);
		}
		return $date;
	}

	/**
	 *
	 * @global Users $current_user
	 * @param type $date
	 * @param Users $user
	 * @return type
	 */
	public static function convertToUserFormat($date, $user = null)
	{
		$current_user = vglobal('current_user');
		if (empty($user)) {
			$user = $current_user;
		}
		$format = $user->date_format;
		if (empty($format)) {
			$format = 'yyyy-mm-dd';
		}
		return self::__convertToUserFormat($date, $format);
	}

	/**
	 *
	 * @param type $date
	 * @param type $format
	 * @return type
	 */
	public static function __convertToUserFormat($date, $format)
	{
		$log = vglobal('log');
		$log->debug('Start ' . __CLASS__ . ':' . __FUNCTION__ . ' ' . serialize($date) . ' | ' . $format);
		$date = self::convertToInternalFormat($date);
		$separator = '-';
		if (strpos($date[0], '-') !== false) {
			$separator = '-';
		} elseif (strpos($date[0], '.') !== false) {
			$separator = '.';
		} elseif (strpos($date[0], '/') !== false) {
			$separator = '/';
		}
		list($y, $m, $d) = explode($separator, $date[0]);

		switch ($format) {
			case 'dd-mm-yyyy': $date[0] = $d . '-' . $m . '-' . $y;
				break;
			case 'mm-dd-yyyy': $date[0] = $m . '-' . $d . '-' . $y;
				break;
			case 'yyyy-mm-dd': $date[0] = $y . '-' . $m . '-' . $d;
				break;
			case 'dd.mm.yyyy': $date[0] = $d . '.' . $m . '.' . $y;
				break;
			case 'mm.dd.yyyy': $date[0] = $m . '.' . $d . '.' . $y;
				break;
			case 'yyyy.mm.dd': $date[0] = $y . '.' . $m . '.' . $d;
				break;
			case 'dd/mm/yyyy': $date[0] = $d . '/' . $m . '/' . $y;
				break;
			case 'mm/dd/yyyy': $date[0] = $m . '/' . $d . '/' . $y;
				break;
			case 'yyyy/mm/dd': $date[0] = $y . '/' . $m . '/' . $d;
				break;
		}

		if (isset($date[1]) && $date[1] != '') {
			$userDate = $date[0] . ' ' . $date[1];
		} else {
			$userDate = $date[0];
		}
		$log->debug('End ' . __CLASS__ . ':' . __FUNCTION__);
		return $userDate;
	}

	public static function getDayFromDate($date)
	{
		return date('l', strtotime($date));
	}

	/**
	 *
	 * @global Users $current_user
	 * @param type $value
	 * @param Users $user
	 */
	public static function convertToUserTimeZone($value, $user = null)
	{
		global $log, $current_user, $default_timezone;
		$log->debug('Start ' . __CLASS__ . ':' . __FUNCTION__ . "($value) method ...");
		if (empty($user)) {
			$user = $current_user;
		}
		$timeZone = $user->time_zone ? $user->time_zone : $default_timezone;
		$return = DateTimeField::convertTimeZone($value, self::getDBTimeZone(), $timeZone);
		$log->debug('End ' . __CLASS__ . ':' . __FUNCTION__);
		return $return;
	}

	/**
	 *
	 * @global Users $current_user
	 * @param type $value
	 * @param Users $user
	 */
	public static function convertToDBTimeZone($value, $user = null, $formatDate = true)
	{
		global $log, $current_user, $default_timezone;
		$log->debug('Start ' . __CLASS__ . ':' . __FUNCTION__ . "($value)");
		if (empty($user)) {
			$user = $current_user;
		}
		$timeZone = $user->time_zone ? $user->time_zone : $default_timezone;

		if ($formatDate) {
			$value = self::sanitizeDate($value, $user);
		}

		$return = DateTimeField::convertTimeZone($value, $timeZone, self::getDBTimeZone());
		$log->debug('End ' . __CLASS__ . ':' . __FUNCTION__);
		return $return;
	}

	/**
	 *
	 * @param type $time
	 * @param type $sourceTimeZoneName
	 * @param type $targetTimeZoneName
	 * @return DateTime
	 */
	public static function convertTimeZone($time, $sourceTimeZoneName, $targetTimeZoneName)
	{
		$log = vglobal('log');
		$log->debug('Start ' . __CLASS__ . ':' . __FUNCTION__ . "($time, $sourceTimeZoneName, $targetTimeZoneName)");
		// TODO Caching is causing problem in getting the right date time format in Calendar module.
		// Need to figure out the root cause for the problem. Till then, disabling caching.
		//if(empty(self::$cache[$time][$targetTimeZoneName])) {
		// create datetime object for given time in source timezone
		$sourceTimeZone = new DateTimeZone($sourceTimeZoneName);
		if ($time == '24:00')
			$time = '00:00';
		$current_user = vglobal('current_user');
		$format = $current_user->date_format;
		if (empty($format)) {
			$format = 'yyyy-mm-dd';
		}
		$time = str_replace(".", "-", $time);
		$time = str_replace("/", "-", $time);

		$myDateTime = new DateTime($time, $sourceTimeZone);

		// convert this to target timezone using the DateTimeZone object
		$targetTimeZone = new DateTimeZone($targetTimeZoneName);
		$myDateTime->setTimeZone($targetTimeZone);
		self::$cache[$time][$targetTimeZoneName] = $myDateTime;
		//}
		$myDateTime = self::$cache[$time][$targetTimeZoneName];
		$log->debug('End ' . __CLASS__ . ':' . __FUNCTION__);
		return $myDateTime;
	}

	/** Function to set timee values compatible to database (GMT)
	 * @param $user -- value :: Type Users
	 * @returns $insert_date -- insert_date :: Type string
	 */
	function getDBInsertTimeValue($user = null)
	{
		$log = vglobal('log');
		$log->debug('Start ' . __CLASS__ . ':' . __FUNCTION__ . '(' . $this->datetime . ')');
		$date = self::convertToDBTimeZone($this->datetime, $user);
		$log->debug('End ' . __CLASS__ . ':' . __FUNCTION__);
		return $date->format("H:i:s");
	}

	/**
	 * This function returns the date in user specified format.
	 * @global type $log
	 * @global Users $current_user
	 * @return string
	 */
	function getDisplayDate($user = null)
	{
		$date_value = explode(' ', $this->datetime);
		if (isset($date_value[1]) && $date_value[1] != '') {
			$date = self::convertToUserTimeZone($this->datetime, $user);
			$date_value = $date->format('Y-m-d');
		}

		$display_date = self::convertToUserFormat($date_value, $user);
		return $display_date;
	}

	function getDisplayTime($user = null)
	{
		$log = vglobal('log');
		$log->debug('Start ' . __CLASS__ . ':' . __FUNCTION__ . '(' . $this->datetime . ')');
		$date = self::convertToUserTimeZone($this->datetime, $user);
		$time = $date->format("H:i");

		//Convert time to user preferred value
		$userModel = Users_Privileges_Model::getCurrentUserModel();
		if ($userModel->get('hour_format') == '12') {
			$time = Vtiger_Time_UIType::getTimeValueInAMorPM($time);
		}
		$log->debug('End ' . __CLASS__ . ':' . __FUNCTION__);
		return $time;
	}

	function getFullcalenderTime($user = null)
	{
		global $log;
		$log->debug("Entering getDisplayTime(" . $this->datetime . ") method ...");
		$date = self::convertToUserTimeZone($this->datetime, $user);
		$time = $date->format("H:i:s");
		$log->debug("Exiting getDisplayTime method ...");
		return $time;
	}

	static function getDBTimeZone()
	{
		if (empty(self::$databaseTimeZone)) {
			$defaultTimeZone = date_default_timezone_get();
			if (empty($defaultTimeZone)) {
				$defaultTimeZone = 'UTC';
			}
			self::$databaseTimeZone = $defaultTimeZone;
		}
		return self::$databaseTimeZone;
	}

	static function getPHPDateFormat($user = null)
	{
		$current_user = vglobal('current_user');
		if (empty($user)) {
			$user = $current_user;
		}
		return str_replace(array('yyyy', 'mm', 'dd'), array('Y', 'm', 'd'), $user->date_format);
	}

	private static function sanitizeDate($value, $user)
	{
		$current_user = vglobal('current_user');
		if (empty($user)) {
			$user = $current_user;
		}
		if (strlen($value) < 8) {
			return $value;
		}

		$value = str_replace('T', ' ', $value);
		list($date, $time) = explode(' ', $value);
		if (!empty($date) && !in_array($time, ['AM', 'PM'])) {
			$date = self::__convertToDBFormat($date, $user->date_format);
			$value = $date;
			if (!empty($time)) {
				$value .= ' ' . rtrim($time);
			}
		}
		return $value;
	}
}
