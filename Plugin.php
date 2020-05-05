<?php namespace Flynsarmy\CommentableSubscriptions;

use Event;
use Flynsarmy\Commentable\Controllers\Threads;
use Flynsarmy\Commentable\Models\Comment;
use Flynsarmy\Commentable\Models\Thread;
use Flynsarmy\CommentableSubscriptions\Classes\Notifier;
use RainLab\User\Models\User;
use System\Classes\PluginBase;

/**
 * Commentable Plugin Information File.
 */
class Plugin extends PluginBase
{
    public $require = ['Flynsarmy.Commentable'];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Commentable - Subscriptions Extension',
            'description' => 'Adds option to subscribe to comment threads',
            'author'      => 'Flynsarmy',
            'icon'        => 'icon-comments',
        ];
    }

    public function registerComponents()
    {
        return [
            'Flynsarmy\CommentableSubscriptions\Components\Subscription'   => 'threadSubscription',
        ];
    }

    public function boot()
    {
        // Add 'subscribers' Thread relation
        Thread::extend(function (Thread $model) {
            $model->belongsToMany['subscribers'] = [
                User::class,
                'table' => 'flynsarmy_commentable_thread_subscribers',
                'key' => 'thread_id',
                'otherKey' => 'user_id',
            ];
        });

        // Add 'commentable_subscriptions' User relation
        User::extend(function (User $model) {
            $model->belongsToMany['commentable_subscriptions'] = [
                Thread::class,
                'table' => 'flynsarmy_commentable_thread_subscribers',
                'key' => 'user_id',
                'otherKey' => 'thread_id',
            ];
        });

        // Add 'Subscribers' column to Threads list
        Threads::extendListColumns(function ($list, $model) {
            if (!$model instanceof Thread) {
                return;
            }

            $list->addColumns([
                'subscribers' => [
                    'label' => 'Subscribers',
                    'relation' => 'subscribers',
                    'useRelationCount' => true,
                ]
            ]);
        });

        // Notify subscribers on new Comment
        Comment::saved(function (Comment $model) {
            $notifier = new Notifier($model);
                
            if ($notifier->isSendingNotifications()) {
                $notifier->notifySubscribers();
            }
        });
    }

    public function registerMailTemplates()
    {
        return [
            'flynsarmy.commentablesubscriptions::mail.comment_reply'   => 'Notification to subscribers when a comment is made to a thread.',
        ];
    }
}
