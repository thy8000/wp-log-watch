<?php

namespace WPTrace\Infrastructure\WordPress\Posts;

use WPTrace\Application\Audit\RecordAuditEvent;
use WPTrace\Application\DTO\RecordAuditInput;

final class PostUpdatedListener
{
    private RecordAuditEvent $RecordAuditEvent;

    public function __construct(RecordAuditEvent $RecordAuditEvent) {
        $this->RecordAuditEvent = $RecordAuditEvent;
    }
    public function register(): void {
        add_action('post_updated', [$this, 'handle'], 10, 3);
    }

    public function handle(int $post_ID, \WP_Post $post_after, \WP_Post $post_before): void {
        $this->RecordAuditEvent->execute(
            new RecordAuditEventInput(
                action: 'post_updated',
                actorID: get_current_user_ID(),
                objectType: 'post',
                objectID: $postID,
                context: 'wp-admin'
            )
        );
    }
}