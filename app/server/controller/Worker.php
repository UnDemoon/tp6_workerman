<?php

namespace app\server\controller;

use think\worker\Server;

define('HEARTBEAT_TIME', 20); // 心跳间隔55秒

class Worker extends Server
{
    protected $socket = 'http://0.0.0.0:2345';

    public function __construct()
    {
        parent::__construct();
        $this->onWorkerStart();
        // 或者这样调用
//        $this->worker->onWorkerStart = function ($worker) {
//
//            echo "Worker starting...\n";
//        };
        $this->onConnect();
        $this->onMessage();
    }

    /**
     *  Worker子进程启动时的回调函数，每个子进程启动时都会执行。
     *
     * @return void
     */
    public function onWorkerStart()
    {
        $this->worker->onWorkerStart = function ($worker) {
            echo "Worker starting...\n";
        };
    }

    /**
     * 当客户端与Workerman建立连接时(TCP三次握手完成后)触发的回调函数。
     * 每个连接只会触发一次onConnect回调。
     *
     * @return void
     */
    public function onConnect()
    {
        $this->worker->onConnect = function ($connection) {
            $connection->onWebSocketConnect = function ($connection, $http_header) {
                //  workerman是多进程的，每个进程内部会维护一个自增的connection id，所以多个进程之间的connection id会有重复。
                //  如果想要不重复的connection id 可以根据需要给connection->id重新赋值，例如加上worker->id前缀。
                // 查找用户的逻辑 (用token查询到对应的用户)
                // 将用户id和$connection->id以一对一关系存入数据库表，在连接和断开时维护该表
                if ($_GET['token']) {
                    $connection->id = $_GET['token'];
                    $connection->group = $_GET['room_id'];
                }
                echo 'new connection from ip ' . $connection->getRemoteIp() . "\n";
                // 可以在这里判断连接来源是否合法，不合法就关掉连接
                echo 'onConnect';
            };
        };
    }

    /**
     * 收到信息.
     */
    public function onMessage()
    {
        $this->worker->onMessage = function ($current_con, $data) {
            $info = json_decode($data, true);
            $to_id = $info['to_user_id'] ?? 0;

            // 转发给当前进程所维护的其它所有客户端
            foreach ($current_con->worker->connections as $con) {
                // 当单人消息
                if ($to_id) {
                    if ($con->id == $to_id) {
                        $con->send($data);
                    }
                } // 组消息
                else {
                    if ($con->group == $current_con->group) {
                        $con->send($data);
                    }
                }
            }
        };
    }
}
