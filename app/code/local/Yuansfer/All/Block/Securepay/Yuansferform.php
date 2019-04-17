<?php
require_once 'Yuansfer/All/Model/Requestor.php';
require_once 'Yuansfer/All/Model/Error/Base.php';


class Yuansfer_All_Block_Securepay_Yuansferform extends Mage_Core_Block_Abstract
{


    protected function _toHtml()
    {
        $debug = Mage::getStoreConfig('payment/yuansfer/yuansfer_mode');
        if ($debug) {
            $token = Mage::getStoreConfig('payment/yuansfer/yuansfer_test_apikey');
        } else {
            $token = Mage::getStoreConfig('payment/yuansfer/yuansfer_live_apikey');
        }

        $merchantNo = Mage::getStoreConfig('payment/yuansfer/yuansfer_merchant_no');
        $storeNo = Mage::getStoreConfig('payment/yuansfer/yuansfer_store_no');

        $sOrderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $oOrder = Mage::getModel('sales/order')->loadByIncrementId($sOrderId);

        $ipn = Mage::getUrl('yuansfer/securePay/ipn');
        // $callback = Mage::getUrl('yuansfer/securePay/callback', array(
        //     '_query' => array(
        //         'status' => '{status}',
        //         'amount' => '{amount}',
        //         'reference' => '{reference}',
        //         'note' => '{note}'
        //     )
        // ));
        $callback = Mage::getUrl('yuansfer/securePay/callback', array(
            '_query' => 'status={status}&amount={amount}&reference={reference}&note={note}'            
        ));

        $methodCode = $oOrder->getPayment()->getMethod();
        $this->log('current method=' . $methodCode);
        $vendor = '';
        if ($methodCode == Yuansfer_All_Model_MethodAbstract::CODE_ALIPAY) {
            $vendor = 'alipay';
        } elseif ($methodCode == Yuansfer_All_Model_MethodAbstract::CODE_UNIONPAY) {
            $vendor = 'unionpay';
        } elseif ($methodCode == Yuansfer_All_Model_MethodAbstract::CODE_WECHATPAY) {
            $vendor = 'wechatpay';
        }
        $requestor = new Requestor();
        $requestor->setDebug($debug);
        $ret = $requestor->getSecureForm(
            $merchantNo,
            $storeNo,
            $token,
            $vendor,
            $oOrder,
            $ipn,
            $callback
        );

        $this->log('return from yuansfer:' . print_r($ret,true));

        return $ret;
    }


    protected function log($msg)
    {
        Mage::log("Yuansfer SecurePay form - " . $msg);
    }


}
