<?php
defined('_JEXEC') or die('Restricted access');

if(!class_exists('Sisow'))
	jimport('sisow.base');

class plgVmPaymentSisow_Mistercash extends SisowBase
{
	function __construct(& $subject, $config) {
		$this->paymentcode = 'mistercash';
		$this->paymentname = 'MisterCash';
		$this->redirect = true;
		parent::__construct($subject, $config);
	}
}