<?php
require __DIR__ . '/vendor/autoload.php';

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;

$API_KEY = 'APIKEY';
$BOT_NAME = 'BOTNAME';

$username = "USERNAME";
$password = "PASSWORD";
$host = "cn.portaone.com";

// You can get chat_id via getUpdates API method
$chat_id = "CHAT ID";

while(true) {
reloadMail($username, $password, $host, $API_KEY, $BOT_NAME, 'folder1', $chat_id);
reloadMail($username, $password, $host, $API_KEY, $BOT_NAME, 'folder2', $chat_id);
sleep(5);
}

function fixtags($text){
$text = htmlspecialchars($text);
$text = preg_replace("/=/", "=\"\"", $text);
$text = preg_replace("/&quot;/", "&quot;\"", $text);
$tags = "/&lt;(\/|)(\w*)(\ |)(\w*)([\\\=]*)(?|(\")\"&quot;\"|)(?|(.*)?&quot;(\")|)([\ ]?)(\/|)&gt;/i";
$replacement = "<$1$2$3$4$5$6$7$8$9$10>";
$text = preg_replace($tags, $replacement, $text);
$text = preg_replace("/=\"\"/", "=", $text);
$text = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $text);
return $text;
}

function reloadMail($username, $password, $host, $API_KEY, $BOT_NAME, $folder, $chat_id) {
$mbox = imap_open('{'.$host.':143/novalidate-cert}'.$folder, $username, $password)
or die("can't connect: " . imap_last_error());

$MC = imap_check($mbox);

$telegram = new Telegram($API_KEY, $BOT_NAME);


// Fetch an overview for all messages in INBOX
$result = imap_fetch_overview($mbox,"1:{$MC->Nmsgs}",0);
foreach ($result as $overview) {
    $headers = imap_headerinfo($mbox, $overview->msgno);
    if($headers->Unseen == 'U') {
        $msg_from = imap_utf8($overview->from);
        $date = imap_utf8($overview->date);
        $subject = imap_utf8($overview->subject);
	$body = imap_utf8(imap_fetchbody($mbox, $overview->msgno, 1));
	$msg = "<pre>From: " . $msg_from . "</pre>";
        $msg .= "<pre>Date: " . $date . "</pre>";
        $msg .= "<pre>Subject: " . $subject . "</pre>";
	$body = strip_tags($body,'<b><strong><i><em><code><pre><a>');
	//echo $msg;	
//	$msg .= $body;
	$msg = fixtags($msg);
if ($chat_id !== '' && $msg !== '') {
            $data = [
                'chat_id' => $chat_id,
                'text' => $msg,
		'parse_mode' => 'HTML',
            ];

            $result = Request::sendMessage($data);
            if ($result->isOk()) {
                $str = 'Message sent succesfully to: ' . $chat_id;
            } else {
                $str = 'Sorry message not sent to: ' . $chat_id;
            }
$str .= $msg;
$str .= "\n";
		file_put_contents('/tmp/log.txt', $str.PHP_EOL, FILE_APPEND | LOCK_EX);
        }

    }
}
imap_close($mbox);
}

