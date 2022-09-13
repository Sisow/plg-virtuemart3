<?
defined('_JEXEC') or die('Restricted access');

if(!class_exists('Sisow'))
	jimport('sisow.base');

class plgVmPaymentSisow_Afterpay extends SisowBase
{
	function __construct(& $subject, $config) {
		$this->paymentcode = 'afterpay';
		$this->paymentname = 'Afterpay';
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
				
				$selected = $mainframe->getUserState( "gender") == 'm' ? 'selected' : '';
				$male = '<option '.$selected.' value="m">De heer</option>';
				
				$selected = $mainframe->getUserState( "gender") == 'v' ? 'selected' : '';
				$female = '<option '.$selected.' value="v">Mevrouw</option>';
				
				$dag = '<select name="sisowafterpay_dag" style="width:100px">';
				$dag .= '<option value="">Dag</option>';
				for($i = 1;$i < 32; $i++)
				{
					$selected = $mainframe->getUserState( "dag") == sprintf("%02d", $i) ? 'selected' : '';
					$dag .= '<option value="'.sprintf("%02d", $i).'" ' . $selected . '>'.$i.'</option>';
				}
				$dag .= '</select>';
				
				$months = array('' => 'Maand', '01' => 'Januari', '02' => 'Februari', '03' => 'Maart', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Augustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'December');
				
				$maand = '<select name="sisowafterpay_maand" style="width:100px">';
				foreach($months as $k => $v)
				{
					$selected = $mainframe->getUserState( "maand") == $k ? 'selected' : '';
					$maand .= '<option value="'.$k.'" ' . $selected . '>'.$v.'</option>';
				}
				$maand .= '</select>';
				
				$jaar = '<select name="sisowafterpay_jaar" style="width:100px">';
				$jaar .= '<option value="">Jaar</option>';
				for($i = date("Y")-18;$i > date("Y") - 110; $i--)
				{
					$selected = $mainframe->getUserState( "jaar") == $i ? 'selected' : '';
					$jaar .= '<option value="'.$i.'" ' . $selected . '>'.$i.'</option>';
				}
				$jaar .= '</select>';
				
				$aanhef = 	'<select name="sisowafterpay_gender">
								<option value="">Geslacht</option>
								'.$male.$female.'
							</select>';
				$coc = '<input type="text" name="sisowafterpay_coc" id="sisow_coc" size="20" value="'.$mainframe->getUserState( "coc").'"/>';	

				$html .= '<br />
							<span class="vmpayment_cardinfo">
								<table border="0" cellspacing="0" cellpadding="2" width="100%">
									<tr valign="top">
										<td>
											Aanhef:&nbsp '.$aanhef.'<br/>
											Geboortedatum:<br/>'. $dag.'&nbsp&nbsp' . $maand . '&nbsp&nbsp' . $jaar . '<br/>
											KvK nummer:&nbsp'.$coc.' <small>Enkel verplicht voor B2B</small><br/>
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

	function plgVmConfirmedOrder($cart, $order) {

		if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($method->payment_element)) {
			return FALSE;
		}
		
		$this->errors = array();
		$mainframe = JFactory::getApplication ();
			
		if (empty($order['details']['ST']->phone_1) && empty($order['details']['BT']->phone_1) || empty($order['details']['ST']->phone_1)){					
			$this->errors[] = vmText::_('VMPAYMENT_SISOW_AFTERPAY_NO_PHONE_ERROR');			
		}	
		
		$gender = $mainframe->getUserState( "gender" );
		if (empty($gender)) {
			$this->errors[] = vmText::_('VMPAYMENT_SISOW_AFTERPAY_NO_GENDER');			
		}
		$dag = $mainframe->getUserState( "dag" );
		$maand = $mainframe->getUserState( "maand" );
		$jaar = $mainframe->getUserState( "jaar" );
		if (empty($dag) || empty($maand) || empty($jaar)) {
			$this->errors[] = vmText::_('VMPAYMENT_SISOW_AFTERPAY_NO_BIRTHDAY');
		}
		
		parent::plgVmConfirmedOrder($cart, $order);
	}
	
	public function plgVmOnSelectCheckPayment(VirtueMartCart $cart, &$msg) {
		if (!$this->selectedThisByMethodId($cart->virtuemart_paymentmethod_id)) {
			return NULL; // Another method was selected, do nothing
		}
		// 1. Step 1: check the data
		$gender = vRequest::getVar('sisowafterpay_gender');
		$coc = vRequest::getVar('sisowafterpay_coc');
		$dag = vRequest::getVar('sisowafterpay_dag');
		$maand = vRequest::getVar('sisowafterpay_maand');
		$jaar = vRequest::getVar('sisowafterpay_jaar');
	
		// STEP 3. Save in session
		$mainframe =& JFactory::getApplication();
		$mainframe->setUserState( "gender", $gender);
		$mainframe->setUserState( "coc", $coc);
		$mainframe->setUserState( "dag", $dag);
		$mainframe->setUserState( "maand", $maand);
		$mainframe->setUserState( "jaar", $jaar);

		return true;
	}
}