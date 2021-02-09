<?php
require '../vendor/autoload.php';
use Askoldex\Teletant\Context;
use RedBeanPHP\OODBBean;
use Askoldex\Teletant\Interfaces\StorageInterface;


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
