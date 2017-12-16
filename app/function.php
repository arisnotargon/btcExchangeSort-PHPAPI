<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;

use App\Exceptions\AppException;
use Carbon\Carbon;

function service($action, $payload = [])
{
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    socket_connect($socket, env('SERVICE_HOST'), 5454);
    socket_write($socket, json_encode([
        'method' => $action,
        'body'   => $payload,
    ]));
    $response = json_decode(socket_read($socket, 4096), true);
    socket_shutdown($socket, 2);
    socket_close($socket);

    if (empty($response) || $response['error'] !== null) {
        throw new AppException(json_encode($response['error']));
    }

    return $response['result'];
}

function sendSMS($mobile, $text)
{
    if (!empty($mobile) && !empty($text)) {

        service('sms/send', [
            'mobile' => $mobile,
            'text'   => $text,
        ]);
    }
}

function sendMobileCode($mobile, $challenge, $validate, $seccode)
{
    if (service('geetest/validate', [
        'challenge' => $challenge,
        'validate'  => $validate === null ? '_' : $validate,
        'seccode'   => $seccode,
    ])) {
        $code = mt_rand(100000, 999999);
        Cache::put('MOBILE_CODE_' . $mobile, $code, 5);
        sendSMS($mobile, '您的验证码是' . $code);

        return true;
    }

    return false;
}

function verifyMobileCode($mobile, $code)
{
    $re = false;
    if ($code !== null && Cache::get('MOBILE_CODE_' . $mobile) === (int)$code) {
        $re = true;
        Cache::forget('MOBILE_CODE_' . $mobile);
    }
    return $re;
}

function dwz($url)
{
    try {
        $response = (new Client())->get('http://api.weibo.com/2/short_url/shorten.json', ['query' => [
            'source'   => '2280714968',
            'url_long' => $url,
        ]])->getBody()->getContents();

        $response = json_decode($response);
    } catch (TransferException $e) {
        return $url;
    }

    return isset($response->urls[0]->url_short) ? $response->urls[0]->url_short : $url;
}

function weekDayParseChinese($weekDay)
{
    $re = '';
    switch ($weekDay) {
        case 1:
            $re = '星期一';
            break;
        case 2:
            $re = '星期二';
            break;
        case 3:
            $re = '星期三';
            break;
        case 4:
            $re = '星期四';
            break;
        case 5:
            $re = '星期五';
            break;
        case 6:
            $re = '星期六';
            break;
        case 0:
            $re = '星期天';
            break;
        default :
            $re = '没这天';
            break;
    }
    return $re;
}

function checkHoliday($start, $end)
{
    static $smallHoliday = [
        [
            'name'  => '元旦',
            'start' => '2017-12-30',
            'end'   => '2018-01-01',
        ],
        [
            'name'  => '踏青',
            'start' => '2018-04-05',
            'end'   => '2018-04-07',
        ],
        [
            'name'  => '五一',
            'start' => '2018-05-01',
            'end'   => '2018-05-03',
        ],
        [
            'name'  => '端午',
            'start' => '2018-06-16',
            'end'   => '2018-06-18',
        ],
        [
            'name'  => '中秋',
            'start' => '2018-06-16',
            'end'   => '2018-06-18',
        ],
    ];

    static $bigHoliday = [
        [
            'name'  => '国庆节',
            'start' => '2018-10-01',
            'end'   => '2018-10-07',
        ],
        [
            'name'  => '春节',
            'start' => '2018-02-15',
            'end'   => '2018-02-21',
        ],
    ];

    $start = $start instanceof Carbon ? $start : Carbon::parse($start);
    $end   = $end instanceof Carbon ? $end : Carbon::parse($end);

    $result = false;

    foreach ($smallHoliday as $row) {
        if ($end->gte(Carbon::parse($row['start'])) && $start->lte(Carbon::parse($row['end']))) {
            $result[] = $row;
            break;
        }
    }

    foreach ($bigHoliday as $row) {
        if ($end->gte(Carbon::parse($row['start'])) && $start->lte(Carbon::parse($row['end']))) {
            $result[] = $row;
            break;
        }
    }

    return $result;
}

function rangeArray($from, $to, $step = 1)
{
    $rever = false;
    if ($from > $to) {
        list($from, $to) = [$to, $from];
        $rever = true;
    }
    $array = [];
    for ($x = $from; $x <= $to; $x += $step) {
        $array[] = $x;
    }
    if ($rever) {
        $array = array_reverse($array);
    }
    return $array;
}

function inTime($time, $start, $end)
{
    $time  instanceof Carbon || Carbon::parse($time);
    $start instanceof Carbon || Carbon::parse($start)->startOfDay();
    $end   instanceof Carbon || Carbon::parse($end)->endOfDay();

    return ($time->gte($start) && $time->lte($end));
}