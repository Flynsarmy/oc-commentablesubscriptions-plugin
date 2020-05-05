<?php namespace Flynsarmy\CommentableSubscriptions\Classes;

use Flynsarmy\Commentable\Models\Thread;
use RainLab\User\Models\User;

class Auth
{
    protected User $user;
    protected Thread $thread;

    public function __construct(User $user, Thread $thread)
    {
        $this->user = $user;
        $this->thread = $thread;
    }

    public function makeAuthCode($action)
    {
        $hash = md5(
            $action
            .$this->thread->id
            .$this->user->created_at
            .$this->user->persist_code
        );

        return $hash.'!'.$this->user->id;
    }
}
