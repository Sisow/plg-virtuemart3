<?php
defined('_JEXEC') or die('Restricted access');

if(!class_exists('Sisow'))
	jimport('sisow.base');

class plgVmPaymentSisow_Cbc extends SisowBase
{
	function __construct(& $subject, $config) {
		$this->paymentcode = 'cbc';
		$this->paymentname = 'CBC Pay button';
		$this->redirect = true;
		parent::__construct($subject, $config);
	}
}