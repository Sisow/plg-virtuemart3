<?php
defined('_JEXEC') or die('Restricted access');

if(!class_exists('Sisow'))
	jimport('sisow.base');

class plgVmPaymentSisow_Sofort extends SisowBase
{
	function __construct(& $subject, $config) {
		$this->paymentcode = 'sofort';
		$this->paymentname = 'SofortBanking';
		$this->redirect = true;
		parent::__construct($subject, $config);
	}
}