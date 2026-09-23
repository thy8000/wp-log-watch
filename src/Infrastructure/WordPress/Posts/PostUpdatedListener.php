<?php

namespace WPTrace\Infrastructure\WordPress\Posts;

final class PostUpdatedListener
{
    public function register(): void {
        add_action('post_updated', [$this, 'handle'], 10, 3);
    }

    public function handle(): void {
        
    }
}