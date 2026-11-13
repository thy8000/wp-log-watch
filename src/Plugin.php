<?php

declare(strict_types=1);

namespace WPLogWatch;

use WPLogWatch\Application\Contracts\ClockInterface;
use WPLogWatch\Application\Services\SensitiveDataFilter;
use WPLogWatch\Application\UseCases\DeleteExpiredAuditLogs;
use WPLogWatch\Application\UseCases\ExportAuditLogs;
use WPLogWatch\Application\UseCases\GetAuditLog;
use WPLogWatch\Application\UseCases\RecordAuditEvent;
use WPLogWatch\Application\UseCases\SearchAuditLogs;
use WPLogWatch\Domain\Contracts\AuditLogRepositoryInterface;
use WPLogWatch\Infrastructure\Cron\RetentionScheduler;
use WPLogWatch\Infrastructure\Logging\ErrorLogger;
use WPLogWatch\Infrastructure\Persistence\Database\AuditTableSchema;
use WPLogWatch\Infrastructure\Persistence\WordPressAuditLogRepository;
use WPLogWatch\Infrastructure\WordPress\Capabilities;
use WPLogWatch\Infrastructure\WordPress\RequestContext;
use WPLogWatch\Infrastructure\WordPress\RetentionSettings;
use WPLogWatch\Infrastructure\WordPress\WordPressClock;
use WPLogWatch\Infrastructure\WordPress\WordPressUserProvider;
use WPLogWatch\Infrastructure\WordPress\Hooks\PluginHooks;
use WPLogWatch\Infrastructure\WordPress\Hooks\PostHooks;
use WPLogWatch\Infrastructure\WordPress\Hooks\PostMetaHooks;
use WPLogWatch\Infrastructure\WordPress\Hooks\TermHooks;
use WPLogWatch\Infrastructure\WordPress\Hooks\ThemeHooks;
use WPLogWatch\Infrastructure\WordPress\Hooks\UserHooks;
use WPLogWatch\Presentation\Admin\AdminMenu;
use WPLogWatch\Presentation\Admin\Controllers\AuditLogController;
use WPLogWatch\Presentation\Admin\Controllers\SettingsController;
use WPLogWatch\Presentation\Rest\AuditLogController as RestAuditLogController;
use WPLogWatch\Shared\Container\Container;

/**
 * Composition root: builds the service container and wires the plugin together.
 */
final class Plugin {

	private static ?self $instance = null;

	private Container $container;

	private function __construct() {
		$this->container = new Container();
		$this->wire();
	}

