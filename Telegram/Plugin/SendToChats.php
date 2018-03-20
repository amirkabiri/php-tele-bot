<?php

namespace Telegram\Plugin;

use Telegram\Plugin\Fdb as Fdb;
use Telegram\Plugin\Api as Api;
use stdClass;

class SendToChats
{
    private $fdb;
    private $api;

    public function __construct($token)
    {
        $this->api = new Api($token);
        $this->fdb = new Fdb('Telegram/data');
        $this->fdb->schema('stc_users', ['timestamp' => time()]);
        $this->fdb->schema('stc_groups', ['timestamp' => time()]);
        $this->fdb->schema('stc_super_groups', ['timestamp' => time()]);
        $this->fdb->schema('stc_channels', ['timestamp' => time()]);
    }

    public function run($req, $res, $next)
    {
        switch ($req->chat->type) {
            case 'private':
                if (!$this->fdb->exists('stc_users', $req->chat->id)) $this->fdb->insert('stc_users', [], $req->chat->id);
                break;
            case 'group':
                if (!$this->fdb->exists('stc_groups', $req->chat->id)) $this->fdb->insert('stc_groups', [], $req->chat->id);
                break;
            case 'supergroup':
                if (!$this->fdb->exists('stc_super_groups', $req->chat->id)) $this->fdb->insert('stc_super_groups', [], $req->chat->id);
                break;
        }
        if (isset($req->migrate_from_chat_id)) {
            $this->fdb->delete('stc_groups', $req->migrate_from_chat_id);
            $this->fdb->insert('stc_super_groups', [], $req->chat->id);
        }

        $next();
    }
    public function send($to, $callback){
        set_time_limit(0);

        $ids = [];
        foreach ($to as $item){
            switch ($item){
                case 'private':
                    $ids = array_merge($ids, $this->fdb->scan('stc_users'));
                    break;
                case 'group':
                    $ids = array_merge($ids, $this->fdb->scan('stc_groups'));
                    break;
                case 'super_group':
                    $ids = array_merge($ids, $this->fdb->scan('stc_super_groups'));
                    break;
                case 'channel':
                    $ids = array_merge($ids, $this->fdb->scan('stc_channels'));
                    break;
            }
        }
        $counter = 0;
        foreach ($ids as $id){
            $counter++;
            if($counter >= 10){
                sleep(10);
                $counter = 0;
            }
            $callback($id, $this->api);
        }
    }
}