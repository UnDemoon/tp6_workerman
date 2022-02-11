<?php

declare(strict_types=1);

namespace app\user\controller;

use think\facade\View;

class Index
{
    public function index()
    {
        View::assign('user_id', time());
//        return '您好！这是一个[user]示例应用';
        return View::fetch('index');
    }
}
