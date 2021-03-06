<?php
require_once 'Yuansfer/All/Model/CurlClient.php';
require_once 'Yuansfer/All/Model/Error/Api.php';


class Requestor
{
    private $debug = false;

    public function refund($merchantNo, $storeNo, $token, $payment, $amount)
    {
        $transactionId = $payment->getParentTransactionId();
//        $order = $payment->getOrder();

        $httpClient = CurlClient::instance();
        $url = 'https://mapi.yuansfer.com/appTransaction/v2/securepayRefund';
        if ($this->debug) {
            $url = 'https://mapi.yuansfer.yunkeguan.com/appTransaction/v2/securepayRefund';
        }

        $params = array(
            'merchantNo' => $merchantNo,
            'storeNo' => $storeNo,
            'amount' => $amount,
            'reference' => $transactionId,
        );
        $params = $this->addSign($params, $token);

        $this->log('send to ' . $url . ' with params:' . print_r($params, true));

        list($rbody, $rcode, $rheaders) = $httpClient->request('post', $url, [], $params, false);

        $resp = $this->_interpretResponse($rbody, $rcode, $rheaders, $params);

        $this->log('response: ' . print_r($resp, true));

        if (
            !isset($resp['ret_code']) ||
            $resp['ret_code'] !== '000100'
        ) {
            throw new ErrorException('Order refund failed!');
        }

        return $resp;


    }

    private function _interpretResponse($rbody, $rcode, $rheaders, $params)
    {
        try {
            $resp = json_decode($rbody, true);
        } catch (Exception $e) {
            $msg = "Invalid response body from API: $rbody "
                . "(HTTP response code was $rcode)";
            throw new Error_Api($msg, $rcode, $rbody);
        }

        if ($rcode < 200 || $rcode >= 300) {
            $this->handleApiError($rbody, $rcode, $rheaders, $resp, $params);
        }

        return $resp;
    }

    public function handleApiError($rbody, $rcode, $rheaders, $resp, $param)
    {
        if (!is_array($resp) || !isset($resp['error'])) {
            $msg = "Invalid response object from API: $rbody "
                . "(HTTP response code was $rcode)";
        } else {
            $msg = isset($resp['message']) ? $resp['message'] : null;
        }

        throw new Error_Api($msg, $param, $rcode, $rbody, $resp, $rheaders);

    }

    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    public function getDebug()
    {
        return $this->debug;
    }

    protected function log($msg)
    {
        Mage::log('Requestor - ' . $msg);
    }

    public function getSecureForm($merchantNo, $storeNo, $token, $vendor, $order, $ipn, $callback)
    {
        $httpClient = CurlClient::instance();
        $url = 'https://mapi.yuansfer.com/appTransaction/v2/securepay';
        if ($this->debug) {
            $url = 'https://mapi.yuansfer.yunkeguan.com/appTransaction/v2/securepay';
        }
        //$headers = array('Authorization: Bearer ' . $token);

        $product = '';
        foreach ($order->getAllItems() as $item) {
            $product .= $item->getName() . '...';
            break;
        }

        $params = array(
            'merchantNo' => $merchantNo,
            'storeNo' => $storeNo,
            'amount' => $order->getGrandTotal(),
            'vendor' => $vendor,
            'currency' => $order->getOrderCurrencyCode(),
            'reference' => $this->getReferenceCode($order->getIncrementId()),
            'ipnUrl' => $ipn,
            'callbackUrl' => $callback,
            'terminal' => $this->ismobile() ? 'WAP' : 'ONLINE',
            'description' => $product,
            'note' => sprintf('#%s(%s)', $order->getRealOrderId(), $order->getCustomerEmail()),
        );
        $params = $this->addSign($params, $token);

        $this->log('send to ' . $url . ' with params:' . print_r($params, true));

        list($rbody, $rcode, $rheaders) = $httpClient->request('post', $url, [], $params, false);

        $this->log($rbody);

        if ($rcode < 200 || $rcode >= 300) {
            $this->handleApiError($rbody, $rcode, $rheaders, null, $params);
        }

        return $rbody;
    }

    protected function getReferenceCode($order_id)
    {
        return $order_id . 'at' . time();
    }

    protected function ismobile()
    {
        $is_mobile = '0';

        if (preg_match('/(android|up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone)/i',
            $_SERVER['HTTP_USER_AGENT'])) {
            $is_mobile = 1;
        }

        if (
            (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])) ||
            (stripos($_SERVER['HTTP_ACCEPT'], 'application/vnd.wap.xhtml+xml') > 0)
        ) {
            $is_mobile = 1;
        }

        $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
        $mobile_agents = array(
            'w3c ', 'acs-', 'alav', 'alca', 'amoi', 'andr', 'audi', 'avan', 'benq', 'bird', 'blac', 'blaz', 'brew',
            'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno', 'ipaq', 'java', 'jigs', 'kddi', 'keji',
            'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-', 'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto',
            'mwbp', 'nec-', 'newt', 'noki', 'oper', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox', 'qwap',
            'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar', 'sie-', 'siem', 'smal', 'smar',
            'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-', 'tosh', 'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-',
            'wapa', 'wapi', 'wapp', 'wapr', 'webc', 'winw', 'winw', 'xda', 'xda-',
        );

        if (in_array($mobile_ua, $mobile_agents, true)) {
            $is_mobile = 1;
        }

        if (isset($_SERVER['ALL_HTTP'])) {
            if (stripos($_SERVER['ALL_HTTP'], 'OperaMini') > 0) {
                $is_mobile = 1;
            }
        }

        if (stripos($_SERVER['HTTP_USER_AGENT'], 'windows') > 0) {
            $is_mobile = 0;
        }

        return $is_mobile;
    }

    /**
     * @param array $params
     * @param string $token
     *
     * @return mixed
     */
    protected function addSign($params, $token)
    {
        unset($params['verifySign']);

        ksort($params, SORT_STRING);
        $str = '';
        foreach ($params as $k => $v) {
            $str .= $k . '=' . $v . '&';
        }
        $sig = md5($str . md5($token));

        $params['verifySign'] = $sig;

        return $params;
    }
}
