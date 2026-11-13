<?php

declare(strict_types=1);

namespace WPLogWatch\Domain\Entities;

use InvalidArgumentException;
use WPLogWatch\Domain\ValueObjects\ActorType;

/**
 * Answers "who performed this action?".
 *
 * A human actor is identified by a positive user id. A system actor has no id
 * and is further qualified by its ActorType (cron, CLI, REST…).
 */
final class Actor {

	private function __construct(
		private readonly ActorType $type,
		private readonly ?int $id,
	) {
	}

	public static function user( int $id ): self {
		if ( $id <= 0 ) {
			throw new InvalidArgumentException( 'A user actor requires a positive id.' );
		}

		return new self( ActorType::User, $id );
	}

	public static function system( ActorType $type = ActorType::System ): self {
		return new self( $type, null );
	}

	/**
	 * Rebuilds an actor from its persisted representation. A positive id always
	 * wins: the actor is a human user regardless of any stored type.
	 */
	public static function fromTypeAndId( ActorType $type, ?int $id ): self {
		if ( $id !== null && $id > 0 ) {
			return new self( ActorType::User, $id );
		}

		return new self( $type, null );
	}

	public function type(): ActorType {
		return $this->type;
	}

	public function id(): ?int {
		return $this->id;
	}

	public function isUser(): bool {
		return $this->type->isUser() && $this->id !== null;
	}

	public function isSystem(): bool {
		return ! $this->isUser();
	}
}
