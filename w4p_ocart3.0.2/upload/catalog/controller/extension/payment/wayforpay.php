<?php

class ControllerExtensionPaymentWayforpay extends Controller
{
    public $codesCurrency = array(
            980 => 'UAH',
            840 => 'USD',
            978 => 'EUR',
            643 => 'RUB',
        );

    public function index()
    {
	    $this->language->load('extension/payment/wayforpay');
	    $this->load->model('checkout/order');

	    $fields = $this->generateFields();
		$names = $fields['productName'];
		$prices = $fields['productPrice'];
		$counts = $fields['productCount'];
		unset($fields['productName']);
		unset($fields['productPrice']);
		unset($fields['productCount']);
	    $data['action'] = WayForPay::URL;
	    $data['button_confirm'] = $this->language->get('button_confirm');
	    $data['fields'] = $fields;
	    $data['prod_name'] = $names;
	    $data['prod_price'] = $prices;
	    $data['prod_count'] = $counts;
	    $data['text_loading'] = 'loading';
	    $data['order_id'] = $this->session->data['order_id'];

	    return $this->load->view('extension/payment/wayforpay', $data);
    }

	public function generateFields() {
		$w4p = new WayForPay();
		$key = $this->config->get('payment_wayforpay_secretkey');
		$w4p->setSecretKey($key);

		$this->load->model('checkout/order');
		$order = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$serviceUrl = $this->config->get('payment_wayforpay_serviceUrl');
		$returnUrl = $this->config->get('payment_wayforpay_returnUrl');

		$currency = isset($this->codesCurrency[$order['currency_code']]) ? $this->codesCurrency[$order['currency_code']] : $order['currency_code'];
		$amount = round(($order['total'] * $order['currency_value']), 2);

		$fields = array(
			'orderReference' => $order['order_id'] . WayForPay::ORDER_SEPARATOR . time(),
			'merchantAccount' => $this->config->get('payment_wayforpay_merchant'),
			'orderDate' => strtotime($order['date_added']),
			'merchantAuthType' => 'simpleSignature',
			'merchantDomainName' => $_SERVER['HTTP_HOST'],
			'merchantTransactionSecureType' => 'AUTO',
			'amount' => $amount,
			'currency' => $currency,
			'serviceUrl' => $serviceUrl,
			'returnUrl' => $returnUrl,
			'language' => $this->config->get('payment_wayforpay_language')
		);

		$productNames = array();
		$productQty = array();
		$productPrices = array();
		$this->load->model('account/order');
		$products = $this->model_account_order->getOrderProducts($order['order_id']);
		foreach ($products as $product) {
			$productNames[] = str_replace(array("'", '"', '&#39;', '&'), '', htmlspecialchars_decode($product['name']));
			$productPrices[] = $product['price'];
			$productQty[] = intval($product['quantity']);
		}

		$fields['productName'] = $productNames;
		$fields['productPrice'] = $productPrices;
		$fields['productCount'] = $productQty;

		/**
		 * Check phone
		 */
		$phone = str_replace(array('+', ' ', '(', ')'), array('', '', '', ''), $order['telephone']);
		if (strlen($phone) == 10) {
			$phone = '38' . $phone;
		} elseif (strlen($phone) == 11) {
			$phone = '3' . $phone;
		}

		$fields['clientFirstName'] = $order['payment_firstname'];
		$fields['clientLastName'] = $order['payment_lastname'];
		$fields['clientEmail'] = $order['email'];
		$fields['clientPhone'] = $phone;
		$fields['clientCity'] = $order['payment_city'];
		$fields['clientAddress'] = $order['payment_address_1'] . ' ' . $order['payment_address_2'];
		$fields['clientCountry'] = $order['payment_iso_code_3'];

		$fields['merchantSignature'] = $w4p->getRequestSignature($fields);

		return $fields;
    }

    public function confirm()
    {
	    if ($this->session->data['payment_method']['code'] == 'wayforpay') {
		    $this->load->model('checkout/order');

		    $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		    if ( ! $order_info) {
			    return;
		    }

		    $order_id = $this->session->data['order_id'];

		    if ($order_info['order_status_id'] == 0) {
			    $this->model_checkout_order->confirm($order_id, $this->config->get('wayforpay_order_status_progress_id'), 'WayForPay');

			    return;
		    }

		    if ($order_info['order_status_id'] != $this->config->get('payment_wayforpay_order_status_id')) {
			    $this->model_checkout_order->update($order_id, $this->config->get('payment_wayforpay_order_status_id'), 'WayForPay', true);
		    }
	    }
    }

