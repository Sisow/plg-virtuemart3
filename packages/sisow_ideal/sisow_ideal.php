<?php
defined('_JEXEC') or die('Restricted access');

if(!class_exists('Sisow'))
	jimport('sisow.base');

class plgVmPaymentSisow_Ideal extends SisowBase
{
	function __construct(& $subject, $config) {
		$this->paymentcode = 'ideal';
		$this->paymentname = 'iDEAL';
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
		
		$html = array();
		foreach ($this->methods as $this->_currentMethod) {
			if ($this->checkConditions($cart, $this->_currentMethod, $cart->cartPrices)) {		
				$cartPrices=$cart->cartPrices;
				$methodSalesPrice = $this->setCartPrices($cart, $cartPrices, $this->_currentMethod);
				$this->_currentMethod->$method_name = $this->renderPluginName($this->_currentMethod,'checkout');
				$html = $this->getPluginHtml($this->_currentMethod, $selected, $methodSalesPrice);
				
				//load value
				$mainframe =& JFactory::getApplication();
				$issuerid = $mainframe->getUserState( "issuerid" );

				$sisow = new Sisow($this->_currentMethod->sisow_merchantid, $this->_currentMethod->sisow_merchantkey, $this->_currentMethod->sisow_shopid);
				$banks = array();
				$sisow->DirectoryRequest($banks, false, $this->_currentMethod->sisow_testmode == '1' ? true : false);
				
				$html_issuerdropdown = '';
				foreach($banks as $k => $v)
				{
					$selected = $k == $issuerid ? 'selected' : '';
					$html_issuerdropdown .= '<option value="' . $k  . '" ' . $selected . '>' . $v . '</option>';
				}
				
				$html .= '<br />
							<span class="vmpayment_cardinfo">
								<table border="0" cellspacing="0" cellpadding="2" width="100%">
									<tr valign="top">
										<td>
											Veilig betalen met iDEAL.<br/>
											<select name="sisow_issuer" id="sisow_issuer">
											<option value="">Kies uw bank...</option>
											'.$html_issuerdropdown.'
											</select>
										</td>
									</tr>
								</table>
							</span>';
				
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
		$issuerid = vRequest::getVar('sisow_issuer');

		if (empty($issuerid)) {
			vmInfo('VMPAYMENT_SISOW_IDEAL_SELECT_BANK');
			return false;
		}
		// STEP 3. Save in session
		$mainframe =& JFactory::getApplication();
		$mainframe->setUserState( "issuerid", $issuerid);

		return true;
	}
}