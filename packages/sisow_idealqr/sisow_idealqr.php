<?php
defined('_JEXEC') or die('Restricted access');

if(!class_exists('Sisow'))
	jimport('sisow.base');

class plgVmPaymentSisow_Idealqr extends SisowBase
{
	function __construct(& $subject, $config) {
		$this->paymentcode = 'idealqr';
		$this->paymentname = 'iDEAL QR';
		$this->redirect = true;
		parent::__construct($subject, $config);
	}
}