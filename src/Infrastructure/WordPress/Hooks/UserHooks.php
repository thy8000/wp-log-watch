<?php

declare(strict_types=1);

namespace WPLogWatch\Infrastructure\WordPress\Hooks;

use WP_User;
use WPLogWatch\Domain\Entities\Actor;
use WPLogWatch\Domain\ValueObjects\ActorType;
use WPLogWatch\Domain\ValueObjects\EventType;
use WPLogWatch\Domain\ValueObjects\ResourceType;

/**
 * Audits user lifecycle and authentication events.
 */
final class UserHooks extends AbstractHook {

	public function register(): void {
		add_action( 'user_register', array( $this, 'onRegister' ), 10, 2 );
		add_action( 'profile_update', array( $this, 'onProfileUpdate' ), 10, 3 );
		add_action( 'delete_user', array( $this, 'onDelete' ), 10, 3 );
		add_action( 'wp_login', array( $this, 'onLogin' ), 10, 2 );
		add_action( 'wp_logout', array( $this, 'onLogout' ), 10, 1 );
		add_action( 'wp_login_failed', array( $this, 'onLoginFailed' ), 10, 1 );
		add_action( 'set_user_role', array( $this, 'onRoleChanged' ), 10, 3 );
	}

	/**
	 * @param array<string, mixed> $userData
	 */
	public function onRegister( int $userId, array $userData ): void {
		$user = get_userdata( $userId );
		if ( ! $user instanceof WP_User ) {
			return;
		}

		$this->record(
			EventType::fromString( EventType::USER_CREATED ),
			ResourceType::fromString( ResourceType::USER ),
			$userId,
			sprintf( 'User "%s" created.', $user->user_login ),
			array(
				'login' => $user->user_login,
				'roles' => $user->roles,
			),
		);
	}

	/**
	 * @param array<string, mixed> $userData
	 */
	public function onProfileUpdate( int $userId, WP_User $oldUserData, array $userData ): void {
		$user = get_userdata( $userId );
		if ( ! $user instanceof WP_User ) {
			return;
		}

		$before = array();
		$after  = array();

		$fields = array(
			'user_login'   => 'login',
			'user_email'   => 'email',
			'user_url'     => 'url',
			'display_name' => 'display_name',
		);

		foreach ( $fields as $dbField => $label ) {
			$oldValue = $oldUserData->{$dbField} ?? null;
			$newValue = $user->{$dbField} ?? null;

			if ( $oldValue !== $newValue ) {
				$before[ $label ] = $oldValue;
				$after[ $label ]  = $newValue;
			}
		}

		if ( $before === array() ) {
			return;
		}

		$this->record(
			EventType::fromString( EventType::USER_UPDATED ),
			ResourceType::fromString( ResourceType::USER ),
			$userId,
			sprintf( 'User "%s" profile updated.', $user->user_login ),
			array(
				'login'  => $user->user_login,
				'before' => $before,
				'after'  => $after,
			),
		);
	}

	/**
	 * @param mixed $reassign
	 */
	public function onDelete( int $userId, $reassign, WP_User $user ): void {
		$this->record(
			EventType::fromString( EventType::USER_DELETED ),
			ResourceType::fromString( ResourceType::USER ),
			$userId,
			sprintf( 'User "%s" deleted.', $user->user_login ),
			array(
				'login' => $user->user_login,
				'roles' => $user->roles,
			),
		);
	}

	public function onLogin( string $userLogin, WP_User $user ): void {
		$this->record(
			EventType::fromString( EventType::USER_LOGIN ),
			ResourceType::fromString( ResourceType::USER ),
			(int) $user->ID,
			sprintf( 'User "%s" logged in.', $userLogin ),
			array( 'login' => $userLogin ),
		);
	}

	public function onLogout( int $userId ): void {
		$user  = get_userdata( $userId );
		$login = $user instanceof WP_User ? $user->user_login : (string) $userId;

		$this->record(
			EventType::fromString( EventType::USER_LOGOUT ),
			ResourceType::fromString( ResourceType::USER ),
			$userId,
			sprintf( 'User "%s" logged out.', $login ),
			array( 'login' => $login ),
		);
	}

	public function onLoginFailed( string $username ): void {
		$this->record(
			EventType::fromString( EventType::USER_LOGIN_FAILED ),
			ResourceType::fromString( ResourceType::USER ),
			null,
			sprintf( 'Failed login attempt for "%s".', $username ),
			array( 'username' => $username ),
			Actor::system( ActorType::System ),
		);
	}

	/**
	 * @param string[] $oldRoles
	 */
	public function onRoleChanged( int $userId, string $role, array $oldRoles ): void {
		// No previous role means this is part of user creation, already logged.
		if ( $oldRoles === array() ) {
			return;
		}

		$user  = get_userdata( $userId );
		$login = $user instanceof WP_User ? $user->user_login : (string) $userId;

		$this->record(
			EventType::fromString( EventType::USER_ROLE_CHANGED ),
			ResourceType::fromString( ResourceType::USER ),
			$userId,
			sprintf( 'User "%s" role changed.', $login ),
			array(
				'login'     => $login,
				'role'      => $role,
				'old_roles' => $oldRoles,
			),
		);
	}
}
