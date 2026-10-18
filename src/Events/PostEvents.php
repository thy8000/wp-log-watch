<?php

namespace WPTrace\Events;

if (!defined("ABSPATH")) {
    exit;
}

use WPTrace\Logger\PostLogger;

final class PostEvents
{
    public function __construct()
    {
        \add_action('transition_post_status', [$this, 'check_post_publish'],  10, 3);
    }

    public function check_post_publish($new_status, $old_status, $post)
    {
        if ($post->post_type !== 'post') {
            return;
        }

        if (\wp_is_post_revision($post->ID) || \wp_is_post_autosave($post->ID)) {
            return;
        }

        if ($new_status === 'publish' && $old_status !== 'publish') {
            $PostLogger = new PostLogger();
            $PostLogger->save($post->ID, 'create');
        }
    }
}
