<?php
// Heading
$_['heading_title'] = 'WayForPay';

// Text 
$_['text_payment'] = 'Оплата';
$_['text_wayforpay'] = '<a onclick="window.open(\'http://wayforpay.com/\');"><img src="view/image/payment/w4p.png" alt="WayForPay" title="WayForPay" style="border: 1px solid #EEEEEE;" /></a>';
$_['text_success'] = 'Настройки модуля обновлены!';
$_['text_pay'] = 'WayForPay';
$_['text_card'] = 'Credit Card';
$_['text_all_zones'] = 'All Zones';

// Entry
$_['entry_merchant'] = 'Merchant Account:';
$_['entry_secretkey'] = 'Secret key:';

$_['entry_order_status'] = 'Статус заказа после оплаты:';
$_['entry_currency'] = 'Валюта мерчанта';
$_['entry_returnUrl'] = 'Ссылка возврата клиента:<br /><span class="help">http://{your_domain}/index.php?route=extension/payment/wayforpay/response</span>';
$_['entry_serviceUrl'] = 'Ссылка возврата для сервера:<br /><span class="help">http://{your_domain}/index.php?route=extension/payment/wayforpay/callback</span>';
$_['entry_language'] = 'Язык страницы:<br /><span class="help">по-умолчанию : RU </span>';
$_['entry_geo_zone']    = 'Geo Zone';

$_['entry_status'] = 'Статус:';
$_['entry_sort_order'] = 'Порядок сортировки:';

// Error
$_['error_permission'] = 'У Вас нет прав для управления этим модулем!';
$_['error_merchant'] = 'Неверный ID магазина (Merchant Account)!';
$_['error_secretkey'] = 'Отсутствует секретный ключ!';
$_['error_returnUrl'] = 'Обязателен returnUrl!';
$_['error_serviceUrl'] = 'Обязателен serviceUrl!';
?>