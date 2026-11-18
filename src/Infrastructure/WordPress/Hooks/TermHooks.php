<?php

declare(strict_types=1);

namespace WPLogWatch\Infrastructure\WordPress\Hooks;

use WP_Term;
use WPLogWatch\Domain\ValueObjects\EventType;
use WPLogWatch\Domain\ValueObjects\ResourceType;

/**
 * Audits taxonomy term changes (all taxonomies by default).
 */
final class TermHooks extends AbstractHook {

	/**
	 * Built-in taxonomies whose terms are never audited: they are internal to
	 * WordPress (menus, post formats, legacy link categories) rather than
	 * user-managed content.
	 */
	private const IGNORED_TAXONOMIES = array( 'nav_menu', 'link_category', 'post_format' );

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
		if ( in_array( $taxonomy, self::IGNORED_TAXONOMIES, true ) ) {
			return false;
		}

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return false;
		}

		/**
		 * Filters whether term changes in a given taxonomy are audited.
		 *
		 * @param bool   $audited  Whether to audit this taxonomy. Default true.
		 * @param string $taxonomy The taxonomy name.
		 */
		return (bool) apply_filters( 'wp_log_watch_audit_taxonomy', true, $taxonomy );
	}
}
