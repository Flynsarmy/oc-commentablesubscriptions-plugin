# Commentable - Subscriptions Extension

Provides ability to subscribe to comment threads in order to be notified when new comments are posted.

Requires
* [Flynsarmy.Commentable](https://octobercms.com/plugin/flynsarmy-commentable) plugin 

## Installation

* `git clone` to */plugins/flynsarmy/commentablesubscriptions*
* `php artisan plugin:refresh Flynsarmy.CommentableSubscriptions`
* Add the *Thread Subscription* component to your page (in addition to the *Comments* component)
* Add `{% component 'threadSubscription' type="MyType" id="MyID" %}` to your page where you'd like the subscribe/unsubscribe link to go.


## Notifications

By default all thread subscribers are notified all new public comments. If you'd like subscribers to only be notified of replies to their comments, use the following event:
```php
\Event::listen('flynsarmy.commentablesubscriptions.getsubscribers', function ($comment) {
    $parent_authors = $comment->parents()->select('author_id')->get()->lists('author_id');
    return
        $comment
            ->thread
            ->subscribers()
            ->whereIn('id', $parent_authors)
            ->get();
});
```