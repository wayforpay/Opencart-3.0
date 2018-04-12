<?php

class ControllerExtensionPaymentWayforpay extends Controller
{
    private $error = array();

    public function index()
    {
        $this->load->language('extension/payment/wayforpay');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
//------------------------------------------------------------

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_wayforpay', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token']. '&type=payment', true));
        }

        $arr = array(
            "heading_title", "text_payment", "text_success", "text_pay", "text_card",
            "entry_merchant", "entry_secretkey", "entry_order_status",
            "entry_currency", "entry_returnUrl", "entry_serviceUrl", "entry_language", "entry_status",
            "entry_sort_order", "error_permission", "error_merchant", "error_secretkey", "error_returnUrl", "error_serviceUrl");

        foreach ($arr as $v) {
            $data[$v] = $this->language->get($v);
        }
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_all_zones'] = $this->language->get('text_all_zones');
	$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');


//------------------------------------------------------------
        $arr = array('warning', 'merchant', 'secretkey', 'type', 'returnUrl', 'serviceUrl');
        foreach ($arr as $v)
            $data['error_' . $v] = (isset($this->error[$v])) ? $this->error[$v] : '';
//------------------------------------------------------------

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
            'separator' => false
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token']  . '&type=payment', true),
            'separator' => ' :: '
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/wayforpay', 'user_token=' . $this->session->data['user_token'], true),
            'separator' => ' :: '
        );

        $data['action'] = $this->url->link('extension/payment/wayforpay', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

//------------------------------------------------------------
        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $arr = array("payment_wayforpay_merchant", "payment_wayforpay_secretkey", "payment_wayforpay_currency", "payment_wayforpay_returnUrl", "payment_wayforpay_serviceUrl",
            "payment_wayforpay_language", "payment_wayforpay_status", "payment_wayforpay_sort_order", "payment_wayforpay_order_status_id");

        foreach ($arr as $v) {
            $data[$v] = (isset($this->request->post[$v])) ? $this->request->post[$v] : $this->config->get($v);
            if (defined('HTTP_CATALOG') and defined('HTTPS_CATALOG') and !isset($this->request->post[$v])) {
                if ($v == 'payment_wayforpay_returnUrl' and empty($data[$v])) {
                    $data[$v] = (isset($_SERVER['HTTPS']) ? HTTPS_CATALOG : HTTP_CATALOG) . 'index.php?route=extension/payment/wayforpay/response';
                } elseif ($v == 'payment_wayforpay_serviceUrl' and empty($data[$v])) {
                    $data[$v] = (isset($_SERVER['HTTPS']) ? HTTPS_CATALOG : HTTP_CATALOG) . 'index.php?route=extension/payment/wayforpay/callback';
                }
            }
        }
	    $data['text_all_zones'] = $this->language->get('text_all_zones');
        if (isset($this->request->post['payment_wayforpay_geo_zone_id'])) {
		    $data['payment_wayforpay_geo_zone_id'] = $this->request->post['payment_wayforpay_geo_zone_id'];
	    } else {
		    $data['payment_wayforpay_geo_zone_id'] = $this->config->get('payment_wayforpay_geo_zone_id');
	    }

	    $this->load->model('localisation/geo_zone');

	    $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
//------------------------------------------------------------


        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/wayforpay', $data));
    }

//------------------------------------------------------------
    private function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/wayforpay')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['payment_wayforpay_merchant']) {
            $this->error['merchant'] = $this->language->get('error_merchant');
        }

        if (!$this->request->post['payment_wayforpay_secretkey']) {
            $this->error['secretkey'] = $this->language->get('error_secretkey');
        }

        if (!$this->request->post['payment_wayforpay_returnUrl']) {
            $this->error['returnUrl'] = $this->language->get('error_returnUrl');
        }

        if (!$this->request->post['payment_wayforpay_serviceUrl']) {
            $this->error['serviceUrl'] = $this->language->get('error_serviceUrl');
        }
		$ret = !$this->error;
	    return $ret;
    }
}
