<?php

use Workerman\Worker;
use Workerman\WebServer;
use Workerman\Lib\Timer;
use PHPSocketIO\SocketIO;

include __DIR__ . '/vendor/autoload.php';

// 全局数组保存uid在线数据
$uidConnectionMap = array();
// 记录最后一次广播的在线用户数
$last_online_count = 0;

// PHPSocketIO服务
$sender_io = new SocketIO(2120);
// 客户端发起连接事件时，设置连接socket的各种事件回调
$sender_io->on('connection', function ($socket) {
    // 当客户端发来登录事件时触发
    $socket->on('login', function ($uid) use ($socket) {
        global $uidConnectionMap;
        if (!$uid) {
            return;
        }
//        if (isset($socket->uid)) {
//            return;
//        }
        $uid = (string)$uid;
        // 判断已经登录过了
//        if (!checkHasIn($uid)) {
        // 更新对应uid的在线数据
        if (!isset($uidConnectionMap[$uid])) {
            $uidConnectionMap[$uid] = array('uid' => $uid, 'connect_time' => date('Y-m-d H:i:s', time()), 'count' => 1);
        } else {
            $uidConnectionMap[$uid]['count']++;
            $uidConnectionMap[$uid]['connect_time'] = date('Y-m-d H:i:s', time());
        }
        // 将这个连接加入到uid分组，方便针对uid推送数据
        $socket->join($uid);
        $socket->uid = $uid;
        // 更新这个socket对应页面的在线数据
        $socket->emit('update_online_uids', json_encode($uidConnectionMap));
//        }
    });

    // 当客户端发来退出事件时触发
    $socket->on('logout', function ($uid) use ($socket) {
        if (!$uid) {
            return;
        }
        global $uidConnectionMap, $sender_io;
        $uidConnectionMap[$uid]['count']--;
        if ($uidConnectionMap[$uid]['count'] <= 0) {
            unset($uidConnectionMap[$uid]);
        }
        // 移除socket
//        $connection = getConnectionByUid($uid);
//        if ($connection) {
//            array_splice($uidConnectionMap, $connection['key'], 1);
//        }
    });
    // 当客户端断开连接是触发（一般是关闭网页或者跳转刷新导致）
    $socket->on('disconnect', function () use ($socket) {
        if (!isset($socket->uid)) {
            return;
        }
        global $uidConnectionMap, $sender_io;
        // 移除socket
        $uidConnectionMap[$socket->uid]['count']--;
        if ($uidConnectionMap[$socket->uid]['count'] <= 0) {
            unset($uidConnectionMap[$socket->uid]);
        }
//        $connection = getConnectionByUid($socket->uid);
//        if ($connection) {
//            array_splice($uidConnectionMap, $connection['key'], 1);
//        }
    });
});

// 当$sender_io启动后监听一个http端口，通过这个端口可以给任意uid或者所有uid推送数据
$sender_io->on('workerStart', function () {
    // 监听一个http端口
    $inner_http_worker = new Worker('http://127.0.0.1:2021');
    // 当http客户端发来数据时触发
    $inner_http_worker->onMessage = function ($http_connection, $data) {
        global $uidConnectionMap, $sender_io;
        $_POST = $_POST ? $_POST : $_GET;
        // 推送数据的url格式 type=publish&to=uid&content=xxxx

        $notice_type = @$_POST['type'];
        $to = @$_POST['to'];
//        $_POST['content'] = htmlspecialchars(@$_POST['content']);
        // 有指定uid则向uid所在socket组发送数据
        if ($to) {
            $sender_io->to($to)->emit($notice_type, $_POST['content']);
            // 否则向所有uid推送数据
        } else {
            $sender_io->emit($notice_type, @$_POST['content']);
        }

        // http接口返回，如果用户离线socket返回fail
        if ($to && !checkHasIn($to)) {
            return $http_connection->send('offline');
        } else {
            return $http_connection->send('ok');
        }

        return $http_connection->send('fail');
    };
    // 执行监听
    $inner_http_worker->listen();

    // 一个定时器，定时向所有uid推送当前uid在线数
    Timer::add(1, function () {
        global $uidConnectionMap, $sender_io, $last_online_count;
        $online_count_now = count($uidConnectionMap);
        // 只有在客户端在线数变化了才广播，减少不必要的客户端通讯
        if ($last_online_count != $online_count_now) {
            $sender_io->emit('update_online_uids', json_encode($uidConnectionMap));
            $last_online_count = $online_count_now;
        }
    });
});

/**
 * 判断是否已登录
 * @param $uid
 * @return bool
 */
function checkHasIn($uid)
{
    global $uidConnectionMap;
    if ($uidConnectionMap) {
        foreach ($uidConnectionMap as $key => $item) {
            if ($item['uid'] == $uid) {
                return true;
            }
        }
    }
    return false;
}

function getConnectionByUid($uid)
{
    global $uidConnectionMap;
    if ($uidConnectionMap) {
        foreach ($uidConnectionMap as $key => $item) {
            if ($item['uid'] == $uid) {
                return array('key' => $key, 'item' => $item);
            }
        }
    }
    return false;
}

if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