    public function response()
    {
        $w4p = new WayForPay();
        $key = $this->config->get('payment_wayforpay_secretkey');
        $w4p->setSecretKey($key);

        $paymentInfo = $w4p->isPaymentValid($_POST);

        if ($paymentInfo === true) {
            list($order_id,) = explode(WayForPay::ORDER_SEPARATOR, $_POST['orderReference']);

            $message = '';

            $this->load->model('checkout/order');

            /**
             * check current order status if no eq wayforpay_order_status_id then confirm
             */
            $orderInfo = $this->model_checkout_order->getOrder($order_id);
            if (
                $orderInfo &&
                $orderInfo['order_status_id'] == $this->config->get('payment_wayforpay_order_status_id')
            ) {
                //nothing
            } else {
                $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_wayforpay_order_status_id'), $message, false);
            }

            $this->response->redirect($this->url->link('checkout/success'));
        } else {
            $this->session->data['error'] = $paymentInfo;
            $this->response->redirect($this->url->link('checkout/failure', '', 'SSL'));
        }
    }

    public function callback()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $w4p = new WayForPay();
        $key = $this->config->get('payment_wayforpay_secretkey');
        $w4p->setSecretKey($key);

        $paymentInfo = $w4p->isPaymentValid($data);

        if ($paymentInfo === true) {
            list($order_id,) = explode(WayForPay::ORDER_SEPARATOR, $data['orderReference']);

            $message = '';

            $this->load->model('checkout/order');

            /**
             * check current order status if no eq wayforpay_order_status_id then confirm
             */
            $orderInfo = $this->model_checkout_order->getOrder($order_id);
            if (
                $orderInfo &&
                $orderInfo['order_status_id'] == $this->config->get('payment_wayforpay_order_status_id')
            ) {
                //nothing
            } else {
                $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_wayforpay_order_status_id'), $message, false);
            }

            echo $w4p->getAnswerToGateWay($data);
        } else {
            echo $paymentInfo;
        }
        exit();
    }
}

class WayForPay
{
    const ORDER_APPROVED = 'Approved';
    const ORDER_HOLD_APPROVED = 'WaitingAuthComplete';
    const ORDER_IS_PENDING = 'Pending';

    const ORDER_SEPARATOR = '#';

    const SIGNATURE_SEPARATOR = ';';

    const URL = "https://secure.wayforpay.com/pay";

    protected $secret_key = '';
    protected $keysForResponseSignature = array(
        'merchantAccount',
        'orderReference',
        'amount',
        'currency',
        'authCode',
        'cardPan',
        'transactionStatus',
        'reasonCode'
    );

    /** @var array */
    protected $keysForSignature = array(
        'merchantAccount',
        'merchantDomainName',
        'orderReference',
        'orderDate',
        'amount',
        'currency',
        'productName',
        'productCount',
        'productPrice'
    );


    /**
     * @param $option
     * @param $keys
     * @return string
     */
    public function getSignature($option, $keys)
    {
        $hash = array();
        foreach ($keys as $dataKey) {
            if (!isset($option[$dataKey])) {
                $option[$dataKey] = '';
            }
            if (is_array($option[$dataKey])) {
                foreach ($option[$dataKey] as $v) {
                    $hash[] = $v;
                }
            } else {
                $hash [] = $option[$dataKey];
            }
        }

        $hash = implode(self::SIGNATURE_SEPARATOR, $hash);
        return hash_hmac('md5', $hash, $this->getSecretKey());
    }


    /**
     * @param $options
     * @return string
     */
    public function getRequestSignature($options)
    {
        return $this->getSignature($options, $this->keysForSignature);
    }

    /**
     * @param $options
     * @return string
     */
    public function getResponseSignature($options)
    {
        return $this->getSignature($options, $this->keysForResponseSignature);
    }


    /**
     * @param array $data
     * @return string
     */
    public function getAnswerToGateWay($data)
    {
        $time = time();
        $responseToGateway = array(
            'orderReference' => $data['orderReference'],
            'status' => 'accept',
            'time' => $time
        );
        $sign = array();
        foreach ($responseToGateway as $dataKey => $dataValue) {
            $sign [] = $dataValue;
        }
        $sign = implode(self::SIGNATURE_SEPARATOR, $sign);
        $sign = hash_hmac('md5', $sign, $this->getSecretKey());
        $responseToGateway['signature'] = $sign;

        return json_encode($responseToGateway);
    }

    /**
     * @param $response
     * @return bool|string
     */
    public function isPaymentValid($response)
    {
        
        if (!isset($response['merchantSignature']) && isset($response['reason'])) {
            return $response['reason'];
        }
		$sign = $this->getResponseSignature($response);
        if ($sign != $response['merchantSignature']) {
            return 'An error has occurred during payment ';
        }

        if (
            $response['transactionStatus'] == self::ORDER_APPROVED ||
            $response['transactionStatus'] == self::ORDER_HOLD_APPROVED ||
            $response['transactionStatus'] == self::ORDER_IS_PENDING		
        ) {
            return true;
        }

        return false;
    }

    public function setSecretKey($key)
    {
        $this->secret_key = $key;
    }

    public function getSecretKey()
    {
        return $this->secret_key;
    }
}
