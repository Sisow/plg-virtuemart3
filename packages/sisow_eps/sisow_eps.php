<?php
defined('_JEXEC') or die('Restricted access');

if(!class_exists('Sisow'))
	jimport('sisow.base');

class plgVmPaymentSisow_Eps extends SisowBase
{
	function __construct(& $subject, $config) {
		$this->paymentcode = 'eps';
		$this->paymentname = 'EPS';
		$this->redirect = true;
		parent::__construct($subject, $config);
	}
	
	function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected = 0, &$htmlIn) {

		if ($this->getPluginMethods($cart->vendorId) === 0) {
			if (empty($this->_name)) {
				$app = JFactory::getApplication();
				$app->enqueueMessage(vmText::_('COM_VIRTUEMART_CART_NO_' . strtoupper($this->_psType)));
				return false;
			} else {
				return false;
			}
		}
		$htmla = array();
		$html = '';
		$method_name = $this->_psType . '_name';
		vmdebug('methods', $this->methods);
		VmConfig::loadJLang('com_virtuemart');
		$currency = CurrencyDisplay::getInstance();
		
		$document = JFactory::getDocument();
		//$document->addScript('https://bankauswahl.giropay.de/eps/widget/v1/jquery-1.10.2.min.js');
		$document->addStyleSheet('https://bankauswahl.giropay.de/eps/widget/v1/style.css');
		$document->addScript('https://www.sisow.nl/Sisow/scripts/giro-eps.js');
		
		$html = array();
		foreach ($this->methods as $this->_currentMethod) {
			if ($this->checkConditions($cart, $this->_currentMethod, $cart->cartPrices)) {
				$cartPrices=$cart->cartPrices;
				$methodSalesPrice = $this->setCartPrices($cart, $cartPrices, $this->_currentMethod);
				$this->_currentMethod->$method_name = $this->renderPluginName($this->_currentMethod,'checkout');
				$html = $this->getPluginHtml($this->_currentMethod, $selected, $methodSalesPrice);
				
				//load value
				$mainframe =& JFactory::getApplication();
				$bic = $mainframe->getUserState( "biceps" );
				
				$html .= '<br />
							<span class="vmpayment_cardinfo">
								<table border="0" cellspacing="0" cellpadding="2" width="100%">
									<tr valign="top">
										<td>
											Mit eps Online-Überweisung zahlen Sie einfach, schnell und sicher im Online-Banking Ihrer Bank. Im nächsten Schritt werden Sie direkt zum Online-Banking Ihrer Bank weitergeleitet, wo Sie die Zahlung durch Eingabe von PIN und TAN freigeben.<br/>
											Ihre Bank <input id="eps_widget" autocomplete="off" name="bic_eps" class="inputbox" value="' . $bic . '"/>
										</td>
									</tr>
								</table>
							</span>
							<script>
								( function($) {
									$(document).ready(function() {
										$(\'#eps_widget\').eps_widget({\'return\': \'bic\'});
									});
								})( jQuery );
							</script>';
				
				$htmla[] = $html;
			}
		}
		
		$htmlIn[] = $htmla;

		return TRUE;
	}
	
	public function plgVmOnSelectCheckPayment(VirtueMartCart $cart, &$msg) {
		if (!$this->selectedThisByMethodId($cart->virtuemart_paymentmethod_id)) {
			return NULL; // Another method was selected, do nothing
		}
		// 1. Step 1: check the data
		$bic = vRequest::getVar('bic_eps');

		if (empty($bic)) {
			vmInfo('VMPAYMENT_SISOW_EPS_NO_BIC');
			return false;
		}
		// STEP 3. Save in session
		$mainframe =& JFactory::getApplication();
		$mainframe->setUserState( "biceps", $bic);
		$mainframe->setUserState( "bicgiropay", '');

		return true;
	}
}