<?php namespace Flynsarmy\CommentableSubscriptions\Classes;

use Event;
use Mail;
use Flynsarmy\CommentableSubscriptions\Classes\Auth;
use Flynsarmy\Commentable\Models\Comment;
use RainLab\User\Models\User;

class Notifier
{
    /**
     * The comment we're sending this notification around
     *
     * @var \Flynsarmy\Commentable\Models\Comment
     */
    public $comment;

    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    /**
     * Determines whether or not we're sending notifications for the current
     * comment.
     *
     * @return bool
     */
    public function isSendingNotifications()
    {
        // Newly public comment
        $isSending =
            $this->comment->status == 'public' &&
            $this->comment->getOriginal('status') != 'public';

        // Not from the sample seed
        if ($isSending) {
            if ( $this->comment->thread_id === 1 &&
                 $this->comment->thread->commentable_id == "welcome" &&
                 $this->comment->thread->commentable_type == "BlogPost" &&
                 $this->comment->created_at->diffInSeconds($this->comment->thread->created_at) < 2) {
                $isSending = false;
            }
        }

        $comment = $this->comment;
        $result = Event::fire(
            'flynsarmy.commentablesubscriptions.issendingnotifications',
            [$isSending, $comment],
            true
        );

        if ($result !== null) {
            $isSending = !!$result;
        }

        return $isSending;
    }

    public function getSubscribers()
    {
        $comment = $this->comment;
        $subscribers = $this->comment->thread->subscribers;

        $result = Event::fire(
            'flynsarmy.commentablesubscriptions.getsubscribers',
            [$comment],
            true
        );

        if ($result !== null) {
            $subscribers = $result;
        }

        return $subscribers;
    }

    /**
     * Notify all subscribers of a thread about the new Comment
     *
     * @return void
     */
    public function notifySubscribers()
    {
        $subscribers = $this->getSubscribers();

        foreach ($subscribers as $subscriber) {
            $this->notifySubscriber($subscriber);
        }
    }
    
    /**
     * Notify a given subscriber of the new Comment
     *
     * @param User $user
     * @return void
     */
    public function notifySubscriber(User $user)
    {
        // Don't notify self
        if ($this->comment->author_id && $this->comment->author_id == $user->id) {
            return;
        }

        $auth = new Auth($user, $this->comment->thread);
        $unsubscribeUrl = $this->comment->url . '?' . http_build_query([
            'auth' => $auth->makeAuthCode('unsubscribe'),
            'action' => 'unsubscribe'
        ]);

        $data = [
            'comment' => $this->comment,
            'user' => $user,
            'unsubscribeUrl' => $unsubscribeUrl,
        ];

        $vars = [
            'name'  => $user->username,
            'email' => $user->email
        ];

        Mail::queue('flynsarmy.commentablesubscriptions::mail.comment_reply', $data, function ($message) use ($vars) {
            extract($vars);
            $message->to($email, $name);
        });
    }
}
