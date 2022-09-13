<?php
defined('_JEXEC') or die('Restricted access');

if(!class_exists('Sisow'))
	jimport('sisow.base');

class plgVmPaymentSisow_Webshopgiftcard extends SisowBase
{
	function __construct(& $subject, $config) {
		$this->paymentcode = 'webshopgiftcard';
		$this->paymentname = 'Webshop Giftcard';
		$this->redirect = true;
		parent::__construct($subject, $config);
	}
}