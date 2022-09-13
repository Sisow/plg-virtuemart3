<?
defined('_JEXEC') or die('Restricted access');

if(!class_exists('Sisow'))
	jimport('sisow.base');

class plgVmPaymentSisow_Focum extends SisowBase
{
	function __construct(& $subject, $config) {
		$this->paymentcode = 'focum';
		$this->paymentname = 'Focum Achteraf Betalen';
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
				
				$dag = '<select name="sisow_dag" style="width:100px">';
				$dag .= '<option value="">Dag</option>';
				for($i = 1;$i < 32; $i++)
				{
					$selected = $mainframe->getUserState( "dag") == sprintf("%02d", $i) ? 'selected' : '';
					$dag .= '<option value="'.sprintf("%02d", $i).'" ' . $selected . '>'.$i.'</option>';
				}
				$dag .= '</select>';
				
				$months = array('' => 'Maand', '01' => 'Januari', '02' => 'Februari', '03' => 'Maart', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Augustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'December');
				
				$maand = '<select name="sisow_maand" style="width:100px">';
				foreach($months as $k => $v)
				{
					$selected = $mainframe->getUserState( "maand") == $k ? 'selected' : '';
					$maand .= '<option value="'.$k.'" ' . $selected . '>'.$v.'</option>';
				}
				$maand .= '</select>';
				
				$jaar = '<select name="sisow_jaar" style="width:100px">';
				$jaar .= '<option value="">Jaar</option>';
				for($i = date("Y")-18;$i > date("Y") - 110; $i--)
				{
					$selected = $mainframe->getUserState( "jaar") == $i ? 'selected' : '';
					$jaar .= '<option value="'.$i.'" ' . $selected . '>'.$i.'</option>';
				}
				$jaar .= '</select>';
				
				$aanhef = 	'<select name="sisow_gender">
								<option value="">Geslacht</option>
								'.$male.$female.'
							</select>';
				$voorletters = '<input type="text" name="sisow_initials" id="sisow_jaar" size="6" value="'.$mainframe->getUserState( "initials").'"/>';	
				$iban = '<input type="text" name="sisow_iban" id="sisow_iban" size="20" value="'.$mainframe->getUserState( "iban").'"/>';	

				$html .= '<br />
							<span class="vmpayment_cardinfo">
								<table border="0" cellspacing="0" cellpadding="2" width="100%">
									<tr valign="top">
										<td>
											Focum Achteraf Betalen is een betaalmogelijkheid waarbij niet direct betaald hoeft te worden. De betalingstermijn is 14 dagen en de factuur zal worden verzonden met de verzending. <br/>
											Om te betalen met Focum Achteraf Betalen moet u minimaal 18 jaar oud zijn. <br/>
											Er wordt een credit check uitgevoerd op het moment van aankoop.<br/><br/>
											Aanhef:&nbsp '.$aanhef.'<br/>
											Voorletters:&nbsp'.$voorletters.' <br/>
											IBAN:&nbsp'.$iban.' <br/>
											Geboortedatum:<br/>'. $dag.'&nbsp&nbsp' . $maand . '&nbsp&nbsp' . $jaar . '
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
		$gender = vRequest::getVar('sisow_gender');
		$initials = vRequest::getVar('sisow_initials');
		$iban = vRequest::getVar('sisow_iban');
		$dag = vRequest::getVar('sisow_dag');
		$maand = vRequest::getVar('sisow_maand');
		$jaar = vRequest::getVar('sisow_jaar');

		if (empty($gender)) {
			vmInfo('VMPAYMENT_SISOW_FOCUM_NO_GENDER');
			return false;
		}
		else if (empty($initials)) {
			vmInfo('VMPAYMENT_SISOW_FOCUM_NO_INITIALS');
			return false;
		}
		else if (empty($iban)) {
			vmInfo('VMPAYMENT_SISOW_FOCUM_NO_IBAN');
			return false;
		}
		else if (empty($dag) || empty($maand) || empty($jaar)) {
			vmInfo('VMPAYMENT_SISOW_FOCUM_NO_BIRTHDAY');
			return false;
		}
		// STEP 3. Save in session
		$mainframe =& JFactory::getApplication();
		$mainframe->setUserState( "gender", $gender);
		$mainframe->setUserState( "initials", $initials);
		$mainframe->setUserState( "iban", $iban);
		$mainframe->setUserState( "dag", $dag);
		$mainframe->setUserState( "maand", $maand);
		$mainframe->setUserState( "jaar", $jaar);

		return true;
	}
}