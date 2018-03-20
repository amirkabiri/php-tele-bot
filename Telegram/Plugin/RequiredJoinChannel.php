<?php

namespace Telegram\Plugin;

class RequiredJoinChannel
{
    private $channel_id;

    public function __construct($channel_id)
    {
        $this->channel_id = ltrim($channel_id, '@');
    }

    public function run($req, $res, $next){
        $result = $res->getChatMember([
            'chat_id' => '@'.$this->channel_id,
            'user_id' => $req->from->id
        ]);
        if($result->ok && in_array($result->result->status, ['creator', 'administrator', 'member'])){
            $next();
        }else{
            $res->sendMessage([
                'chat_id' => $req->chat->id,
                'reply_to_message_id' => $req->message_id,
                'text' => 'برای استفاده از ربات لطفا ابتدا در کانال زیر عضو شده سپس دوباره روی /start کلیک کنید تا ربات برای شما فعال شود',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => 'برای عضو شدن در کانال اینجا کلیک کنید', 'url' => 'https://t.me/'.$this->channel_id]]
                    ]
                ])
            ]);
        }
    }

}