<?php
require './../vendor/autoload.php';
require './../rb.php';

use Askoldex\Teletant\Bot;
use Askoldex\Teletant\Context;
use Askoldex\Teletant\Settings;
use Askoldex\Teletant\Addons\Menux;
use RedBeanPHP\OODBBean;
use Askoldex\Teletant\Interfaces\StorageInterface;
use Askoldex\Teletant\States\Scene;
use Askoldex\Teletant\States\Stage;

$settings = new Settings('1524846431:AAFiqHtYpey6MepXLhlrHglgM8K-vL8CDlc');
$settings->setHookOnFirstRequest(false);
$bot = new Bot($settings);
$stage = new Stage();

R::setup('sqlite:thisbotdb.db');
R::ext('xdispense', function ($table_name) {
    return R::getRedBean()->dispense($table_name);
});
// Functions ##
function clr($text)
{
    $text = str_replace(['&', '<', '>'], ['&amp;', '', ''], $text);
    return htmlentities($text, ENT_QUOTES); // | ENT_HTML401


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


function dmwe($entities,$member,Context $ctx){


    foreach ($entities as $entity){
        $type = $entity->type();
        if(in_array($type,['url','mention','text_link','text_mention'])){
            if ($member->status() == 'member'){
                $ctx->Api()->deleteMessage([
                    'chat_id' => $ctx->getChatID(),
                    'message_id' => $ctx->getMessageID(),
                ]);
                break;
            }
        }
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
    }, 'admin' => function (Context $ctx, callable $next) {
        if ($ctx->getUserID() == 1014474410) {
            $next($ctx);
        }
    },
]);
$bot->em('private', function (Bot $bot) {
    $bot->onCommand('start', function (Context $ctx) {
        $key = Menux::Create('menu')
            ->row()
            ->btn('Qo\'llanma');
        $ctx->replyHTML('Assalomu alaykum! Polonchi pistonchi nomli gurpaning shaxsiy botiga hush kelibsiz!', $key);
    });
    $bot->onText('Qo\'llanma', function (Context $ctx) {
        $ctx->replyHTML('Bu botni gurpalarga qoshib admin qilsangiz turli xil reklaman va a\'zolarning kirib chiqib ketganligi haqidagi habarni tozalab turadi!');
    });
});
$bot->em('admin', function (Bot $bot) {
    $bot->onText('static', function (Context $ctx) {
        $ctx->replyHTML(R::count('main'));
    });
});
$bot->em('group', function (Bot $bot) {
    $bot->onMessage('entities', function (Context $ctx) {
        $member = $ctx->Api()->getChatMember([
            'chat_id' => $ctx->getChatID(),
            'user_id' => $ctx->getUserID(),
        ]);

        $entities = $ctx->getMessage()->entities();
        $caption_entities = $ctx->getMessage()->caption_entities();

        print_r($entities);
        print_r($caption_entities);

        dmwe($entities,$member,$ctx);
        dmwe($caption_entities,$member,$ctx);


    });

    $bot->onMessage('new_chat_members', function (Context $ctx) {
        try {
            $ctx->Api()->deleteMessage([
                'chat_id' => $ctx->getChatID(),
                'message_id' => $ctx->getMessageID()
            ]);
        } catch (Exception $e) {
            print_r($e);
        }
    });
    $bot->onMessage('left_chat_member', function (Context $ctx) {
        try {
            $ctx->Api()->deleteMessage([
                'chat_id' => $ctx->getChatID(),
                'message_id' => $ctx->getMessageID()
            ]);
        } catch (Exception $e) {
            print_r($e);
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
$bot->listen();