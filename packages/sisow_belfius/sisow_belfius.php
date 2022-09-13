<?php
defined('_JEXEC') or die('Restricted access');

if(!class_exists('Sisow'))
	jimport('sisow.base');

class plgVmPaymentSisow_Belfius extends SisowBase
{
	function __construct(& $subject, $config) {
		$this->paymentcode = 'belfius';
		$this->paymentname = 'Belfius Direct NET';
		$this->redirect = true;
		parent::__construct($subject, $config);
	}
}