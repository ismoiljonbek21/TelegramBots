<?php
require './../vendor/autoload.php';
require './../rb.php';
require './classes.php';
require './functions.php';

use Askoldex\Teletant\Bot;
use Askoldex\Teletant\Context;
use Askoldex\Teletant\Settings;
use Askoldex\Teletant\Addons\Menux;
use Askoldex\Teletant\States\Scene;
use Askoldex\Teletant\States\Stage;

$settings = new Settings('1584665860:AAEVRP9_qYjmqHdgKVKXE1y5T6bpB6oCP3g');
$settings->setHookOnFirstRequest(false);
$bot = new Bot($settings);
$stage = new Stage();
R::setup('sqlite:thisbotdb.db');
R::ext('xdispense', function ($table_name) {
    return R::getRedBean()->dispense($table_name);
});
$bot->eventMiddlewares([

    'private' => function (Context $ctx, callable $next) {
        if ($ctx->getChatType() == 'private') {
            $next($ctx);
        }
    },
    'group' => function (Context $ctx, callable $next) {
        if ($ctx->getChatType() == 'group' || $ctx->getChatType() == 'supergroup') {
            $next($ctx);
        }
    },
    'admin' => function (Context $ctx, callable $next) {
        if ($ctx->getUserID() == 1014474410) {
            $next($ctx);
        }
    },

    'channel' => function (Context $ctx, callable $next) {
        if ($ctx->getChatType() == 'channel') {
            return false;
        }

    },
]);
$bot->em('private', function (Bot $bot) {
    $bot->onStart(function (Context $ctx) {
        $key = Menux::Create('qollanma')->row()->btn('Qo\'llanma🗒','https://telegra.ph/Chat-bot-02-08');
        if ($ctx->getUserID() == 1014474410 || $ctx->getUserID() == 924057406) {
            $ctx->enter('admin');
        } else {
            $ctx->replyPhoto('https://opt-1451630.ssl.1c-bitrix-cdn.ru/upload/medialibrary/12d/Chatbot.jpg?1599641746338417',"Assalomu alaykum " . $ctx->getFullName() . " 👋\nBotimizga xush kelibsiz!😉" . "\nBotni guruhingizga qo'shing➕ va bot guruhingiz a'zolari bilan bemalol gaplasha oladi🗣.\nBiz har kuni yangidan yangi so'zlarni qoshib botni boyitib boramiz☺️");
            $ctx->replyHTML('Menu',$key);
        }
    });
    $bot->onText('Qo\'llanma🗒',function (Context $ctx){
        $ctx->replyHTML("Botdan foydalanish uchun qo'llanma🗒\n\nVazifasi🗞\nGuruh a'zolari👤 bilan so'zlashish💭\n\nBotni qollash☑️\n1-qadam: Botni guruhga qoshing➕\n2-qadam: Botni guruhda admin qiling🚔\n3-qadam: Tayyor🟢"."\nKo'proq ma'lumot:\nhttps://telegra.ph/Chat-bot-02-08");
    });

});
$bot->em('group', function (Bot $bot) {
    $bot->onText('{text}', function (Context $ctx) {
        $w = $ctx->var('text');
        $key = R::findAll('key', 'word = ?', [$w]);
        if ($key) {
            $random = array_rand($key, 1);
            print_r($random);
            $ctx->replyHTML(strval($key[$random]->answer));
        }

    });
});


$delete = new Scene('delete');
$delete->onEnter(function (Context $ctx) {
    $key = Menux::Create('cancel')->row()->btn('Bekor qilish❌');
    $ctx->replyHTML('O\'chirmoqchi bo\'lgan so\'zni kiriting!⛔️',$key);

});
$delete->onText('{text}', function (Context $ctx) {
    $t = $ctx->var('text');
    if ($t == 'Bekor qilish❌'){
        $ctx->enter('admin');
    }
    else{
        $key = R::findAll('key', 'word =?', [strval($ctx->var('text'))]);
        if ($key) {
            foreach ($key as $item) {
                R::trash($item);
            }
            $ctx->replyHTML('So\'z o\'chirildi!✅');
            $ctx->enter('admin');
        }
        else {
            $ctx->replyHTML('So\'z topilmadi!❔');
            $ctx->enter('admin');
        }
    }
});


$admin = new Scene('admin');
$admin->onEnter(function (Context $ctx) {
    $key = Menux::Create('menu')->row()->btn('So\'z qo\'shish➕')->btn('Ro\'yhat📝')->btn('O\'chirish❌')->row()->btn('Qo\'llanma🗒');
    $ctx->replyHTML('Menu🔖', $key);
});
$admin->onCommand('start',function (Context $ctx){
    $ctx->enter('admin');
});
$admin->onText("word:{word} ans:{ans}", function (Context $ctx) {
    $trimmed_correct = trim(strval(strtolower($ctx->var('word'))), " \t\n\r");
    $key = R::findOne('key', 'word =?', [$trimmed_correct]);
    $words = R::dispense('key');
    $words->word = clr(strtolower($trimmed_correct));
    $words->answer = clr(strval($ctx->var('ans')));
    $words = R::store($words);
    $ctx->replyHTML("So'z muvaffaqqiyatli qo'shildi!");
});
$admin->onText('{text}', function (Context $ctx) {
    $w = $ctx->var('text');
    $key = R::findAll('key', 'word = ?', [strtolower($w)]);
    if ($key) {
        $random = array_rand($key, 1);
        print_r($random);
        $ctx->replyHTML(strval($key[$random]->answer));
    } else if ($w == 'O\'chirish❌') {
        $ctx->enter('delete');
    } else if ($w == 'So\'z qo\'shish➕') {
        $ctx->replyHTML("Yangi so'z kiritish uchun📝 word:  <code>(so'z)</code> ans: <code>(javob)</code>\nExample📖: <code>word:</code> Hi! <code>ans:</code> Hello!");
    } else if ($w == 'Ro\'yhat📝') {
        $data = R::findAll("key");
        $ctx->replyHTML("👤So'zlar ro'yhati: " . R::count('key'));
        foreach ($data as $item) {
            $ctx->replyHTML(strval($item->word) . " ➖ " . strval($item->answer));
        }
    } else if ($w == 'Qo\'llanma🗒'){
        $ctx->replyHTML("Botdan foydalanish uchun qo'llanma🗒\n\nVazifasi🗞\nGuruh a'zolari👤 bilan so'zlashish💭\n\nBotni qollash☑️\n1-qadam: Botni guruhga qoshing➕\n2-qadam: Botni guruhda admin qiling🚔\n3-qadam: Tayyor🟢"."\nKo'proq ma'lumot:\nhttps://telegra.ph/Chat-bot-02-08");
    }
});



$stage->addScenes($delete, $admin);
$bot->middlewares([
    function (Context $ctx, callable $next) {
        print_r($ctx->update()->export());
        User::register($ctx);
        $ctx->setStorage(User::$storage);
        $next($ctx);
    },
    $stage->middleware(),
]);
$bot->polling();