<?php

// ver 1.0 - 05.03.21

ini_set('max_execution_time', '1700');
set_time_limit(1700);


header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: application/json');
header('Content-Type: application/json; charset=utf-8');


http_response_code(200);

/////////////////////////////////////////////////
////////////  F U N K T I O N S  /////////////////
/////////////////////////////////////////////////

{
function send_forward($inputJSON, $link){
	
$request = 'POST';	
		
$descriptor = curl_init($link);

 curl_setopt($descriptor, CURLOPT_POSTFIELDS, $inputJSON);
 curl_setopt($descriptor, CURLOPT_RETURNTRANSFER, 1);
 curl_setopt($descriptor, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
 curl_setopt($descriptor, CURLOPT_CUSTOMREQUEST, $request);

    $itog = curl_exec($descriptor);
    curl_close($descriptor);

   		 return $itog;
		
}
function get_smartsender($token, $link){
	
$request = 'GET';	
		
$descriptor = curl_init($link);

 curl_setopt($descriptor, CURLOPT_RETURNTRANSFER, 1);
 curl_setopt($descriptor, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bearer '.$token)); 
 curl_setopt($descriptor, CURLOPT_CUSTOMREQUEST, $request);

    $itog = curl_exec($descriptor);
    curl_close($descriptor);

   		 return $itog;
		
}
function post_smartsender($token, $inputJSON, $link){
	
$request = 'POST';	
		
$descriptor = curl_init($link);

 curl_setopt($descriptor, CURLOPT_POSTFIELDS, $inputJSON);
 curl_setopt($descriptor, CURLOPT_RETURNTRANSFER, 1);
 curl_setopt($descriptor, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bearer '.$token)); 
 curl_setopt($descriptor, CURLOPT_CUSTOMREQUEST, $request);

    $itog = curl_exec($descriptor);
    curl_close($descriptor);

   		 return $itog;
		
}
function put_smartsender($token, $inputJSON, $link){
	
$request = 'PUT';	
		
$descriptor = curl_init($link);

 curl_setopt($descriptor, CURLOPT_POSTFIELDS, $inputJSON);
 curl_setopt($descriptor, CURLOPT_RETURNTRANSFER, 1);
 curl_setopt($descriptor, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bearer '.$token)); 
 curl_setopt($descriptor, CURLOPT_CUSTOMREQUEST, $request);

    $itog = curl_exec($descriptor);
    curl_close($descriptor);

   		 return $itog;
		
}
}

/////////////////////////////////////////////////
/////////////////////////////////////////////////


$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE); //convert JSON into array



//------------------

$ssToken = "ZURwGiwJj9OQmysBHltHJ23RqLvYSrLHfPtetugRPopv2MPYhvj92vroHP5x";
$merchantId = "ekjkMtbqXQz_UAUUzhrHA2RunSM";
$merchantSecret = "Kuk1w3FJPo56264URLlD5eSpps9Vd82Kvn46Eo5E1fl9of7co33n8lWYx10boniMTI3ic9WFfh89Vv6O348R8hD8urnF196O4yPcrUnfn2mldcWQAn87vSD7bqj4zisB";
$userId = $input["userId"];
$addParam = $input["addParam"];
$description = $input["description"];
$action = $input["action"];
$url = ((!empty($_SERVER["HTTPS"])) ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"];
$url = explode("?", $url);
$url = $url[0];


//------------------




if ($action == "Purchase") {
    if ($userId == NULL) {
    $result["status"] = "error";
    if ($userId == NULL) {
        $result["message"][] = "Вы не указали идентификатор пользователя. Система не знает, чью информацию нужно использовать.";
    }
    echo json_encode($result);
    exit;
}
    $response = get_smartsender($ssToken, "https://api.smartsender.com/v1/contacts/".$userId."/checkout?page=1&limitation=20");
    $cursor = json_decode($response, true);
    if ($cursor["error"] != NULL && $cursor["error"] != 'undefined') {
        $result["status"] = "error";
        $result["message"][] = "Ошибка получения данных из SmartSender";
        if ($cursor["error"]["code"] == 404 || $cursor["error"]["code"] == 400) {
            $result["message"][] = "Пользователь не найден. Проверте правильность идентификатора пользователя и приналежность токена к текущему проекту.";
        } else if ($cursor["error"]["code"] == 403) {
            $result["message"][] = "Токен проекта SmartSender указан неправильно. Проверте правильность токена.";
        }
        echo json_encode($result);
        exit;
    } else if (empty($cursor["collection"])) {
        $result["status"] = "error";
        $result["message"][] = "Корзина пользователя пустая. Для тестирования добавте товар в корзину.";
        echo json_encode($result);
        exit;
    }
    $pages = $cursor["cursor"]["pages"];
    for ($i = 1; $i <= $pages; $i++) {
        $response = get_smartsender($ssToken, "https://api.smartsender.com/v1/contacts/".$userId."/checkout?page=".$i."&limitation=20");
        $checkout = json_decode ($response, true);
    	$essences = $checkout["collection"];
    	$currency = $essences[0]["currency"];
    	foreach ($essences as $product) {
    		$message = $message.$product["product"]["name"].': '.$product["name"].' - '.$product["pivot"]["quantity"].' x '.$product["amount"].'<br>';
    		$summ[] = $product["pivot"]["quantity"]*$product["cash"]["amount"];
    		$tovar[] = $product["product"]["name"].': '.$product["name"].' - '.$product["pivot"]["quantity"].' x '.$product["amount"].' = '.$product["pivot"]["quantity"]*$product["cash"]["amount"];
    	}
    }
    if (is_array($summ)) {
        $summ_itog = array_sum($summ);
    }
    $concord["operation"] = "Purchase";
    $concord["merchant_id"] = $merchantId;
    $concord["amount"] = $summ_itog;
    $concord["order_id"] = $userId."-".mt_rand(1000000, 9999999);
    $concord["currency_iso"] = "UAH";
    $concord["description"] = $message;
    if (is_array($addParam) === true) {
        $concord["add_params"] = $addParam;
    }
    $concord["add_params"]["userId"] = $userId;
    $concord["callback_url"] = $url;
    $concord["signature"] = hash_hmac('md5', $concord["merchant_id"].';'.$concord["order_id"].';'.$concord["amount"].';'.$concord["currency_iso"].';'.$concord["description"], $merchantSecret);
    $concord["redirect"] = false;

    $concordResult = send_forward(json_encode($concord), "https://pay.concord.ua/api/");
    $result["concord"] = json_decode($concordResult, true);
    $result["summ"] = $summ_itog;
    $result["currency"] = $currency;
    $result["tovar"] = $tovar;
    echo json_encode($result);
} else if ($input["merchantSignature"] == hash_hmac('md5', $merchantId.';'.$input["orderReference"].';'.$input["amount"].';'.$input["currency"], $merchantSecret)) {
    echo "Ok Signature";
    $userId = $input["add_params"]["userId"];
    if ($userId == NULL) {
        $userId = explode('-', $input["orderReference"]);
        $userId = $userId[0];
    }
    $transactionStatus = $input["transactionStatus"];
    $reason = $input["reason"];
    if ($transactionStatus != "Approved") {
        $ssData["values"]["transactionStatus"] = $transactionStatus;
        $ssData["values"]["reasonCode"] = $input["reasonCode"];
        if ($transactionStatus == "Expired") {
            $ssData["values"]["reason"] = "Время для оплаты истекло...";
        } else if (file_exists("reasoncode.php")) {
            include("reasoncode.php");
        } else if ($reason == NULL || $reason == 'undefined') {
            $ssData["values"]["reason"] = "Обратитесь в Ваш банк для выяснения причин отказа";
        } else {
            $ssData["values"]["reason"] = $reason;
        }
        put_smartsender($ssToken, json_encode($ssData), "https://api.smartsender.com/v1/contacts/".$userId);
        exit;
    }
    if ($input["add_params"]["tag"] != NULL && $input["add_params"]["tags"] != 'undefined') {
        $response = get_smartsender($ssToken, 'https://api.smartsender.com/v1/tags?page=1&limitation=20');
        $data_tags = json_decode($response, true);
        $pages_tags = $data_tags["cursor"]["pages"];
        for ($i = 1; $i <= $pages_tags; $i++) {
            $response = get_smartsender($ssToken, 'https://api.smartsender.com/v1/tags?page='.$i.'&limitation=20');
            $data_tags = json_decode($response, true);
            foreach ($data_tags["collection"] as $all_ss_tags) {
                $ss_tags[$all_ss_tags["name"]] = $all_ss_tags["id"];
            }
        }
        if (is_array($input["add_params"]["tag"]) === true) {
            foreach ($input["add_params"]["tag"] as $tag_name) {
                $ss_tag_id = $ss_tags[$tag_name];
                $response = post_smartsender($ssToken, '', 'https://api.smartsender.com/v1/contacts/'.$userId.'/tags/'.$ss_tag_id.'');
            }
        } else {
            $ss_tag_id = $ss_tags[$input["add_params"]["tag"]];
            $response = post_smartsender($ssToken, '', 'https://api.smartsender.com/v1/contacts/'.$userId.'/tags/'.$ss_tag_id.'');
        }
    }
    if ($input["add_params"]["funnel"] != NULL && $input["add_params"]["funnel"] != 'undefined') {
        $response = get_smartsender($ssToken, 'https://api.smartsender.com/v1/funnels?page=1&limitation=20');
        $data_funnels = json_decode($response, true);
        $pages_funnels = $data_funnels["cursor"]["pages"];
        for ($i = 1; $i <= $pages_funnels; $i++) {
            $response = get_smartsender($ssToken, 'https://api.smartsender.com/v1/funnels?page='.$i.'&limitation=20');
            $data_funnels = json_decode($response, true);
            foreach ($data_funnels["collection"] as $all_ss_funnels) {
                $ss_funnels[$all_ss_funnels["name"]] = $all_ss_funnels["serviceKey"];
            }
        }
        if (is_array($input["add_params"]["funnel"]) === true) {
            foreach ($input["add_params"]["funnel"] as $funnel_name) {
                $ss_funnel_id = $ss_funnels[$funnel_name];
                $response = post_smartsender($ssToken, '', 'https://api.smartsender.com/v1/contacts/'.$userId.'/funnels/'.$ss_funnel_id.'');
            }
        } else {
            $ss_funnel_id = $ss_funnels[$input["add_params"]["funnel"]];
            $response = post_smartsender($ssToken, '', 'https://api.smartsender.com/v1/contacts/'.$userId.'/funnels/'.$ss_funnel_id.'');
        }
    }
    if ($input["add_params"]["trigger"] != NULL && $input["add_params"]["trigger"] != 'undefined') {
        if (is_array($input["add_params"]["trigger"]) === true) {
            foreach ($input["add_params"]["trigger"] as $trigger_name) {
                $response = post_smartsender($ssToken, '', 'https://api.smartsender.com/v1/contacts/'.$userId.'/fire?name='.$trigger_name.'');
            }
        } else {
            $trigger_name = $input["add_params"]["trigger"];
            $response = post_smartsender($ssToken, '', 'https://api.smartsender.com/v1/contacts/'.$userId.'/fire?name='.$trigger_name.'');
        }
    }
    
} else {
    $result["status"] = "Failed Signature";
    echo json_encode($result);
}






