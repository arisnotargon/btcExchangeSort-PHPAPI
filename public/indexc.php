<?php
$resArr = [
    'huobi' => [],
    'cex' => [],
    'bian' => [],
    'bitfinx' => [],
    'bittrex' => [],
];
//交易对
$symbolInput = !empty($_GET['symbol']) ? $_GET['symbol'] : '';
$symbolMap = [
    'default' => [
        'huobi' => 'ethbtc',
        'cex' => 'eth-btc',
    ],
];
$symbol = !empty($symbolInput) ? $symbolMap[$symbolInput] : $symbolMap['default'];

//火币,600毫秒
//$huobiResponse = (new Client())->get('https://api.huobi.pro/market/depth', ['query' => [
//    'symbol' => $symbol['huobi'],
//    'type' => 'step1',
//]])->getBody()->getContents();
$huobiUrl = 'https://api.huobi.pro/market/depth?symbol=' . $symbol['huobi'] . '&type=step1';
$huobiResponse = file_get_contents($huobiUrl);
$huobiArr = [
    'status'=>200,
    'buy' => [],
    'sell' => [],
];
//bids卖,ask买
try{
    $huobiResponse = json_decode($huobiResponse)->tick;
    foreach ($huobiResponse->bids as $row) {
        $huobiArr['sell'][] = [
            'price' => $row[0],
            'depth' => $row[1],
        ];
    }
    foreach ($huobiResponse->asks as $row) {
        $huobiArr['buy'][] = [
            'price' => $row[0],
            'depth' => $row[1],
        ];
    }
}catch (\ErrorException $e){
    $huobiArr['status'] = 500;
}catch (\Throwable $e){
    $huobiArr['status'] = 500;
}

//$huobiArr['time'] = date('Y-m-d H:i:s',substr($huobiResponse->ts,0,-3)).'.'.substr($huobiResponse->ts,-3);

//cex,1800毫秒

$cexUrl = 'https://c-cex.com/t/api_pub.html?a=getorderbook&market='.$symbol['cex'].'&type=both&depth=10';
var_dump($cexUrl);die();
$cexResponse = file_get_contents($cexUrl);
$cexArr = [
    'status' => 200,
    'buy' => [],
    'sell' => [],
];
try{
    $cexResponse = json_decode($cexResponse);
    foreach ($cexResponse->result->buy as $row) {
        $cexArr['buy'][] = [
            'price' => $row->Rate,
            'depth' => $row->Quantity,
        ];
    }
    foreach ($cexResponse->result->sell as $row) {
        $cexArr['sell'][] = [
            'price' => $row->Rate,
            'depth' => $row->Quantity,
        ];
    }
}catch (\ErrorException $e){
    $cexArr['status'] = 500;
}catch (\Throwable $e){
    $cexArr['status'] = 500;
}

