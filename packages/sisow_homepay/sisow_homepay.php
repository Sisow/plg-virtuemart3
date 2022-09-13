<?php
defined('_JEXEC') or die('Restricted access');

if(!class_exists('Sisow'))
	jimport('sisow.base');

class plgVmPaymentSisow_Homepay extends SisowBase
{
	function __construct(& $subject, $config) {
		$this->paymentcode = 'homepay';
		$this->paymentname = 'ING Home\'Pay';
		$this->redirect = true;
		parent::__construct($subject, $config);
	}
}