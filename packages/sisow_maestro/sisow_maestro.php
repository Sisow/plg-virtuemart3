<?php
defined('_JEXEC') or die('Restricted access');

if(!class_exists('Sisow'))
	jimport('sisow.base');

class plgVmPaymentSisow_Maestro extends SisowBase
{
	function __construct(& $subject, $config) {
		$this->paymentcode = 'maestro';
		$this->paymentname = 'Maestro';
		$this->redirect = true;
		parent::__construct($subject, $config);
	}
}