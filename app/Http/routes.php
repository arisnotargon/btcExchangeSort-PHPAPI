<?php
//五个一起6秒左右
Route::get('api','ApiController@btcExchangeApi');
//火币600+毫秒
Route::get('api/huobi','ApiController@huobiTradeInfo');
//cex2000毫秒左右
Route::get('api/cex','ApiController@cexTradeInfo');
//币安1000毫秒左右
Route::get('api/bian','ApiController@bianTradeInfo');
//bitfinex2000毫秒左右
Route::get('api/bitfinex','ApiController@bitfinexTradeInfo');
//bittrex1200毫秒左右
Route::get('api/bittrex','ApiController@bittrexTradeInfo');
Route::get('api/gate','ApiController@gateTradeInfo');