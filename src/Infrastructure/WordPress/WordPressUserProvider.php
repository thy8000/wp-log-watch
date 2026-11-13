<?php

declare(strict_types=1);

namespace WPLogWatch\Infrastructure\WordPress;

use WPLogWatch\Domain\Entities\Actor;
use WPLogWatch\Domain\ValueObjects\ActorType;

/**
 * Resolves the current actor from the WordPress environment.
 */
final class WordPressUserProvider {

	public function currentActor(): Actor {
		$userId = get_current_user_id();

		if ( $userId > 0 ) {
			return Actor::user( $userId );
		}

		return Actor::system( $this->detectSystemActorType() );
	}

	public function currentUserId(): int {
		return get_current_user_id();
	}

	private function detectSystemActorType(): ActorType {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return ActorType::WpCli;
		}

		if ( wp_doing_cron() ) {
			return ActorType::Cron;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return ActorType::RestApi;
		}

		return ActorType::System;
	}
}
