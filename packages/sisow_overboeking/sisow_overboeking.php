<?php
defined('_JEXEC') or die('Restricted access');

if(!class_exists('Sisow'))
	jimport('sisow.base');

class plgVmPaymentSisow_Overboeking extends SisowBase
{
	function __construct(& $subject, $config) {
		$this->paymentcode = 'overboeking';
		$this->paymentname = 'Overboeking';
		$this->redirect = false;
		parent::__construct($subject, $config);
	}
}