	public static function instance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function boot(): void {
		register_activation_hook( WP_LOG_WATCH_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( WP_LOG_WATCH_FILE, array( $this, 'deactivate' ) );

		$this->register();
		$this->loadTextdomain();
		$this->scheduleUpgrades();
	}

	public function activate(): void {
		AuditTableSchema::createTable();
		Capabilities::install();
		$this->container->get( RetentionScheduler::class )->schedule();
	}

	public function deactivate(): void {
		$this->container->get( RetentionScheduler::class )->unschedule();
	}

	private function wire(): void {
		$c = $this->container;

		$c->set( ClockInterface::class, new WordPressClock() );
		$c->set( WordPressUserProvider::class, new WordPressUserProvider() );
		$c->set( RequestContext::class, new RequestContext() );
		$c->set( SensitiveDataFilter::class, new SensitiveDataFilter() );
		$c->set( RetentionSettings::class, new RetentionSettings() );
		$c->set( ErrorLogger::class, new ErrorLogger() );
		$c->set( AuditLogRepositoryInterface::class, new WordPressAuditLogRepository() );

		$c->factory(
			RecordAuditEvent::class,
			static fn (): RecordAuditEvent => new RecordAuditEvent(
				$c->get( AuditLogRepositoryInterface::class ),
				$c->get( ClockInterface::class ),
				$c->get( SensitiveDataFilter::class ),
			)
		);

		$c->factory(
			SearchAuditLogs::class,
			static fn (): SearchAuditLogs => new SearchAuditLogs(
				$c->get( AuditLogRepositoryInterface::class ),
			)
		);

		$c->factory(
			GetAuditLog::class,
			static fn (): GetAuditLog => new GetAuditLog(
				$c->get( AuditLogRepositoryInterface::class ),
			)
		);

		$c->factory(
			ExportAuditLogs::class,
			static fn (): ExportAuditLogs => new ExportAuditLogs(
				$c->get( AuditLogRepositoryInterface::class ),
			)
		);

		$c->factory(
			DeleteExpiredAuditLogs::class,
			static fn (): DeleteExpiredAuditLogs => new DeleteExpiredAuditLogs(
				$c->get( AuditLogRepositoryInterface::class ),
				$c->get( ClockInterface::class ),
			)
		);

		$this->wireHooks( $c );
		$this->wireCron( $c );
		$this->wirePresentation( $c );
	}

	private function wireHooks( Container $c ): void {
		$hookFactory = static fn ( string $hookClass ): object => new $hookClass(
			$c->get( RecordAuditEvent::class ),
			$c->get( WordPressUserProvider::class ),
			$c->get( RequestContext::class ),
			$c->get( ErrorLogger::class ),
		);

		$c->factory( PostHooks::class, static fn (): PostHooks => $hookFactory( PostHooks::class ) );
		$c->factory( TermHooks::class, static fn (): TermHooks => $hookFactory( TermHooks::class ) );
		$c->factory( UserHooks::class, static fn (): UserHooks => $hookFactory( UserHooks::class ) );
		$c->factory( PluginHooks::class, static fn (): PluginHooks => $hookFactory( PluginHooks::class ) );
		$c->factory( ThemeHooks::class, static fn (): ThemeHooks => $hookFactory( ThemeHooks::class ) );
		$c->factory( PostMetaHooks::class, static fn (): PostMetaHooks => $hookFactory( PostMetaHooks::class ) );
	}

	private function wireCron( Container $c ): void {
		$c->factory(
			RetentionScheduler::class,
			static fn (): RetentionScheduler => new RetentionScheduler(
				$c->get( DeleteExpiredAuditLogs::class ),
				$c->get( RetentionSettings::class ),
				$c->get( ErrorLogger::class ),
			)
		);
	}

	private function wirePresentation( Container $c ): void {
		$c->factory(
			AuditLogController::class,
			static fn (): AuditLogController => new AuditLogController(
				$c->get( SearchAuditLogs::class ),
				$c->get( GetAuditLog::class ),
				$c->get( ExportAuditLogs::class ),
			)
		);

		$c->factory(
			SettingsController::class,
			static fn (): SettingsController => new SettingsController(
				$c->get( RetentionSettings::class ),
			)
		);

		$c->factory(
			AdminMenu::class,
			static fn (): AdminMenu => new AdminMenu(
				$c->get( AuditLogController::class ),
				$c->get( SettingsController::class ),
			)
		);

		$c->factory(
			RestAuditLogController::class,
			static fn (): RestAuditLogController => new RestAuditLogController(
				$c->get( SearchAuditLogs::class ),
				$c->get( GetAuditLog::class ),
			)
		);
	}

	private function register(): void {
		foreach ( array(
			PostHooks::class,
			TermHooks::class,
			UserHooks::class,
			PluginHooks::class,
			ThemeHooks::class,
			PostMetaHooks::class,
		) as $hookClass ) {
			$this->container->get( $hookClass )->register();
		}

		$this->container->get( RetentionScheduler::class )->register();
		$this->container->get( AdminMenu::class )->register();
		$this->container->get( RestAuditLogController::class )->register();
	}

	private function scheduleUpgrades(): void {
		add_action(
			'admin_init',
			static function (): void {
				AuditTableSchema::maybeUpgrade();
			}
		);
	}

	private function loadTextdomain(): void {
		add_action(
			'init',
			static function (): void {
				load_plugin_textdomain(
					'wp-log-watch',
					false,
					dirname( plugin_basename( WP_LOG_WATCH_FILE ) ) . '/languages'
				);
			}
		);
	}
}
