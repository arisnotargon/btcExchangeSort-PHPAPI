<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class ApiController extends Controller
{
    private $symbolMap = [
        'default' => [
            'huobi' => 'ethbtc',
            'cex' => 'eth-btc',
            'binance' => 'ETHBTC',
            'bitfinex' => 'ethbtc',
            'bittrex' => 'BTC-ETH',
            'gate' => 'eth_btc',
        ],
    ];

    public function btcExchangeApi(Request $request)
    {
        //交易对
        $symbolInput = $request->input('symbol');

        $symbol = !empty($symbolInput) ? $this->symbolMap[$symbolInput] : $this->symbolMap['default'];

        //火币,600毫秒
        $huobiResponse = (new Client())->get('https://api.huobi.pro/market/depth', ['query' => [
            'symbol' => $symbol['huobi'],
            'type' => 'step1',
        ]])->getBody()->getContents();
        $huobiArr = [
            'status' => 200,
            'buy' => [],
            'sell' => [],
        ];
        try {
            $huobiResponse = json_decode($huobiResponse)->tick;
            /***
             * bids买,asks卖
             * 买价从高到低排序,卖价从低到高排序
             */
            $i = 0;
            foreach ($huobiResponse->bids as $row) {
                $huobiArr['buy'][] = [
                    'price' => $row[0],
                    'depth' => $row[1],
                ];
                $i++;
                if ($i >= 10) {
                    break;
                }
            }

            $i = 0;
            foreach ($huobiResponse->asks as $row) {
                $huobiArr['sell'][] = [
                    'price' => $row[0],
                    'depth' => $row[1],
                ];
                $i++;
                if ($i >= 10) {
                    break;
                }
            }
//            $huobiArr['time'] = date('Y-m-d H:i:s',substr($huobiResponse->ts,0,-3)).'.'.substr($huobiResponse->ts,-3);
        } catch (\ErrorException $e) {
            $huobiArr['status'] = 500;
        } catch (\Throwable $e) {
            $huobiArr['status'] = 500;
        }

        //cex,2000毫秒
        $cexResponse = (new Client())->get('https://c-cex.com/t/api_pub.html', ['query' => [
            'a' => 'getorderbook',
            'market' => $symbol['cex'],
            'type' => 'both',
            'depth' => 10,
        ]])->getBody()->getContents();
        $cexArr = [
            'status' => 200,
            'buy' => [],
            'sell' => [],
        ];
        $cexResponse = json_decode($cexResponse);

        try {
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
        } catch (\ErrorException $e) {
            $cexArr['status'] = 500;
        } catch (\Throwable $e) {
            $cexArr['status'] = 500;
        }

        //币安,2000毫秒
        $bianArr = [
            'status' => 200,
            'sell' => [],
            'buy' => [],
        ];
        try {
            $bianResponse = (new Client())->get('https://api.binance.com/api/v1/depth', ['query' => [
                'symbol' => $symbol['binance'],
                'limit' => 10,
            ]])->getBody()->getContents();
            $bianResponse = json_decode($bianResponse);

            //买从高到低
            foreach ($bianResponse->bids as $row) {
                $bianArr['buy'][] = [
                    'price' => $row[0],
                    'depth' => $row[1],
                ];
            }
            //卖从低到高
            foreach ($bianResponse->asks as $row) {
                $bianArr['sell'][] = [
                    'price' => $row[0],
                    'depth' => $row[1],
                ];
            }
        } catch (\ErrorException $e) {
            $bianArr['status'] = 500;
        } catch (\Throwable $e) {
            $bianArr['status'] = 500;
        }

        //bitfinex
        $bitfinexResponse = (new Client())->get('https://api.bitfinex.com/v1/book/' . $symbol['bitfinex'])->getBody()->getContents();
        $bitfinexArr = [
            'status' => 200,
            'sell' => [],
            'buy' => [],
        ];
        try {
            $bitfinexResponse = json_decode($bitfinexResponse, true);
            /***
             * bids买,asks卖
             * 买价从高到低排序,卖价从低到高排序
             */
        } catch (\Exception $e) {
            $bitfinexArr['status'] = 500;
        } catch (\Throwable $e) {
            $bitfinexArr['status'] = 500;
        }
        $i = 0;
        foreach (array_reverse($bitfinexResponse['bids']) as $row) {
            $bitfinexArr['buy'][] = [
                'price' => $row['price'],
                'depth' => $row['amount'],
            ];
            $i++;
            if ($i >= 10) {
                break;
            }
        }
        $i = 0;
        foreach ($bitfinexResponse['asks'] as $row) {
            $bitfinexArr['sell'][] = [
                'price' => $row['price'],
                'depth' => $row['amount'],
            ];
            $i++;
            if ($i >= 10) {
                break;
            }
        }

        //bittrex
        $bittrexArr = [
            'status' => 200,
            'sell' => [],
            'buy' => [],
        ];
        try {
            $bittrexResponse = (new Client())->get('https://bittrex.com/api/v1.1/public/getorderbook', [
                'query' => [
                    'market' => $symbol['bittrex'],
                    'type' => 'both',
                ]
            ])->getBody()->getContents();
            $bittrexResponse = json_decode($bittrexResponse, true);
            $i = 0;
            foreach ($bittrexResponse['result']['buy'] as $row) {
                $bittrexArr['buy'][] = [
                    'price' => $row['Rate'],
                    'depth' => $row['Quantity'],
                ];
                $i++;
                if ($i >= 10) {
                    break;
                }
            }
            $i = 0;
            foreach ($bittrexResponse['result']['sell'] as $row) {
                $bittrexArr['sell'][] = [
                    'price' => $row['Rate'],
                    'depth' => $row['Quantity'],
                ];
                $i++;
                if ($i >= 10) {
                    break;
                }
            }
        } catch (\Exception $exception) {
            $bittrexArr['status'] = 500;
        } catch (\Throwable $exception) {
            $bittrexArr['status'] = 500;
        }
        return response()->json([
            'huobi' => $huobiArr,
            'cex' => $cexArr,
            'bian' => $bianArr,
            'bitfinex' => $bitfinexArr,
            '$bittrexArr' => $bittrexArr,
        ]);
    }

    public function huobiTradeInfo(Request $request)
    {
        $symbolInput = $request->input('symbol');

        $symbol = !empty($symbolInput) ? $this->symbolMap[$symbolInput] : $this->symbolMap['default'];

        //火币,600毫秒
        $huobiResponse = (new Client())->get('https://api.huobi.pro/market/depth', ['query' => [
            'symbol' => $symbol['huobi'],
            'type' => 'step1',
        ]])->getBody()->getContents();
        $huobiArr = [
            'status' => 200,
            'buy' => [],
            'sell' => [],
        ];
        try {
            $huobiResponse = json_decode($huobiResponse)->tick;
            /***
             * bids买,asks卖
             * 买价从高到低排序,卖价从低到高排序
             */
            $i = 0;
            foreach ($huobiResponse->bids as $row) {
                $huobiArr['buy'][] = [
                    'price' => $row[0],
                    'depth' => $row[1],
                ];
                $i++;
                if ($i >= 10) {
                    break;
                }
            }

            $i = 0;
            foreach ($huobiResponse->asks as $row) {
                $huobiArr['sell'][] = [
                    'price' => $row[0],
                    'depth' => $row[1],
                ];
                $i++;
                if ($i >= 10) {
                    break;
                }
            }
//            $huobiArr['time'] = date('Y-m-d H:i:s',substr($huobiResponse->ts,0,-3)).'.'.substr($huobiResponse->ts,-3);
        } catch (\ErrorException $e) {
            $huobiArr['status'] = 500;
            $huobiArr['sell'][] = [
                'price' => '连接错误',
                'depth' => 0,
            ];
            $huobiArr['buy'][] = [
                'price' => '连接错误',
                'depth' => 0,
            ];
        } catch (\Throwable $e) {
            $huobiArr['status'] = 500;
            $huobiArr['sell'][] = [
                'price' => '连接错误',
                'depth' => 0,
            ];
            $huobiArr['buy'][] = [
                'price' => '连接错误',
                'depth' => 0,
            ];
        }
        return response()->json($huobiArr);
    }

    public function cexTradeInfo(Request $request)
    {
        $symbolInput = $request->input('symbol');

        $symbol = !empty($symbolInput) ? $this->symbolMap[$symbolInput] : $this->symbolMap['default'];

        //cex,2000毫秒
        $cexResponse = (new Client())->get('https://c-cex.com/t/api_pub.html', ['query' => [
            'a' => 'getorderbook',
            'market' => $symbol['cex'],
            'type' => 'both',
            'depth' => 10,
        ]])->getBody()->getContents();
        $cexArr = [
            'status' => 200,
            'buy' => [],
            'sell' => [],
        ];
        $cexResponse = json_decode($cexResponse);

        try {
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
        } catch (\ErrorException $e) {
            $cexArr['status'] = 500;
            $cexArr['sell'][] = [
                'price' => '连接错误',
                'depth' => 0,
            ];
            $cexArr['buy'][] = [
                'price' => '连接错误',
                'depth' => 0,
            ];
        } catch (\Throwable $e) {
            $cexArr['status'] = 500;
            $cexArr['sell'][] = [
                'price' => '连接错误',
                'depth' => 0,
            ];
            $cexArr['buy'][] = [
                'price' => '连接错误',
                'depth' => 0,
            ];
        }

        return response()->json($cexArr);
    }

    public function bianTradeInfo(Request $request)
    {
        $symbolInput = $request->input('symbol');

        $symbol = !empty($symbolInput) ? $this->symbolMap[$symbolInput] : $this->symbolMap['default'];
        //币安,2000毫秒
        $bianArr = [
            'status' => 200,
            'sell' => [],
            'buy' => [],
        ];
        try {
            $bianResponse = (new Client())->get('https://api.binance.com/api/v1/depth', ['query' => [
                'symbol' => $symbol['binance'],
                'limit' => 10,
            ]])->getBody()->getContents();
            $bianResponse = json_decode($bianResponse);

            //买从高到低
            foreach ($bianResponse->bids as $row) {
                $bianArr['buy'][] = [
                    'price' => $row[0],
                    'depth' => $row[1],
                ];
            }
            //卖从低到高
            foreach ($bianResponse->asks as $row) {
                $bianArr['sell'][] = [
                    'price' => $row[0],
                    'depth' => $row[1],
                ];
            }
        } catch (\ErrorException $e) {
            $bianArr['status'] = 500;
            $bianArr['sell'][] = [
                'price' => '连接错误',
                'depth' => 0,
            ];
            $bianArr['buy'][] = [
                'price' => '连接错误',
                'depth' => 0,
            ];
        } catch (\Throwable $e) {
            $bianArr['status'] = 500;
            $bianArr['sell'][] = [
                'price' => '连接错误',
                'depth' => 0,
            ];
            $bianArr['buy'][] = [
                'price' => '连接错误',
                'depth' => 0,
            ];
        }

        return response()->json($bianArr);
    }

    public function bitfinexTradeInfo(Request $request)
    {
        $symbolInput = $request->input('symbol');

        $symbol = !empty($symbolInput) ? $this->symbolMap[$symbolInput] : $this->symbolMap['default'];

        //bitfinex
        $bitfinexResponse = (new Client())->get('https://api.bitfinex.com/v1/book/' . $symbol['bitfinex'])->getBody()->getContents();
        $bitfinexArr = [
            'status' => 200,
            'sell' => [],
            'buy' => [],
        ];
        try {
            $bitfinexResponse = json_decode($bitfinexResponse, true);
            /***
             * bids买,asks卖
             * 买价从高到低排序,卖价从低到高排序
             */
            $i = 0;
            foreach (array_reverse($bitfinexResponse['bids']) as $row) {
                $bitfinexArr['buy'][] = [
                    'price' => $row['price'],
                    'depth' => $row['amount'],
                ];
                $i++;
                if ($i >= 10) {
                    break;
                }
            }
            $i = 0;
            foreach ($bitfinexResponse['asks'] as $row) {
                $bitfinexArr['sell'][] = [
                    'price' => $row['price'],
                    'depth' => $row['amount'],
                ];
                $i++;
                if ($i >= 10) {
                    break;
                }
            }
        } catch (\Exception $e) {
            $bitfinexArr['status'] = 500;
            $bitfinexArr['sell'][] = [
                'price' => '连接错误',
                'depth' => 0,
            ];
            $bitfinexArr['buy'][] = [
                'price' => '连接错误',
                'depth' => 0,
            ];
        } catch (\Throwable $e) {
            $bitfinexArr['status'] = 500;
            $bitfinexArr['sell'][] = [
                'price' => '连接错误',
                'depth' => 0,
            ];
            $bitfinexArr['buy'][] = [
                'price' => '连接错误',
                'depth' => 0,
            ];
        }


        return response()->json($bitfinexArr);
    }

    public function bittrexTradeInfo(Request $request)
    {
        $symbolInput = $request->input('symbol');

        $symbol = !empty($symbolInput) ? $this->symbolMap[$symbolInput] : $this->symbolMap['default'];

        //bittrex
        $bittrexArr = [
            'status' => 200,
            'sell' => [],
            'buy' => [],
        ];
        try {
            $bittrexResponse = (new Client())->get('https://bittrex.com/api/v1.1/public/getorderbook', [
                'query' => [
                    'market' => $symbol['bittrex'],
                    'type' => 'both',
                ]
            ])->getBody()->getContents();
            $bittrexResponse = json_decode($bittrexResponse, true);
            $i = 0;
            foreach ($bittrexResponse['result']['buy'] as $row) {
                $bittrexArr['buy'][] = [
                    'price' => $row['Rate'],
                    'depth' => $row['Quantity'],
                ];
                $i++;
                if ($i >= 10) {
                    break;
                }
            }
            $i = 0;
            foreach ($bittrexResponse['result']['sell'] as $row) {
                $bittrexArr['sell'][] = [
                    'price' => $row['Rate'],
                    'depth' => $row['Quantity'],
                ];
                $i++;
                if ($i >= 10) {
                    break;
                }
            }
        } catch (\Exception $exception) {
            $bittrexArr['status'] = 500;
            $bittrexArr['sell'][] = [
                'price' => '连接错误',
                'depth' => 0,
            ];
            $bittrexArr['buy'][] = [
                'price' => '连接错误',
                'depth' => 0,
            ];
        } catch (\Throwable $exception) {
            $bittrexArr['status'] = 500;
            $bittrexArr['sell'][] = [
                'price' => '连接错误',
                'depth' => 0,
            ];
            $bittrexArr['buy'][] = [
                'price' => '连接错误',
                'depth' => 0,
            ];
        }

        return response()->json($bittrexArr);
    }

    public function gateTradeInfo(Request $request)
    {
        $symbolInput = $request->input('symbol');

        $symbol = !empty($symbolInput) ? $this->symbolMap[$symbolInput] : $this->symbolMap['default'];

        //gate
        $gateArr = [
            'status' => 200,
            'sell' => [],
            'buy' => [],
        ];
        try {
            $gateResponse = (new Client())->get('http://data.gate.io/api2/1/orderBook/' . $symbol['gate'])->getBody()->getContents();
            $gateResponse = json_decode($gateResponse,true);
            $i = 0;
            foreach (array_reverse($gateResponse['asks']) as $row) {
                $gateArr['sell'][] = [
                    'price' => $row[0],
                    'depth' => $row[1],
                ];
                $i++;
                if ($i >= 10) {
                    break;
                }
            }
            $i = 0;
            foreach ($gateResponse['bids'] as $row) {
                $gateArr['buy'][] = [
                    'price' => $row[0],
                    'depth' => $row[1],
                ];
                $i++;
                if ($i >= 10) {
                    break;
                }
            }
        } catch (\Exception $exception) {
            $gateArr['status'] = 500;
            $gateArr['sell'] = [
                'price' => '连接错误',
                'depth' => 0,
            ];
            $gateArr['buy'] = [
                'price' => '连接错误',
                'depth' => 0,
            ];
        } catch (\Throwable $exception) {
            $gateArr['status'] = 500;
            $gateArr['sell'] = [
                'price' => '连接错误',
                'depth' => 0,
            ];
            $gateArr['buy'] = [
                'price' => '连接错误',
                'depth' => 0,
            ];
        }

        return response()->json($gateArr);
    }
}
