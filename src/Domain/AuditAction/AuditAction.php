<?php

namespace WPTrace\Domain\AuditEvent;

use InvalidArgumentException;

final readonly class AuditAction {
    private const ALLOWED_ACTIONS = [
        'post.created',
        'post.updated',
        'post.published',
        'post.trashed',
        'post.restored',
        'post.deleted',

        'user.created',
        'user.updated',
        'user.deleted',
        'user.login',
        'user.logout',
        'user.role_changed',

        'comment.created',
        'comment.updated',
        'comment.approvaed',
        'comment.unapproved',
        'comment.spammed',
        'comment.deleted',

        'plugin.activated',
        'plugin.deactivated',
        'plugin.updated',

        'theme.activated',
        'theme.updated',
    ];

    public function __construct(
        public string $value
    ) {
        if(!in_array($value, self::ALLOWED_ACTIONS, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid audit action: %s', $value)
            );
        }
    }
}