<?php

class Yuansfer_All_Model_Unionpaymethod extends Yuansfer_All_Model_MethodAbstract {
  	protected $_code  = Yuansfer_All_Model_MethodAbstract::CODE_UNIONPAY;
    protected $_formBlockType = 'yuansfer_all/securepay_form';
    protected $_infoBlockType = 'payment/info_cc';
    protected $_isInitializeNeeded      = true;
    protected $_canUseForMultishipping  = false;
    //protected $_isGateway               = true;
    protected $_canUseInternal          = false;
    //protected $_canUseCheckout          = true;
}