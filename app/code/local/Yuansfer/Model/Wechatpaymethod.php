<?php

class Yuansfer_Model_Wechatpaymethod extends Yuansfer_Model_MethodAbstract {
  	protected $_code  = Yuansfer_Model_MethodAbstract::CODE_WECHATPAY;
    protected $_formBlockType = 'yuansfer/securepay_form';
    protected $_infoBlockType = 'payment/info_cc';
    protected $_isInitializeNeeded      = true;
    protected $_canUseForMultishipping  = false;
    //protected $_isGateway               = true;
    protected $_canUseInternal          = false;
    //protected $_canUseCheckout          = true;
}