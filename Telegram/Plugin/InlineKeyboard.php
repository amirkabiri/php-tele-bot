<?php

namespace Telegram\Plugin;

class InlineKeyboard
{
    private $buttons = [];
    private $keyboard = [];

    public function __construct(){

    }
    public function urlBtn($name, $value){
        $this->buttons[] = ['text' => $name, 'url' => $value];
        return $this;
    }
    public function dataBtn($name, $value){
        $this->buttons[] = ['text' => $name, 'callback_data' => $value];
        return $this;
    }
    public function nextRow(){
        if(count($this->buttons)){
            $this->keyboard[] = $this->buttons;
            $this->buttons = [];
        }
        return $this;
    }
    public function create(){
        if(count($this->buttons)){
            $this->keyboard[] = $this->buttons;
            $this->buttons = [];
        }
        return json_encode([
            'inline_keyboard' => $this->keyboard
        ]);
    }
    public function __destruct()
    {
        unset($this->buttons);
        unset($this->keyboard);
    }
}