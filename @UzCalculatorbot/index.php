<?php
require '../vendor/autoload.php';
require '../rb.php';

use Askoldex\Teletant\Bot;
use Askoldex\Teletant\Context;
use Askoldex\Teletant\Settings;
use Askoldex\Teletant\Addons\Menux;
use RedBeanPHP\OODBBean;
use Askoldex\Teletant\Interfaces\StorageInterface;
use Askoldex\Teletant\States\Scene;
use Askoldex\Teletant\States\Stage;

$settings = new Settings('1693330104:AAHg5L70XX1RdM5ReY0twSiwJuhHmT1-07M');
$settings->setHookOnFirstRequest(false);
$bot = new Bot($settings);
$stage = new Stage();
$channel = '@tezkor_xabarlar_yangiliklari';
$uchannel ='https://t.me/tezkor_xabarlar_yangiliklari';

R::setup('sqlite:thisbotdb.db');
R::ext('xdispense', function ($table_name) {
    return R::getRedBean()->dispense($table_name);
});
// Functions ##
function clr($text)
{
    $text = str_replace(['&', '<', '>', '{', '}'], ['&amp;', '', ''], $text);
    return htmlentities($text, ENT_QUOTES); // | ENT_HTML401


}

function Factorial($number)
{
    $factorial = 1;
    for ($i = 1; $i <= $number; $i++) {
        $factorial = $factorial * $i;
    }
    return $factorial;
}

function degrees1($t1, $t2)
{
    $counts = 1;
    for ($i = 1; $i <= $t2; $i++) {
        $counts = $counts * $t1;
    }
    return $counts;
}

// Classes ##
class Storage implements StorageInterface
{
    private OODBBean $user;

    public function __construct(OODBBean $user)
    {
        $this->user = $user;
    }


    public function setScene(string $sceneName)
    {
        $this->user->scene = $sceneName;
        R::store($this->user);
    }

    public function getScene(): string
    {
        return $this->user->scene;
    }

    public function setTtl(string $sceneName, int $seconds)
    {
        // TODO: Implement setTtl() method.
    }

    public function getTtl(string $sceneName)
    {
        // TODO: Implement getTtl() method.
    }
}

class Field_calculate
{
    const PATTERN = '/(?:\-?\d+(?:\.?\d+)?[\+\-\*\/])+\-?\d+(?:\.?\d+)?/';

    const PARENTHESIS_DEPTH = 10;

    public function calculate($input)
    {
        if (strpos($input, '+') != null || strpos($input, '-') != null || strpos($input, '/') != null || strpos($input, '*') != null) {
            //  Remove white spaces and invalid math chars
            $input = str_replace(' ', '', $input);
            $input = str_replace('a', '', $input);
            $input = preg_replace('[^0-9\.\+\-\*\/\(\)]', '', $input);

            //  Calculate each of the parenthesis from the top
            $i = 0;
            while (strpos($input, '(') || strpos($input, ')')) {
                $input = preg_replace_callback('/\(([^\(\)]+)\)/', 'self::callback', $input);

                $i++;
                if ($i > self::PARENTHESIS_DEPTH) {
                    break;
                }
            }

            //  Calculate the result
            if (preg_match(self::PATTERN, $input, $match)) {
                return $this->compute($match[0]);
            }
            // To handle the special case of expressions surrounded by global parenthesis like "(1+1)"
            if (is_numeric($input)) {
                return $input;
            }

            return 0;
        }

        return $input;
    }

    private function compute($input)
    {
        $compute = create_function('', 'return ' . $input . ';');

        return 0 + $compute();
    }

    private function callback($input)
    {
        if (is_numeric($input[1])) {
            return $input[1];
        } elseif (preg_match(self::PATTERN, $input[1], $match)) {
            return $this->compute($match[0]);
        }

        return 0;
    }
}

class User
{

    /**
     * @var OODBBean
     */
    public static OODBBean $user;
    /**
     * @var Storage
     */
    public static Storage $storage;

