<?php

class Yuansfer_SecurePayController extends Mage_Core_Controller_Front_Action
{
    public function redirectAction()
    {
        $this->getResponse()->setBody($this->getLayout()->createBlock('yuansfer/securepay_yuansferform')->toHtml());
    }

    public function ipnAction()
    {
        $data = $this->getRequest()->getParams();

        $this->log('Get request to IPN');
        $this->log(print_r($data, true));

        $this->processPayment($data);
    }

    public function callbackAction()
    {
        $data = $this->getRequest()->getParams();

        $this->log('Get request to callback');
        $this->log(print_r($data, true));

        //$this->processPayment($data);

        if (isset($data['status']) && $data['status'] === 'success') {
            $this->_redirect('checkout/onepage/success');
        } else {
            Mage::helper('checkout')->sendPaymentFailedEmail(
                Mage::getSingleton('checkout/session')->getQuote(),
                $this->__('Unable to place the order.')
            );
            Mage::getSingleton('checkout/session')->addError($this->__('Unable to place the order.'));
            $this->log('place order error');
            $this->_redirect('checkout/cart');
        }
    }

    protected function verifySig($data)
    {
        $debug = Mage::getStoreConfig('payment/yuansfer/yuansfer_mode');
        if ($debug) {
            $token = Mage::getStoreConfig('payment/yuansfer/yuansfer_test_apikey');
        } else {
            $token = Mage::getStoreConfig('payment/yuansfer/yuansfer_live_apikey');
        }
        $this->log('get ' . ($debug ? 'test' : 'live') . ' token: ' . $token);

        if (!isset($data['verifySign'])) {
            return false;
        }
        $verifySign = $data['verifySign'];

        unset($data['verifySign']);

        ksort($data, SORT_STRING);
        $str = '';
        foreach ($data as $k => $v) {
            $str .= $k . '=' . $v . '&';
        }
        $sig = md5($str . md5($token));

        $this->log('sig: ' . $sig . '; verify: ' . $verifySign);

        return $sig === $verifySign;
    }

    protected function findOrder($data)
    {
        if (!isset($data['reference'])) {
            return null;
        }

        $refs = explode('at', $data['reference']);
        //first item is order id
        if ($refs !== null && is_array($refs)) {
            $order_id = $refs[0];
        } else {
            $this->log('reference code invalid:' . $data['reference']);

            return null;
        }

        $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        if (!$order->getId()) {
            Mage::app()->getResponse()
                ->setHeader('HTTP/1.1', '503 Service Unavailable')
                ->sendResponse();
            exit;
        }

        $this->log('Find order id=' . $order->getId());

        return $order;
    }

    protected function processPayment($data)
    {
        if (!isset($data['status'], $data['reference']) || !$this->verifySig($data)) {
            Mage::app()->getResponse()
                ->setHeader('HTTP/1.1', '503 Service Unavailable')
                ->sendResponse();
            exit;
        }

        $order = $this->findOrder($data);

        if ($data['status'] === 'success') {
            $this->successIPN($order, $data);

            Mage::app()->getResponse()
                ->setBody('success');
        } else {
            $this->failIPN($order, $data);
        }
    }

    protected function successIPN($order, $data)
    {
        $payment = $order->getPayment();
        $amount = $data['amount'];
        $payment->setTransactionId($data['reference'])
            ->setCurrencyCode($order->getOrderCurrencyCode())
            ->setPreparedMessage('')
            ->setIsTransactionClosed(1)
            ->registerCaptureNotification($amount);
        $order->save();

        // notify customer
        $invoice = $payment->getCreatedInvoice();
        if ($invoice && !$order->getEmailSent()) {
            $order->queueNewOrderEmail()->addStatusHistoryComment(
                $this->__('Notified customer about invoice #%s.', $invoice->getIncrementId())
            )
                ->setIsCustomerNotified(true)
                ->save();
        }
    }

    protected function failIPN($order, $data)
    {
        $payment = $order->getPayment();

        $payment->setTransactionId($data['reference'])
            ->setNotificationResult(true)
            ->setIsTransactionClosed(true);
        if (!$order->isCanceled()) {
            $payment->registerPaymentReviewAction(Mage_Sales_Model_Order_Payment::REVIEW_ACTION_DENY, false);
        } else {

            $comment = $this->__('Transaction ID: "%s"', $data['reference']);
            $order->addStatusHistoryComment($comment, false);
        }

        $order->save();

    }


    protected function log($msg)
    {
        Mage::log("Yuansfer SecurePay controller - " . $msg);
    }


}
