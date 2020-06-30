<?php
declare(strict_types=1);

use DyarWeb\Base;
use DyarWeb\Get;
use DyarWeb\SendRequest\Send;


require_once 'vendor/autoload.php';

$tg = new Base();
$offset = 0;
$time = false;
$admins = explode(',', getenv('ADMINS'));

while (true) {
    $res = $tg->pollUpdates($offset, 0, 1);
    if ($res->ok) {
        foreach ($res->result as $data) {
            Get::set($data);
            if (date('H:i') == '21:00' || Get::$text == 'جدید') {
                $markup = Send::replyKeyboardMarkup([
                    ['خیر', 'بله']
                ], true);
                foreach ($admins as $admin) Send::sendMessage($admin, 'سلام امروز کار کردین؟', null, false, false, null, $markup);
            }
            if (in_array(Get::$chat_id,$admins)) {
                if (Get::$text == 'خیر') {
                    Send::sendMessage(Get::$chat_id, 'اوکی', null, false, false, null, Send::ReplyKeyboardRemove());
                } elseif (Get::$text == 'بله') {
                    $markup = Send::replyKeyboardMarkup([
                        ['نصف روز', 'تمام روز']
                    ], true);
                    Send::sendMessage(Get::$chat_id, 'چقدر؟', null, false, false, null, $markup);
                } elseif (Get::$text == 'تمام روز' || Get::$text == 'نصف روز') {
                    $time = Get::$text;
                    Send::sendMessage(Get::$chat_id, 'لطفا نام صاحب کار را ارسال کنید', null, false, false, null, Send::ReplyKeyboardRemove());
                } elseif ($time && Get::$text) {
                    $new_file = [
                        [
                            'Programmer' => '@AlirezaSawari'
                        ]
                    ];
                    if (!file_exists('Work.json')) file_put_contents('Work.json', json_encode($new_file));
                    $work = json_decode(file_get_contents('Work.json'));
                    $work [] = [
                        'date' => jdate('Y / F / j'),
                        'employer' => Get::$text,
                        'time' => $time
                    ];
                    file_put_contents('Work.json', json_encode($work));
                    Send::sendMessage(Get::$chat_id, 'ثبت شد');
                    unset($time);
                } elseif (Get::$text == 'امار') {
                    $new_file = [
                        [
                            'Programmer' => '@AlirezaSawari'
                        ]
                    ];
                    if (!file_exists('Work.json')) file_put_contents('Work.json', json_encode($new_file));
                    $works = json_decode(file_get_contents('Work.json'));
                    $msg = 'آمار :'.PHP_EOL;
                    foreach ($works as $work) {
                        $msg .= ' در : ' . $work->date . ' پیش : ' . $work->employer . ' ' . $work->time . ' کار کردید.' . PHP_EOL . PHP_EOL;
                    }
                    Send::sendMessage(Get::$chat_id, $msg);
                }
            }
        }
        $offset = end($res->result)->update_id + 1;
    } else {
        exit();
    }
}