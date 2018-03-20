<?php

namespace Telegram\Plugin;

class Keyboard
{
    private $keyboard = [];
    private $buttons = [];
    private $resize = true;
    private $oneTime = false;
    private $selective = false;
    private $remove = false;

    public function __construct()
    {

    }
    public function create(){
        if(count($this->buttons)){
            $this->keyboard[] = $this->buttons;
            $this->buttons = [];
        }
        return json_encode([
            'remove_keyboard' => $this->remove,
            'keyboard' => $this->keyboard,
            'resize_keyboard' => $this->resize,
            'one_time_keyboard' => $this->oneTime,
            'selective' => $this->selective
        ]);
    }
    public function btn($text){
        $this->buttons[] = ['text' => $text];
        return $this;
    }
    public function contactBtn($text){
        $this->buttons[] = ['text' => $text, 'request_contact' => true];
        return $this;
    }
    public function LocationBtn($text){
        $this->buttons[] = ['text' => $text, 'request_location' => true];
        return $this;
    }
    public function nextRow(){
        if(count($this->buttons)){
            $this->keyboard[] = $this->buttons;
            $this->buttons = [];
        }
        return $this;
    }
    public function resize($resize = true)
    {
        $this->resize = $resize;
        return $this;
    }
    public function oneTime($oneTime = false)
    {
        $this->oneTime = $oneTime;
        return $this;
    }
    public function selective($selective = false){
        $this->selective = $selective;
        return $this;
    }
    public function remove($remove = false){
        $this->remove = $remove;
        return $this;
    }
    public function __destruct()
    {
        unset($this->buttons);
        unset($this->keyboard);
    }
}