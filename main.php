<?php
declare(strict_types=1);

use DyarWeb\Base;
use DyarWeb\DB\DB;
use DyarWeb\Get;
use DyarWeb\SendRequest\Send;


require_once 'vendor/autoload.php';

$tg = new Base();
$DB = DB::Database();
$offset = 0;
$time = false;
$admins = explode(',', getenv('ADMINS'));

$homeMarkup = Send::replyKeyboardMarkup([
    ['جدید', 'آمار'],
    ['حذف همه']
], true);

while (true) {
    $res = $tg->pollUpdates($offset, 0, 1);
    if ($res->ok) {
        foreach ($res->result as $data) {
            Get::set($data);
            if (date('H:i') == '20:00') {
                $markup = Send::replyKeyboardMarkup([
                    ['خیر', 'بله']
                ], true);
                foreach ($admins as $admin) Send::sendMessage($admin, 'سلام امروز کار کردین؟', null, false, false, null, $markup);
            }
            if (in_array(Get::$chat_id, $admins)) {
                if (Get::$text == 'خیر') {
                    Send::sendMessage(Get::$chat_id, 'اوکی', null, false, false, null, $homeMarkup);
                } elseif (Get::$text == '/start') {
                    Send::sendMessage(Get::$chat_id, 'منوی اصلی', null, false, false, null, $homeMarkup);
                } elseif (Get::$text == 'بله' || Get::$text == 'جدید') {
                    $markup = Send::replyKeyboardMarkup([
                        ['نصف روز', 'تمام روز']
                    ], true);
                    Send::sendMessage(Get::$chat_id, 'چقدر؟', null, false, false, null, $markup);
                } elseif (Get::$text == 'تمام روز' || Get::$text == 'نصف روز') {
                    if (Get::$text == 'تمام روز') $time = 1;
                    elseif (Get::$text == 'نصف روز') $time = 0.5;
                    Send::sendMessage(Get::$chat_id, 'لطفا نام صاحب کار را ارسال کنید', null, false, false, null, Send::ReplyKeyboardRemove());
                } elseif ($time && Get::$text) {
                    $data = $DB->SelectData('Work', 'Work');
                    $NewTime = (end($data)->time ?? 0) + $time;
                    $DB->InsertData('Work', 'Work',
                        [
                            'date' => jdate('l'),
                            'employer' => Get::$text,
                            'time' => $NewTime
                        ]);
                    Send::sendMessage(Get::$chat_id, 'ثبت شد', null, false, false, null, $homeMarkup);
                    unset($time);
                } elseif (Get::$text == 'آمار') {
                    $data = $DB->SelectData('Work', 'Work');
                    if ($data) {
                        $msg = 'آمار :' . PHP_EOL . PHP_EOL;
                        foreach ($data as $work) {
                            $msg .= $work->time . ' روز ' . $work->date . ' ' . $work->employer . PHP_EOL . PHP_EOL;
                        }
                    } else $msg = 'لیست شما خالی هست.';
                    Send::sendMessage(Get::$chat_id, $msg);
                } elseif (Get::$text == 'حذف همه') {
                    $markup = Send::replyKeyboardMarkup([
                        ['بله حذف کن', 'خیر']
                    ], true);
                    Send::sendMessage(Get::$chat_id, 'مطمئنی؟', null, false, false, null, $markup);
                } elseif (Get::$text == 'بله حذف کن') {
                    $DB->DeleteTable('Work','Work');
                    Send::sendMessage(Get::$chat_id, 'لیست با موفقیت حذف شد', null, false, false, null, $homeMarkup);
                }
            } else Send::sendMessage(Get::$chat_id, 'ربات خصوصی هست و شما اجازه دسترسی نداری' . PHP_EOL . PHP_EOL . 'By : https://DyarWeb.com');
        }
        $offset = end($res->result)->update_id + 1;
    } else {
        exit();
    }
}