    public static function register(Context $ctx): OODBBean
    {

        R::useWriterCache(false);
        $user = R::findOne('main', 'user_id = ?', [$ctx->getUserID()]);
        R::useWriterCache(true);

        if ($user == null) {
            $user = R::dispense('main');
            $user->user_id = $ctx->getUserID();
            $user->fio = clr($ctx->getFullName());
            $user->username = $ctx->getUsername();
            $user->scene = '';

            $user = R::load('main', R::store($user));

        }
        self::$storage = new Storage($user);

        return self::$user = $user;
    }

}

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
    }, 'channel' => function (Context $ctx, callable $next) {
        if ($ctx->getChatType() == 'channel') {
            return false;
        }
    }, 'admin' => function (Context $ctx, callable $next) {
        if ($ctx->getUserID() == 1014474410) {
            $next($ctx);
        }
    },
]);
$bot->onCommand('start', function (Context $ctx) {
     $member = $ctx->Api()->getChatMember([
        'chat_id' => $GLOBALS['channel'],
        'user_id' => $ctx->getUserID()

    ]);
    echo $member->status();
    if ($member->status() == 'member' or $member->status() == 'creator' or $member->status() == 'administrator') {
        $ctx->enter('members');


    } else {
        $key = Menux::Create('get')->inline()->row()->uBtn('Kanalga a\'zo bo\'lish', $GLOBALS['uchannel']);
        $ctx->reply('Calculatorni ishlatish uchun kanalimizga a’zo bo‘ling!\n\nA’zo bo‘lganingizda so‘ng /start ga bosing!', $key);
    }


});
$bot->em('admin', function (Bot $bot) {
    $bot->onCommand('static', function (Context $ctx) {
        $ctx->replyHTML(R::count('main'));
    });
});
$bot->onMessage('text', function (Context $ctx) {
    $key = Menux::Create('get')->inline()->row()->uBtn('Kanalga a\'zo bo\'lish', $GLOBALS['uchannel']);
    $ctx->reply('Calculatorni ishlatish uchun kanalimizga a’zo bo‘ling!\n\nA’zo bo‘lganingizda so‘ng /start ga bosing!', $key);
});
$memberScena = new Scene('members');
$memberScena->onEnter(function (Context $ctx) {
    $key1 = Menux::Create('bolim')->row()->btn('Qo\'llanma🔵');
    $ctx->replyHTML('Menu', $key1);
});

