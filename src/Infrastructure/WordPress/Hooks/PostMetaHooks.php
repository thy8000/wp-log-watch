<?php

declare(strict_types=1);

namespace WPLogWatch\Infrastructure\WordPress\Hooks;

use WP_Post;
use WPLogWatch\Domain\ValueObjects\EventType;
use WPLogWatch\Domain\ValueObjects\ResourceType;

/**
 * Audits post meta changes for an explicit allowlist of keys.
 *
 * Post meta is inherently noisy and can contain arbitrary data, so by default
 * only a couple of safe keys are tracked. The list is filterable so a site can
 * opt into more without patching the plugin.
 */
final class PostMetaHooks extends AbstractHook {

	private const DEFAULT_AUDITED_KEYS = array( '_thumbnail_id', '_wp_page_template' );

	public function register(): void {
		add_action( 'added_post_meta', array( $this, 'onAdded' ), 10, 4 );
		add_action( 'updated_post_meta', array( $this, 'onUpdated' ), 10, 4 );
		add_action( 'deleted_post_meta', array( $this, 'onDeleted' ), 10, 4 );
	}

	/**
	 * @param mixed $value
	 */
	public function onAdded( int $metaId, int $objectId, string $key, $value ): void {
		$this->handle( 'added', $objectId, $key, $value );
	}

	/**
	 * @param mixed $value
	 */
	public function onUpdated( int $metaId, int $objectId, string $key, $value ): void {
		$this->handle( 'updated', $objectId, $key, $value );
	}

	/**
	 * @param int[] $metaIds
	 * @param mixed $value
	 */
	public function onDeleted( array $metaIds, int $objectId, string $key, $value ): void {
		$this->handle( 'deleted', $objectId, $key, $value );
	}

	/**
	 * @param mixed $value
	 */
	private function handle( string $operation, int $objectId, string $key, $value ): void {
		if ( ! $this->isAuditedKey( $key ) ) {
			return;
		}

		$post = get_post( $objectId );
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		if ( wp_is_post_revision( $objectId ) || wp_is_post_autosave( $objectId ) ) {
			return;
		}

		$eventType = match ( $operation ) {
			'added' => EventType::POST_META_ADDED,
			'deleted' => EventType::POST_META_DELETED,
			default => EventType::POST_META_UPDATED,
		};

		$this->record(
			EventType::fromString( $eventType ),
			ResourceType::fromString( $post->post_type ),
			$objectId,
			sprintf( 'Post "%s" meta "%s" %s.', $post->post_title, $key, $operation ),
			array(
				'post_type'  => $post->post_type,
				'post_title' => $post->post_title,
				'key'        => $key,
				'value'      => $this->maskValue( $value ),
			),
		);
	}

	private function isAuditedKey( string $key ): bool {
		/** @var string[] $allowed */
		$allowed = apply_filters( 'wp_log_watch_audited_meta_keys', self::DEFAULT_AUDITED_KEYS );

		return in_array( $key, $allowed, true );
	}

	/**
	 * @param mixed $value
	 */
	private function maskValue( $value ): string {
		if ( is_array( $value ) || is_object( $value ) ) {
			$value = wp_json_encode( $value );
		}

		$value = (string) $value;

		if ( mb_strlen( $value ) > 200 ) {
			return mb_substr( $value, 0, 200 ) . '…';
		}

		return $value;
	}
}
