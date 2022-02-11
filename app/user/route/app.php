<?php

use think\facade\Route;

Route::group(function () {
    Route::get('index', 'Index/index');
})->prefix('\app\user\controller\\');
