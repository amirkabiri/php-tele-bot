<?php

namespace Telegram\Plugin;
use stdClass;
use Exception;

class Form
{
    private $fields = [];
    private $fdb;
    private $form_name;
    private $on = ['end' => null, 'cancel' => null];
    private $cancel_command;

    public function __construct($form_name, $cancel_command = '/cancel')
    {
        $this->cancel_command = $cancel_command;
        $this->form_name = $form_name;
        $this->fdb = new Fdb('Telegram/data');
        $this->fdb->schema('form', ['start' => false, 'form' => new stdClass]);
    }
    public function newField($name, $title, $validation, $keyboard = null){
        if($keyboard === null){
            $keyboard = [];
        }elseif (is_array($keyboard)){
            if(isset($keyboard['keyboard'])){
                $keyboard = $keyboard['keyboard'];
            }
        }elseif(is_string($keyboard)){
            try{
                $keyboard = json_decode($keyboard, true);
                $keyboard = $keyboard['keyboard'];
            }catch (Exception $e){
                $keyboard = [];
            }
        }
        $this->fields[] = ['name' => $name, 'title' => $title, 'validation' => $validation, 'keyboard' => $keyboard];
        return $this;
    }
    public function run($req, $res, $next){
        if(!$this->fdb->exists('form', $req->from->id)) return $next();
        $form = $this->fdb->select('form', $req->from->id);
        if($form->start !== $this->form_name) return $next();

        if(isset($req->text) && $req->text === $this->cancel_command){
            if(is_callable($this->on['cancel'])){
                $result = $res->sendMessage([
                    'chat_id' => $req->from->id,
                    'text' => 'form canceled',
                    'reply_markup' => json_encode([
                        'remove_keyboard' => true
                    ])
                ]);
                if($result->ok){
                    $res->deleteMessage([
                        'chat_id' => $result->result->chat->id,
                        'message_id' => $result->result->message_id
                    ]);
                }
                $this->on['cancel']($req, $res, $this->fdb->select('form', $req->from->id)->form);
                $this->fdb->delete('form', $req->from->id);
            }
            return;
        }

        foreach ($this->fields as $index => $field){
            if(!isset($form->form->{$field['name']})){
                $result = $field['validation']($req, $res);
                if($result['ok']){
                    $form->form->{$field['name']} = $result['save'];
                    $this->fdb->update('form', $req->from->id, ['form' => $form->form]);

                    if(isset($this->fields[$index+1])){
                        $nextField = $this->fields[$index+1];
                        if($this->on['cancel'] !== null) $nextField['keyboard'][] = [$this->cancel_command];
                        $res->sendMessage([
                            'chat_id' => $req->from->id,
                            'text' => 'لطفا '. $nextField['title']. ' را وارد کنید',
                            'reply_markup' => json_encode([
                                'keyboard' => $nextField['keyboard'],
                                'resize_keyboard' => true
                            ])
                        ]);
                    }elseif(is_callable($this->on['end'])){
                        $result = $res->sendMessage([
                            'chat_id' => $req->from->id,
                            'text' => 'form completed successfully',
                            'reply_markup' => json_encode([
                                'remove_keyboard' => true
                            ])
                        ]);
                        if($result->ok){
                            $res->deleteMessage([
                                'chat_id' => $result->result->chat->id,
                                'message_id' => $result->result->message_id
                            ]);
                        }
                        $this->on['end']($req, $res, $this->fdb->select('form', $req->from->id)->form);
                        $this->fdb->delete('form', $req->from->id);
                    }
                }
                break;
            }
        }
    }
    public function end($callback){
        $this->on['end'] = $callback;
        return $this;
    }
    public function cancel($callback){
        $this->on['cancel'] = $callback;
        return $this;
    }
    public function start($req, $res){
        if(!$this->fdb->exists('form', $req->from->id)) $this->fdb->insert('form', ['start' => $this->form_name, 'form' => new stdClass], $req->from->id);
        else $this->fdb->update('form', $req->from->id, ['start' => $this->form_name, 'form' => new stdClass]);
        $firstField = $this->fields[0];
        if($this->on['cancel'] !== null) $firstField['keyboard'][] = [$this->cancel_command];
        $res->sendMessage([
            'chat_id' => $req->from->id,
            'text' => 'لطفا '. $firstField['title']. ' را وارد کنید',
            'reply_markup' => json_encode([
                'keyboard' => $firstField['keyboard'],
                'resize_keyboard' => true
            ])
        ]);
    }
}