<?php

require 'Telegram/App.php';

use Telegram\Plugin\Router as Router;
use Telegram\Plugin\InlineKeyboard as InlineKeyboard;

$router = new Router('Your Bot Token');

$router->pv('/^\/start$/', function($req, $res, $next){
    $inlineKeyboard = new InlineKeyboard();
    $inlineKeyboard->dataBtn('Sign Up', 'sign_up');
    $inlineKeyboard->nextRow();
    $inlineKeyboard->urlBtn('Our Site', 'site.com');

    $res->sendMessage([
        'chat_id' => $req->from->id,
        'text' => 'hello!',
        'reply_markup' => $inlineKeyboard->create()
    ]);
});

$router->pv(function ($req, $res, $next){
    $res->sendMessage([
        'chat_id' => $req->from->id,
        'text' => 'command not found'
    ]);
});

$router->handle();