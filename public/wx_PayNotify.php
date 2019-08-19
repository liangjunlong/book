<?php
/**
 * 微信支付回调
 */
require_once dirname(__FILE__) . '/init.php';

$pai = new \PhalApi\PhalApi();

require_once(API_ROOT . '/Library/Wxpay/WxPay.php');

$wechatAppPay = new WxPay();

$order_no = $wechatAppPay->WX_OrderNo();

//1.获取订单详情
$order_info = getOrderDetail($order_no);
if (empty($order_info)) {
    $arrs['return_code'] = 'FAIL';
    $arrs['return_msg'] = 'OK';
    echo xml($arrs);
    Log('获取订单详情', array());
    exit;
}

//2.获取订单对应支付配置
$pay_config = getPayConfig($order_info['house_id']);
if (empty($pay_config)) {
    $arrs['return_code'] = 'FAIL';
    $arrs['return_msg'] = 'OK';
    echo xml($arrs);
    Log('获取订单对应支付配置', array());
    exit;
}

$wxPay = array(
    'appid' => $pay_config['pay_config_wx_appid'],
    'appsecret' => $pay_config['pay_config_wx_appsecret'],
    'mch_id' => $pay_config['pay_config_wx_mchid'],
    'mch_key' => $pay_config['pay_config_wx_mchkey'],
);

$wechatAppPay->setConfig($wxPay);

//3.获取支付返回数据
$code = $wechatAppPay->weixin();
if (empty($code)) {
    $arrs['return_code'] = 'FAIL';
    $arrs['return_msg'] = 'OK';
    echo xml($arrs);
    Log('获取支付返回数据', array());
    exit;
}

$pay_no = $code['ordersn'];
$total_fee = $code['total_fee'] / 100;
$transaction_id = $code['transaction_id'];


if ($total_fee != $order_info['order_money']) {
    $arrs['return_code'] = 'FAIL';
    $arrs['return_msg'] = 'OK';
    echo xml($arrs);
    Log('支付金额和订单需支付金额不符', array('pay_money' => $total_fee, 'need_money' => $order_info['order_money']));
    exit;
}

//4.更改订单状态
$result = updateOrder($pay_no);


//更改订单状态
function updateOrder($order_no)
{
    $prefix = \PhalApi\DI()->config->get('dbs.tables.mdhr.prefix');
    $table_order = $prefix . 'order';
    $table_order_fina = $prefix . 'order_fina';

    \PhalApi\DI()->notorm->beginTransaction(DB_TICKET);

    //a.更新订单状态
    $result1 = \PhalApi\DI()->notorm->$table_order->where(array('order_no' => $order_no))->update(array('order_status' => 1));
    //b.更新支付订单状态
    $result2 = \PhalApi\DI()->notorm->$table_order_fina->where(array('order_fina_order_no' => $order_no))->update(array('order_fina_status' => 1, 'order_fina_pay_time' => time()));

    if ($result1 === false || $result2 === false) {
        \PhalApi\DI()->notorm->rollback(DB_TICKET);
        $arrs['return_code'] = 'FAIL';
        $arrs['return_msg'] = 'OK';
        Log('更改状态失败', array('order_status' => $result1, 'order_fina_status' => $result2));
        echo xml($arrs);
        exit;
    } else {
        \PhalApi\DI()->notorm->commit(DB_TICKET);
        $arrs['return_code'] = 'SUCCESS';
        $arrs['return_msg'] = 'OK';
        echo xml($arrs);
        exit;
    }


}

