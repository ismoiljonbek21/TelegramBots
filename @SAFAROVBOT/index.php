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
    'ChannelAdmin' => function (Context $ctx, callable $next) {
        if ($ctx->getUserID() == 1014474410 && $ctx->getChatType() == 'channel') {
            return false;
        }
    },
    'GroupAdmin' => function (Context $ctx, callable $next) {
        if ($ctx->getUserID() == 1014474410 && $ctx->getChatType() == 'group' ||$ctx->getUserID() == 1014474410 && $ctx->getChatType() == 'supergroup') {
            $next($ctx);
        }
    },
    'channel' => function (Context $ctx, callable $next) {
        if ($ctx->getChatType() == 'channel' ) {
            return false;
        }

    },
]);


$bot->em('private', function (Bot $bot) use ($stage) {
    $bot->onStart(function (Context $ctx) {
        $ctx->enter('name');
    });
    $name = new Scene('name');
    $name->onCommand('start', function (Context $ctx) {
        $ctx->leave();
    });
    $name->onEnter(function (Context $ctx) {
        $ctx->replyHTML("📝Ism-familyangizni kiriting: ");
    });
    $name->onText('{text}', function (Context $ctx) {
        $user = User::$user;
        $user->name = clr($ctx->var('text'));
        try {
            $user = R::store($user);
        } catch (Exception $e) {
            print_r($e);
        }
        $ctx->enter('age');
    });


    $age = new Scene('age');
    $age->onCommand('start', function (Context $ctx) {
        $ctx->leave();
    });
    $age->onEnter(function (Context $ctx) {
        $ctx->replyHTML('📅Yoshingizni kiriting: ');
    });
    $age->onText('{text}:integer', function (Context $ctx) {
        $user = User::$user;
        $var = intval(clr($ctx->var('text')));
        if ($var > 0) {
            $user->age = $var;
            $user = R::store($user);
            $ctx->enter('phone');
        } else {
            $ctx->replyHTML("Raqam bo'lishi shart🔄");
        }
    });


    $phone = new Scene('phone');
    $phone->onCommand('start', function (Context $ctx) {
        $ctx->leave();
    });
    $phone->onEnter(function (Context $ctx) {
        $key = Menux::Create('phone')->row()->cBtn('Contact');
        $ctx->replyHTML('Telefon raqamingizni jo\'nating: ', $key);
    });
    $phone->onMessage('contact', function (Context $ctx) {
        $user = User::$user;
        $user->phone = $ctx->getMessage()->contact()->phoneNumber();
        $user = R::store($user);
        $ctx->enter('member');

    });
    $phone->onMessage('text|integer', function (Context $ctx) {
        $ctx->replyHTML("✉️Iltimos telefon raqamini yuborish uchun <b>Contact</b> buyrug'ini bosing!");
    });


    $member = new Scene('member');
    $member->onCommand('start', function (Context $ctx) {
        $ctx->leave();
    });
    $member->onEnter(function (Context $ctx) {
        $ctx->replyHTML('Ro\'yhatdan o\'ttingiz!✅');
        $ctx->leave();
    });
    $stage->addScenes($name, $age, $phone, $member);
});

$bot->em('GroupAdmin', function (Bot $bot) {
    $bot->onText("users", function (Context $ctx) {
        $data = R::findAll("main");
        $ctx->replyHTML("👤Foydanalunchilar ro'yhati: " . R::count('main'));
        foreach ($data as $item) {
            $ctx->replyHTML("📝FIO: ".$item->name."\n📅Age " . $item->age ."\n#️⃣Phone: " . $item->phone . "\n\n");
        }
    });
});

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