<?php

declare(strict_types=1);

namespace WPLogWatch\Infrastructure\WordPress\Hooks;

use WP_Post;
use WPLogWatch\Domain\ValueObjects\EventType;
use WPLogWatch\Domain\ValueObjects\ResourceType;

/**
 * Audits post/page lifecycle: creation, updates, status transitions and
 * permanent deletion.
 */
final class PostHooks extends AbstractHook {

	private const IGNORED_POST_TYPES = array(
		'revision',
		'attachment',
		'nav_menu_item',
		'custom_css',
		'customize_changeset',
		'oembed_cache',
		'user_request',
		'wp_block',
		'wp_template',
		'wp_template_part',
		'wp_global_styles',
	);

	/**
	 * @var array<int, WP_Post>
	 */
	private array $previousPosts = array();

	public function register(): void {
		add_action( 'pre_post_update', array( $this, 'onPreUpdate' ), 10, 2 );
		add_action( 'save_post', array( $this, 'onSavePost' ), 10, 3 );
		add_action( 'transition_post_status', array( $this, 'onTransitionStatus' ), 10, 3 );
		add_action( 'before_delete_post', array( $this, 'onDeletePost' ), 10, 2 );
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public function onPreUpdate( int $postId, array $data ): void {
		$post = get_post( $postId );

		if ( $post instanceof WP_Post ) {
			$this->previousPosts[ $postId ] = $post;
		}
	}

	public function onSavePost( int $postId, WP_Post $post, bool $update ): void {
		if ( $this->shouldSkip( $post ) ) {
			return;
		}

		if ( in_array( $post->post_status, array( 'auto-draft', 'inherit' ), true ) ) {
			return;
		}

		if ( ! $update ) {
			$this->recordCreation( $post );

			return;
		}

		$old = $this->previousPosts[ $postId ] ?? null;
		unset( $this->previousPosts[ $postId ] );

		// First real save of an auto-draft is semantically a creation.
		if ( $old instanceof WP_Post && in_array( $old->post_status, array( 'auto-draft', 'new' ), true ) ) {
			$this->recordCreation( $post );

			return;
		}

		if ( ! $old instanceof WP_Post ) {
			return;
		}

		$diff = $this->diffPosts( $old, $post );

		if ( $diff['before'] === array() ) {
			return;
		}

		$this->record(
			EventType::fromString( EventType::POST_UPDATED ),
			ResourceType::fromString( $post->post_type ),
			$postId,
			sprintf( 'Post "%s" updated.', $post->post_title ),
			array(
				'post_type' => $post->post_type,
				'before'    => $diff['before'],
				'after'     => $diff['after'],
			),
		);
	}

	public function onTransitionStatus( string $newStatus, string $oldStatus, WP_Post $post ): void {
		if ( $this->shouldSkip( $post ) ) {
			return;
		}

		$resourceType = ResourceType::fromString( $post->post_type );
		$metadata     = array(
			'post_type'  => $post->post_type,
			'old_status' => $oldStatus,
			'new_status' => $newStatus,
		);

		if ( $newStatus === 'publish' && $oldStatus !== 'publish' && ! in_array( $oldStatus, array( 'new', 'auto-draft' ), true ) ) {
			$this->record(
				EventType::fromString( EventType::POST_PUBLISHED ),
				$resourceType,
				(int) $post->ID,
				sprintf( 'Post "%s" published.', $post->post_title ),
				$metadata,
			);
		} elseif ( $newStatus === 'trash' && $oldStatus !== 'trash' ) {
			$this->record(
				EventType::fromString( EventType::POST_TRASHED ),
				$resourceType,
				(int) $post->ID,
				sprintf( 'Post "%s" moved to trash.', $post->post_title ),
				$metadata,
			);
		} elseif ( $oldStatus === 'trash' && $newStatus !== 'trash' ) {
			$this->record(
				EventType::fromString( EventType::POST_RESTORED ),
				$resourceType,
				(int) $post->ID,
				sprintf( 'Post "%s" restored from trash.', $post->post_title ),
				$metadata,
			);
		} elseif ( $newStatus === 'draft' && $oldStatus === 'publish' ) {
			$this->record(
				EventType::fromString( EventType::POST_DRAFTED ),
				$resourceType,
				(int) $post->ID,
				sprintf( 'Post "%s" unpublished.', $post->post_title ),
				$metadata,
			);
		}
	}

	public function onDeletePost( int $postId, WP_Post $post ): void {
		if ( $this->shouldSkip( $post ) ) {
			return;
		}

		$this->record(
			EventType::fromString( EventType::POST_DELETED ),
			ResourceType::fromString( $post->post_type ),
			$postId,
			sprintf( 'Post "%s" permanently deleted.', $post->post_title ),
			array(
				'post_type' => $post->post_type,
				'status'    => $post->post_status,
			),
		);
	}

	private function recordCreation( WP_Post $post ): void {
		$this->record(
			EventType::fromString( EventType::POST_CREATED ),
			ResourceType::fromString( $post->post_type ),
			(int) $post->ID,
			sprintf( 'Post "%s" created.', $post->post_title ),
			array(
				'post_type' => $post->post_type,
				'title'     => $post->post_title,
				'status'    => $post->post_status,
			),
		);
	}

	/**
	 * Compares only the fields the spec considers relevant, avoiding full
	 * content and arbitrary custom fields.
	 *
	 * @return array{before: array<string, mixed>, after: array<string, mixed>}
	 */
	private function diffPosts( WP_Post $oldPost, WP_Post $newPost ): array {
		$before = array();
		$after  = array();

		$fields = array(
			'post_title'  => 'title',
			'post_author' => 'author',
			'post_name'   => 'slug',
		);

		foreach ( $fields as $dbField => $label ) {
			$oldValue = $oldPost->{$dbField};
			$newValue = $newPost->{$dbField};

			if ( (string) $oldValue !== (string) $newValue ) {
				$before[ $label ] = $oldValue;
				$after[ $label ]  = $newValue;
			}
		}

		return array(
			'before' => $before,
			'after'  => $after,
		);
	}

	private function shouldSkip( WP_Post $post ): bool {
		if ( wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) ) {
			return true;
		}

		return in_array( $post->post_type, self::IGNORED_POST_TYPES, true );
	}
}
