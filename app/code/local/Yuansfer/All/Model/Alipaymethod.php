<?php

class Yuansfer_All_Model_Alipaymethod extends Yuansfer_All_Model_MethodAbstract {
  	protected $_code  = Yuansfer_All_Model_MethodAbstract::CODE_ALIPAY;
    protected $_formBlockType = 'yuansfer_all/securepay_form';
    protected $_infoBlockType = 'payment/info_cc';
    protected $_isInitializeNeeded      = true;
    protected $_canUseForMultishipping  = false;
    //protected $_isGateway               = true;
    protected $_canUseInternal          = false;
    //protected $_canUseCheckout          = true;
    
    
    public function isAvailable($quote = null)
    {
    	//does not work for wechar browser
		if(preg_match('/(micromessenger)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
			return false;
		}else
			return parent::isAvailable($quote);
	}



  	
}