<?php
defined('_JEXEC') or die('Restricted access');

if(!class_exists('Sisow'))
	jimport('sisow.base');

class plgVmPaymentSisow_Kbc extends SisowBase
{
	function __construct(& $subject, $config) {
		$this->paymentcode = 'kbc';
		$this->paymentname = 'KBC';
		$this->redirect = true;
		parent::__construct($subject, $config);
	}
}