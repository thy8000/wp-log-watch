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
        \add_action('wp_insert_post', [$this, 'create_post'],  10, 3);
    }

    public function create_post($post_ID, $post, $update)
    {
        if (\wp_is_post_revision($post_ID) || \wp_is_post_autosave($post_ID)) {
            return;
        }

        if ($update || $post->post_type !== 'post') {
            return;
        }

        $PostLogger = new PostLogger();
        $PostLogger->save($post_ID, 'create');
    }
}
