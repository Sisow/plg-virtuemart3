<?php
defined('_JEXEC') or die('Restricted access');

if(!class_exists('Sisow'))
	jimport('sisow.base');

class plgVmPaymentSisow_Ebill extends SisowBase
{
	function __construct(& $subject, $config) {
		$this->paymentcode = 'ebill';
		$this->paymentname = 'Ebill';
		$this->redirect = false;
		parent::__construct($subject, $config);
	}
}