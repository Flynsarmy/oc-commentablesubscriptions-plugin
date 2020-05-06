<?php namespace Flynsarmy\CommentableSubscriptions\Components;

use Auth;
use Request;
use Cms\Classes\ComponentBase;
use Flynsarmy\Commentable\Models\Thread;
use Flynsarmy\CommentableSubscriptions\Classes\Auth as NotificationAuth;
use RainLab\User\Models\User;

class Subscription extends ComponentBase
{
    /**
     * @var boolean
     */
    public $isSubscribed = false;

    /**
     * @var \Flynsarmy\Commentable\Models\Thread
     */
    public $thread;

    /**
     * @var \RainLab\User\Models\User
     */
    public $user;

    /**
     * Unsubscribe message
     *
     * @var array
     */
    public $subscriptionMessage = [
        'type' => 'info',
        'message' => '',
    ];

    public function componentDetails()
    {
        return [
            'name'        => 'Thread Subscription',
            'description' => 'Allows subscribing and unsubscribing from a Thread',
        ];
    }

    public function onRender()
    {
        $this->prepareVars();

        if ($this->canSubscribe()) {
            $this->handleOptOutLinks();
        }
    }

    public function prepareVars()
    {
        $this->thread = $this->getThread();
        $this->user = Auth::getUser();
        $this->isSubscribed = $this->isSubscribed();
    }

    public function handleOptOutLinks()
    {
        $action = Request::input('action');
        $request_code = Request::input('auth');

        if ($action != "unsubscribe" || !$request_code) {
            return;
        }

        if (!$this->thread || !$this->user) {
            return;
        }

        if (!$this->isSubscribed()) {
            return;
        }

        $auth = new NotificationAuth($this->user, $this->thread);
        $auth_code = $auth->makeAuthCode($action);

        if ($auth_code !== $request_code) {
            $this->subscriptionMessage['type'] = "error";
            $this->subscriptionMessage['message'] = "Invalid comment subscription auth code.";
            $this->page['subscriptionMessage'] = $this->subscriptionMessage;
            return;
        }

        $this->thread->subscribers()->detach($this->user->id);
        $this->subscriptionMessage['message'] = "You have been successfully unsubscribed from this thread.";
        $this->page['subscriptionMessage'] = $this->subscriptionMessage;
        $this->prepareVars($this->thread->id);
        return;
    }

    /**
     * Returns whether or not a given user is subscribed to a given thread.
     *
     * @return bool
     */
    public function isSubscribed()
    {
        if (!$this->canSubscribe()) {
            return false;
        }

        return $this->thread->subscribers()->where('user_id', $this->user->id)->exists();
    }

    /**
     * Determines whether or not we're able to subscribe to the current thread.
     *
     * @return bool
     */
    public function canSubscribe()
    {
        return $this->user && $this->thread;
    }

    /**
     * AJAX subscribe/unsubscribe
     *
     * @return void
     */
    public function onUpdateSubscription()
    {
        $thread_id = intval(post('thread_id', 0));
        $this->prepareVars($thread_id);

        if (!$this->canSubscribe()) {
            throw new \ApplicationException("You do not have permission to do this.");
        }

        $isSubscribing = intval(post('subscribing', 1));
        
        // Subscribe
        if ($isSubscribing) {
            $this->subscribe();
        // Unsubscribe
        } elseif (!$isSubscribing) {
            $this->unsubscribe();
        }
    }

    /**
     * Subscribe the current user to the current thread
     *
     * @return void
     */
    public function subscribe()
    {
        if ($this->isSubscribed) {
            return;
        }

        $this->thread->subscribers()->attach($this->user->id);
        $this->isSubscribed = !$this->isSubscribed;
    }

    /**
     * Unsubscribe the current user from the current thread
     *
     * @return void
     */
    public function unsubscribe()
    {
        if (!$this->isSubscribed) {
            return;
        }

        $this->thread->subscribers()->detach($this->user->id);
        $this->isSubscribed = !$this->isSubscribed;
    }

    /**
     * @return \Flynsarmy\Commentable\Models\Thread|null
     */
    protected function getThread()
    {
        $type = $this->property('type');
        $id = $this->property('id');

        if (empty($id) || empty($type)) {
            return null;
        }

        return Thread::where([
            'commentable_type' => $type,
            'commentable_id' => $id,
        ])->first();
    }
}
