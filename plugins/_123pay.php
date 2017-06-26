<?php
$pluginData['_123pay']['type'] = 'payment';
$pluginData['_123pay']['name'] = 'سامانه پرداخت اینترنتی یک دو سه پی';
$pluginData['_123pay']['uniq'] = '_123pay';
$pluginData['_123pay']['description'] = 'پلاگین پرداخت، سامانه پرداخت اینترنتی یک دو سه پی';
$pluginData['_123pay']['author']['name'] = 'تیم فنی یک دو سه پی';
$pluginData['_123pay']['author']['url'] = 'https://123pay.ir';
$pluginData['_123pay']['author']['email'] = 'plugins@123pay.ir';
$pluginData['_123pay']['field']['config'][1]['title'] = 'کد پذیرنده';
$pluginData['_123pay']['field']['config'][1]['name'] = 'merchant_id';

function gateway___123pay($data)
{
    global $config, $db, $smarty;
    require_once 'include/libs/123pay.php';

    $merchant_id = trim($data['merchant_id']);
    $amount = round($data['amount']);
    $callback_url = $data['callback'];

    $response = create($merchant_id, $amount, $callback_url);
    $result = json_decode($response);
    if ($result->status) {
        $update['payment_rand'] = $result->RefNum;
        $sql = $db->queryUpdate('payment', $update, 'WHERE `payment_rand` = "' . $data['invoice_id'] . '" LIMIT 1');
        $db->execute($sql);
        header("location: $result->payment_url");
    } else {
        $data['title'] = 'خطای سیستم';
        $data['message'] = '<span style="color: #ff0000">پیام سیستم : </span>' . $result->message . '<br /><a href="index.php" class="button">بازگشت</a>';
        $query = 'SELECT * FROM `config` WHERE `config_id` = "1" LIMIT 1';
        $conf = $db->fetch($query);
        $smarty->assign('config', $conf);
        $smarty->assign('data', $data);
        $smarty->display('message.tpl');
    }
}

function callback___123pay($data)
{
    global $db, $get;
    require_once 'include/libs/123pay.php';

    $merchant_id = trim($data['merchant_id']);
    $State = $_REQUEST['State'];
    $RefNum = $_REQUEST['RefNum'];
    if ($State == 'OK') {
        $response = verify($merchant_id, $RefNum);
        $result = json_decode($response);
        if ($result->status) {
            $sql = 'SELECT * FROM `payment` WHERE `payment_rand` = "' . $RefNum . '" LIMIT 1;';
            $payment = $db->fetch($sql);
            if ($payment) {
                if ($payment['payment_amount'] == $result->amount) {
                    if ($payment['payment_status'] == 1) {
                        $output['status'] = 1;
                        $output['res_num'] = null;
                        $output['ref_num'] = $RefNum;
                        $output['payment_id'] = $payment['payment_id'];
                    } else {
                        $output['status'] = 0;
                        $output['message'] = 'پرداخت تایید نشد‌';
                    }
                } else {
                    $output['status'] = 0;
                    $output['message'] = 'مبلغ تراکنش صحیح نیست';
                }
            } else {
                $output['status'] = 0;
                $output['message'] = 'درخواست پرداخت شما در سیستم یافت نشد';
            }
        } else {
            $output['status'] = 0;
            $output['message'] = 'پرداخت توسط یک دو سه پی تایید نشد';
        }
    }
    return $output;
}