//根据订单变编号获取订单信息
function getOrderDetail($order_no)
{
    $prefix = \PhalApi\DI()->config->get('dbs . tables . mdhr . prefix');
    $field = 'o . id,o . order_no,o . order_contract_no,o . order_status,o . order_money,o . order_add_time,o . order_price,';
    $field .= 'o . order_start_time,o . order_end_time,h . house_name,h . house_no,h . house_show_rent,v . village_name,';
    $field .= 'v . village_street,v . village_lng,v . village_lat,c . city_name,aa . area_name,h . id AS house_id,';
    $field .= 'h . house_areas,u . user_name,u . user_telephone,gc . config_value AS house_pay_type,cc . contract_deposit ';

    $sql = 'SELECT ' . $field
        . ' FROM ' . $prefix . 'order o '
        . ' JOIN ' . $prefix . 'contract cc ON o . order_contract_no = cc . contract_no '
        . ' JOIN ' . $prefix . 'house h ON cc . contract_house_id = h . id '
        . ' JOIN ' . $prefix . 'global_config gc ON h . house_pay_type = gc . id '
        . ' JOIN ' . $prefix . 'village v ON h . house_village_id = v . id '
        . ' JOIN ' . $prefix . 'city c ON h . house_city_code = c . city_code '
        . ' JOIN ' . $prefix . 'area aa ON h . house_area_code = aa . area_code '
        . ' JOIN ' . $prefix . 'user u ON o . order_creater = u . id '
        . ' WHERE h . house_del = 0 AND o . order_no =:order_no ';

    $params[':order_no'] = $order_no;

    $result = \PhalApi\DI()->notorm->notTable->queryRows($sql, $params);


    if (!empty($result)) {

        foreach ($result as $key => $val) {

            $field = 'assets_images_url';
            $sql = 'SELECT ' . $field
                . ' FROM ' . $prefix . 'house_assets ha '
                . ' JOIN ' . $prefix . 'assets_images ai ON ha . house_assets_assets_id = ai . assets_images_assets_id'
                . ' WHERE ha . house_assets_house_id = ' . $val['house_id'];

            $row = \PhalApi\DI()->notorm->notTable->queryRows($sql, array());
            $result[$key]['house_image_url'] = !empty($row[0]['assets_images_url']) ? $row[0]['assets_images_url'] : "";


            $result[$key]['order_start_time'] = date('Y - m - d ', $val['order_start_time']);
            $result[$key]['order_end_time'] = date('Y - m - d ', $val['order_end_time']);
            $result[$key]['order_add_time'] = date('Y - m - d H:i:s', $val['order_add_time']);

        }
    }
    return !empty($result[0]) ? $result[0] : array();

}


//根据房源id获取支付参数
function getPayConfig($house_id)
{
    $prefix = \PhalApi\DI()->config->get('dbs . tables . mdhr . prefix');
    $field = 'pc . pay_config_company,pc . pay_config_bankname,pc . pay_config_bankcard,pc . pay_config_bankuser,';
    $field .= 'pc . pay_config_wx_appid,pc . pay_config_wx_appsecret,pc . pay_config_wx_mchid,pc . pay_config_wx_mchkey';
    $sql = 'SELECT ' . $field
        . ' FROM ' . $prefix . 'house h '
        . ' JOIN ' . $prefix . 'house_assets ha ON h . id = ha . house_assets_house_id '
        . ' JOIN ' . $prefix . 'assets a ON ha . house_assets_assets_id = a . id AND a . assets_del = 0 '
        . ' JOIN ' . $prefix . 'pay_config pc ON a . assets_type = pc . pay_config_global_id '
        . ' WHERE h . house_del = 0 AND h . id = :house_id';
    $params[':house_id'] = $house_id;
    $result = \PhalApi\DI()->notorm->notTable->queryRows($sql, $params);
    return !empty($result[0]) ? $result[0] : array();
}

function xml($data)
{
    $xml = "<xml>";
    foreach ($data as $key => $val) {
        if (is_numeric($val)) {
            $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
        } else {
            $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
    }
    $xml .= "</xml>";
    return $xml;
}

/**
 * 记录错误
 * @param $msg
 * @param $data
 */
function Log($msg, $data)
{
    $path = API_ROOT . "/Log/wxpay/";

    if (!is_dir($path)) mkdir($path);
    $txt = "========================================================\r\n";
    $txt .= "时间：" . date('Y-m-d H:i:s', time()) . "\r\n";
    $txt .= "执行：调用 " . $msg . "\r\n";
    foreach ($data as $k => $v) {
        $txt .= $k . "=>" . $v . "\r\n";
    }
    $txt .= "========================================================\r\n";
    $txt .= "\r\n";
    error_log($txt, 3, $path . '/' . date('Y-m-d') . ".log");

}