<?php

namespace Telegram\Plugin;

use Telegram\Plugin\Api as Api;

class Router {
    private $api;
    private $update;
    public $methods = [
        'message' => [],
        'callbackQuery' => [],
        'inlineQuery' => []
    ];
    private $handled = false;
    private $currentMsgRoute = 0;
    private $currentCallbackRoute = 0;

    const ANY = ['any'];
    const ANY_TEXT = ['any'];
    const ANY_CAPTION = ['any'];
    const LEFT = ['left'];
    const JOIN = ['join'];
    const LOCATION = ['location'];
    const CONTACT = ['contact'];
    const VIDEO_NOTE = ['video_note'];
    const VOICE = ['voice'];
    const VIDEO = ['video'];
    const STICKER = ['sticker'];
    const PHOTO = ['photo'];
    const GAME = ['game'];
    const DOCUMENT = ['document'];
    const AUDIO = ['audio'];

    private $last_route = null;
    private $middleware = [];

    public function __construct($token)
    {
        $this->api = new Api($token);
    }

    public function handle(){
        $this->update = json_decode(file_get_contents('php://input'));
        if(!empty($this->update)){
            if (isset($this->update->message)){
                $this->api->setChatId($this->update->message->chat->id);
                $this->processMessage();
            }elseif (isset($this->update->callback_query)){
                $this->api->setChatId($this->update->callback_query->from->id);
                $this->processCallbackQuery();
            }
        }
    }
    private function processCallbackQuery(){
        if(isset($this->methods['callbackQuery'][$this->currentCallbackRoute])){
            $route = $this->methods['callbackQuery'][$this->currentCallbackRoute];
            unset($this->methods['callbackQuery'][$this->currentCallbackRoute]);
            $this->currentCallbackRoute++;

            if(is_string($route['route']) && preg_match($route['route'], $this->update->callback_query->data, $matches)){
                $req = $this->update->callback_query;
                $req->match = $matches;
                $this->handleCallbackRoute($req, $route);
            }elseif($route['route'] === Router::ANY){
                $this->handleCallbackRoute($this->update->callback_query, $route);
            }

            if(!$this->handled) $this->processCallbackQuery();
        }
    }
    private function processMessage(){
        if(isset($this->methods['message'][$this->currentMsgRoute])){
            $route = $this->methods['message'][$this->currentMsgRoute];
            unset($this->methods['message'][$this->currentMsgRoute]);
            $this->currentMsgRoute++;

            if($route['type'] === 'message' ||
                ($route['type'] === 'pv' && $this->update->message->chat->type === 'private') ||
                ($route['type'] === 'group' && $this->update->message->chat->type === 'supergroup') ||
                ($route['type'] === 'group' && $this->update->message->chat->type === 'group') ){
                if(is_string($route['route'])){
                    if(isset($this->update->message->text) && preg_match($route['route'], $this->update->message->text, $matches)){
                        $req = $this->update->message;
                        $req->match = $matches;
                        $this->handleMessageRoute($req, $route);
                    }
                }elseif (is_array($route['route'])){
                    if($route['route'] === self::ANY){
                        $this->handleMessageRoute($this->update->message, $route);
                    }elseif ($route['route'] === self::ANY_TEXT && isset($this->update->message->text)){
                        $this->handleMessageRoute($this->update->message, $route);
                    }elseif ($route['route'] === self::ANY_CAPTION && isset($this->update->message->caption)){
                        $this->handleMessageRoute($this->update->message, $route);
                    }elseif (isset($this->update->message->{$route['route'][0]})){
                        $this->handleMessageRoute($this->update->message, $route);
                    }elseif ($route['route'] === self::JOIN && isset($this->update->message->new_chat_members)){
                        $this->handleMessageRoute($this->update->message, $route);
                    }elseif ($route['route'] === self::LEFT && isset($this->update->message->left_chat_member)){
                        $this->handleMessageRoute($this->update->message, $route);
                    }
                }
            }

            if(!$this->handled) $this->processMessage();
        }
    }

    public function handleMessageRoute($req, $route){
        if(isset($route['middleware']) && $route['middleware'] !== null && !$this->checkMiddleware($route['middleware']))return;
        $this->handled = true;

        if(is_array($route['callback'])){
            call_user_func_array($route['callback'], [$req, $this->api, function (){
                $this->handled = false;
                $this->processMessage();
            }]);
        }else{
            $route['callback']($req, $this->api, function (){
                $this->handled = false;
                $this->processMessage();
            });
        }
    }
    public function handleCallbackRoute($req, $route){
        if(isset($route['middleware']) && !$this->checkMiddleware($route['middleware']))return;
        $this->handled = true;

        if(is_array($route['callback'])){
            call_user_func_array([$route['callback'][0],$route['callback'][1]], [$req, $this->api, function (){
                $this->handled = false;
                $this->processCallbackQuery();
            }]);
        }else{
            $route['callback']($req, $this->api, function (){
                $this->handled = false;
                $this->processCallbackQuery();
            });
        }
    }


    public function message($arg1, $arg2 = null){
        $route = !is_null($arg2) ? $arg1 : Router::ANY;
        $callback = !is_null($arg2) ? $arg2 : $arg1;
        $this->methods['message'][] = ['type' => __FUNCTION__, 'route' => $route, 'callback' => $callback, 'middleware' => null];
        $this->last_route = &$this->methods['message'][count($this->methods['message'])-1];
        return $this;
    }
    public function group($arg1, $arg2 = null){
        $route = !is_null($arg2) ? $arg1 : Router::ANY;
        $callback = !is_null($arg2) ? $arg2 : $arg1;
        $this->methods['message'][] = ['type' => __FUNCTION__, 'route' => $route, 'callback' => $callback, 'middleware' => null];
        $this->last_route = &$this->methods['message'][count($this->methods['message'])-1];
        return $this;
    }
    public function pv($arg1, $arg2 = null){
        $route = !is_null($arg2) ? $arg1 : Router::ANY;
        $callback = !is_null($arg2) ? $arg2 : $arg1;
        $this->methods['message'][] = ['type' => __FUNCTION__, 'route' => $route, 'callback' => $callback, 'middleware' => null];
        $this->last_route = &$this->methods['message'][count($this->methods['message'])-1];
        return $this;
    }

    public function data($arg1, $arg2 = null){
        $route = !is_null($arg2) ? $arg1 : Router::ANY;
        $callback = !is_null($arg2) ? $arg2 : $arg1;
        $this->methods['callbackQuery'][] = ['route' => $route, 'callback' => $callback, 'middleware' => null];
        $this->last_route = &$this->methods['callbackQuery'][count($this->methods['callbackQuery'])-1];
        return $this;
    }

    public function middleware($middleware){
        $this->last_route['middleware'][] = $middleware;
        return $this;
    }
    public function setMiddleware($name, $callback){
        $this->middleware[$name] = ['result' => null, 'callback' => $callback];
        return $this;
    }
    private function checkMiddleware($middlewares){
        foreach ($middlewares as $middleware){
            if($this->middleware[$middleware]['result'] === null){
                $this->middleware[$middleware]['result'] = $this->middleware[$middleware]['callback']($this->update, $this->api);
            }
            if(!$this->middleware[$middleware]['result']) return false;
        }
        return true;
    }
}