$memberScena->onText("Qo'llanma🔵", function (Context $ctx) {
    $member = $ctx->Api()->getChatMember([
        'chat_id' => $GLOBALS['channel'],
        'user_id' => $ctx->getUserID()

    ]);
    if ($member->status() == 'left') {
        $ctx->leave();
        $key = Menux::Create('get')->inline()->row()->uBtn('Kanalga a\'zo bo\'lish', $GLOBALS['uchannel']);
        $ctx->reply('Calculatorni ishlatish uchun kanalimizga a’zo bo‘ling!\n\nA’zo bo‘lganingizda so‘ng /start ga bosing!', $key);

    } else {
        $ctx->replyHTML("Botdan foydalanish uchun qo'llanma!\n\nQo'shish:\n     <code>0 + 0</code>\nAyirish:\n     <code>0 - 0</code>\nBo'lish:\n     <code>0 / 0</code>\nKo'paytirish:\n     <code>0 * 0</code>\nQavslar bilan ishlash:\n     <code>0 * (0 + 0)</code>\nKo'p funksiyali misollar:\n     <code>0 / (0 + 0) - (0 * 0)</code>\nSin:\n     <code>sin 0</code>\nCos:\n     <code>cos 0</code>\nTan:\n     <code>tag 0</code>\nIldiz:\n     <code>sqrt 0</code>\nDaraja:\n     <code>d 0 (daraja)</code>\nFaktorial:\n     <code>f 0</code>\nPI:\n     <code>PI</code>\nMore:\n     https://telegra.ph/Mathbot-01-28");

    }

});
$memberScena->onText('kc {number1}<{number2}', function (Context $ctx) {
    $member = $ctx->Api()->getChatMember([
        'chat_id' => $GLOBALS['channel'],
        'user_id' => $ctx->getUserID()

    ]);
    if ($member->status() == 'left') {
        $ctx->leave();
        $key = Menux::Create('get')->inline()->row()->uBtn('Kanalga a\'zo bo\'lish', $GLOBALS['uchannel']);
        $ctx->reply('Calculatorni ishlatish uchun kanalimizga a’zo bo‘ling!\n\nA’zo bo‘lganingizda so‘ng /start ga bosing!', $key);

    } else {
        list('number1' => $t1, 'number2' => $t2) = $ctx->vars();
        $t1 = intval($t1);
        $t2 = intval($t2);
        if ($t1 < $t2) {
            $ctx->replyHTML("To'g'ri");
        } else if ($t1 > $t2) {
            $ctx->replyHTML("Noto'g'ri");
        }
    }


});
$memberScena->onText("kt {number1}>{number2}", function (Context $ctx) {
    $member = $ctx->Api()->getChatMember([
        'chat_id' => $GLOBALS['channel'],
        'user_id' => $ctx->getUserID()

    ]);
    if ($member->status() == 'left') {
        $ctx->leave();
        $key = Menux::Create('get')->inline()->row()->uBtn('Kanalga a\'zo bo\'lish', $GLOBALS['uchannel']);
        $ctx->reply('Calculatorni ishlatish uchun kanalimizga a’zo bo‘ling!\n\nA’zo bo‘lganingizda so‘ng /start ga bosing!', $key);

    } else {
        list('number1' => $t1, 'number2' => $t2) = $ctx->vars();
        $t1 = intval($t1);
        $t2 = intval($t2);
        if ($t1 > $t2) {
            $ctx->replyHTML("To'g'ri");
        } else if ($t1 < $t2) {
            $ctx->replyHTML("Noto'g'ri");
        }
    }


});
$memberScena->onText('sin{number}', function (Context $ctx) {
    $member = $ctx->Api()->getChatMember([
        'chat_id' => $GLOBALS['channel'],
        'user_id' => $ctx->getUserID()

    ]);
    if ($member->status() == 'left') {
        $ctx->leave();
        $key = Menux::Create('get')->inline()->row()->uBtn('Kanalga a\'zo bo\'lish', $GLOBALS['uchannel']);
        $ctx->reply('Calculatorni ishlatish uchun kanalimizga a’zo bo‘ling!\n\nA’zo bo‘lganingizda so‘ng /start ga bosing!', $key);

    } else {
        list('number' => $t1) = $ctx->vars();
        $t1 = intval($t1);
        $ctx->reply(sin($t1));
        $ctx->reply("Sin ( $t1 gradus) = " . sin($t1));
    }

});
$memberScena->onText('cos{number}', function (Context $ctx) {
    $member = $ctx->Api()->getChatMember([
        'chat_id' => $GLOBALS['channel'],
        'user_id' => $ctx->getUserID()

    ]);
    if ($member->status() == 'left') {
        $ctx->leave();
        $key = Menux::Create('get')->inline()->row()->uBtn('Kanalga a\'zo bo\'lish', $GLOBALS['uchannel']);
        $ctx->reply('Calculatorni ishlatish uchun kanalimizga a’zo bo‘ling!\n\nA’zo bo‘lganingizda so‘ng /start ga bosing!', $key);

    } else {
        list('number' => $t1) = $ctx->vars();
        $t1 = intval($t1);
        $ctx->reply(cos($t1));
        $ctx->reply("Cos ( $t1 gradus) = " . cos($t1));
    }

});
$memberScena->onText('tan{number}', function (Context $ctx) {
    $member = $ctx->Api()->getChatMember([
        'chat_id' => $GLOBALS['channel'],
        'user_id' => $ctx->getUserID()

    ]);
    if ($member->status() == 'left') {
        $ctx->leave();
        $key = Menux::Create('get')->inline()->row()->uBtn('Kanalga a\'zo bo\'lish', $GLOBALS['uchannel']);
        $ctx->reply('Calculatorni ishlatish uchun kanalimizga a’zo bo‘ling!\n\nA’zo bo‘lganingizda so‘ng /start ga bosing!', $key);

    } else {
        list('number' => $t1) = $ctx->vars();
        $t1 = intval($t1);
        $ctx->reply(tan($t1));
        $ctx->reply("Tan( $t1 gradus) = " . tan($t1));
    }


});
$memberScena->onText('sqrt{number}', function (Context $ctx) {
    $member = $ctx->Api()->getChatMember([
        'chat_id' => $GLOBALS['channel'],
        'user_id' => $ctx->getUserID()

    ]);
    if ($member->status() == 'left') {
        $ctx->leave();
        $key = Menux::Create('get')->inline()->row()->uBtn('Kanalga a\'zo bo\'lish', $GLOBALS['uchannel']);
        $ctx->reply('Calculatorni ishlatish uchun kanalimizga a’zo bo‘ling!\n\nA’zo bo‘lganingizda so‘ng /start ga bosing!', $key);

    } else {
        list('number' => $t1) = $ctx->vars();
        $t1 = intval($t1);
        $ctx->reply(sqrt($t1));
        $ctx->reply("√$t1 = " . sqrt($t1));
    }

});
$memberScena->onText('f', function (Context $ctx) {
    $member = $ctx->Api()->getChatMember([
        'chat_id' => $GLOBALS['channel'],
        'user_id' => $ctx->getUserID()

    ]);
    if ($member->status() == 'left') {
        $ctx->leave();
        $key = Menux::Create('get')->inline()->row()->uBtn('Kanalga a\'zo bo\'lish', $GLOBALS['uchannel']);
        $ctx->reply('Calculatorni ishlatish uchun kanalimizga a’zo bo‘ling!\n\nA’zo bo‘lganingizda so‘ng /start ga bosing!', $key);

    } else {
        $ctx->reply("Qiymat berilmagan🤷‍♂️");
    }
});
$memberScena->onText('f{number}', function (Context $ctx) {
    $member = $ctx->Api()->getChatMember([
        'chat_id' => $GLOBALS['channel'],
        'user_id' => $ctx->getUserID()

    ]);
    if ($member->status() == 'left') {
        $ctx->leave();
        $key = Menux::Create('get')->inline()->row()->uBtn('Kanalga a\'zo bo\'lish', $GLOBALS['uchannel']);
        $ctx->reply('Calculatorni ishlatish uchun kanalimizga a’zo bo‘ling!\n\nA’zo bo‘lganingizda so‘ng /start ga bosing!', $key);

    } else {
        list('number' => $t1) = $ctx->vars();
        $t1 = intval($t1);
        if ($t1 > 0) $ctx->replyHTML(Factorial($t1));
        if ($t1 > 0) $ctx->replyHTML("$t1! = " . Factorial($t1));
        else $ctx->replyHTML("-\nYozuvingiz to'griligiga ishonch hosil qilib qayta urinib koring!");
    }
});
$memberScena->onText('PI', function (Context $ctx) {
    $member = $ctx->Api()->getChatMember([
        'chat_id' => $GLOBALS['channel'],
        'user_id' => $ctx->getUserID()

    ]);
    if ($member->status() == 'left') {
        $ctx->leave();
        $key = Menux::Create('get')->inline()->row()->uBtn('Kanalga a\'zo bo\'lish', $GLOBALS['uchannel']);
        $ctx->reply('Calculatorni ishlatish uchun kanalimizga a’zo bo‘ling!\n\nA’zo bo‘lganingizda so‘ng /start ga bosing!', $key);

    } else {
        $ctx->reply('PI: ' . pi());
    }
});
$memberScena->onText('d', function (Context $ctx) {
    $member = $ctx->Api()->getChatMember([
        'chat_id' => $GLOBALS['channel'],
        'user_id' => $ctx->getUserID()

    ]);
    if ($member->status() == 'left') {
        $ctx->leave();
        $key = Menux::Create('get')->inline()->row()->uBtn('Kanalga a\'zo bo\'lish', $GLOBALS['uchannel']);
        $ctx->reply('Calculatorni ishlatish uchun kanalimizga a’zo bo‘ling!\n\nA’zo bo‘lganingizda so‘ng /start ga bosing!', $key);

    } else {
        $ctx->reply("Qiymat berilmagan🤷‍♂️");
    }


});
$memberScena->onText('d {number} {degree}', function (Context $ctx) {
    $member = $ctx->Api()->getChatMember([
        'chat_id' => $GLOBALS['channel'],
        'user_id' => $ctx->getUserID()

    ]);
    if ($member->status() == 'left') {
        $ctx->leave();
        $key = Menux::Create('get')->inline()->row()->uBtn('Kanalga a\'zo bo\'lish', $GLOBALS['uchannel']);
        $ctx->reply('Calculatorni ishlatish uchun kanalimizga a’zo bo‘ling!\n\nA’zo bo‘lganingizda so‘ng /start ga bosing!', $key);

    } else {
        list('number' => $t1, 'degree' => $t2) = $ctx->vars();
        $t1 = intval($t1);
        $t2 = intval($t2);
        $result = degrees1($t1, $t2);
        $ctx->reply("$t1 ^ $t2 = " . $result);
    }
});
$memberScena->onText('{test}', function (Context $ctx) {
    $member = $ctx->Api()->getChatMember([
        'chat_id' => $GLOBALS['channel'],
        'user_id' => $ctx->getUserID()

    ]);
    if ($member->status() == 'left') {
        $ctx->leave();
        $key = Menux::Create('get')->inline()->row()->uBtn('Kanalga a\'zo bo\'lish', $GLOBALS['uchannel']);
        $ctx->reply('Calculatorni ishlatish uchun kanalimizga a’zo bo‘ling!\n\nA’zo bo‘lganingizda so‘ng /start ga bosing!', $key);

    } else {
        list('test' => $t1) = $ctx->vars();
        $Cal = new Field_calculate();
        $minresult = $Cal->calculate($t1); // 12
        $result = intval($minresult);
        if ($result !== 0) {
            $ctx->replyHTML(intval($result));
            $ctx->replyHTML($t1 . '= ' . intval($result));
        } else {
            $ctx->replyHTML("0\nYozuvingiz to'griligiga ishonch hosil qilib qayta urinib koring!");
        }
    }


});
$stage->addScene($memberScena);
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