<?php

declare(strict_types=1);

namespace WPLogWatch\Infrastructure\WordPress\Hooks;

use WP_Term;
use WPLogWatch\Domain\ValueObjects\EventType;
use WPLogWatch\Domain\ValueObjects\ResourceType;

/**
 * Audits taxonomy term changes (categories and tags by default).
 */
final class TermHooks extends AbstractHook {

	private const AUDITED_TAXONOMIES = array( 'category', 'post_tag' );

	public function register(): void {
		add_action( 'created_term', array( $this, 'onCreated' ), 10, 3 );
		add_action( 'edited_term', array( $this, 'onEdited' ), 10, 3 );
		add_action( 'delete_term', array( $this, 'onDeleted' ), 10, 4 );
	}

	public function onCreated( int $termId, int $ttId, string $taxonomy ): void {
		if ( ! $this->isAudited( $taxonomy ) ) {
			return;
		}

		$term = get_term( $termId, $taxonomy );
		if ( ! $term instanceof WP_Term ) {
			return;
		}

		$this->record(
			EventType::fromString( EventType::TERM_CREATED ),
			ResourceType::fromString( ResourceType::TERM ),
			$termId,
			sprintf( 'Term "%s" created in "%s".', $term->name, $taxonomy ),
			array(
				'taxonomy' => $taxonomy,
				'name'     => $term->name,
				'slug'     => $term->slug,
			),
		);
	}

	public function onEdited( int $termId, int $ttId, string $taxonomy ): void {
		if ( ! $this->isAudited( $taxonomy ) ) {
			return;
		}

		$term = get_term( $termId, $taxonomy );
		if ( ! $term instanceof WP_Term ) {
			return;
		}

		$this->record(
			EventType::fromString( EventType::TERM_UPDATED ),
			ResourceType::fromString( ResourceType::TERM ),
			$termId,
			sprintf( 'Term "%s" updated.', $term->name ),
			array(
				'taxonomy' => $taxonomy,
				'name'     => $term->name,
				'slug'     => $term->slug,
			),
		);
	}

	public function onDeleted( int $termId, int $ttId, string $taxonomy, WP_Term $deletedTerm ): void {
		if ( ! $this->isAudited( $taxonomy ) ) {
			return;
		}

		$this->record(
			EventType::fromString( EventType::TERM_DELETED ),
			ResourceType::fromString( ResourceType::TERM ),
			$termId,
			sprintf( 'Term "%s" deleted from "%s".', $deletedTerm->name, $taxonomy ),
			array(
				'taxonomy' => $taxonomy,
				'name'     => $deletedTerm->name,
				'slug'     => $deletedTerm->slug,
			),
		);
	}

	private function isAudited( string $taxonomy ): bool {
		return in_array( $taxonomy, self::AUDITED_TAXONOMIES, true );
	}
}
