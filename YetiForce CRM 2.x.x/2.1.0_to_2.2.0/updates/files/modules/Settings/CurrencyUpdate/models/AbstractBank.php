<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */

/**
 * Abstract class for connection to bank currency exchange rates
 */
abstract class AbstractBank
{
	/*
	 * Returns bank name
	 */
	abstract public function getName();
	
	/*
	 * Returns url sources from where exchange rates are taken from
	 */
	abstract public function getSource();
	
	/*
	 * Returns list of currencies supported by this bank
	 */
	abstract public function getSupportedCurrencies();
	
	/*
	 * Returns banks main currency 
	 */
	abstract public function getMainCurrencyCode();
	
	/*
	 * Fetch exchange rates
	 * @param <Array> $currencies - list of systems active currencies
	 * @param <Date> $date - date for which exchange is fetched
	 * @param <Boolean> $cron - if true then it is fired by server and crms currency conversion rates are updated 
	 */
	abstract public function getRates($currencies, $date, $cron=false);
	
}