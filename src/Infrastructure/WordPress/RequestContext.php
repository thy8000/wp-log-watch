<?php

declare(strict_types=1);

namespace WPLogWatch\Infrastructure\WordPress;

/**
 * Provides request-scoped context (origin, IP, user agent) for audit events.
 *
 * The IP is taken only from REMOTE_ADDR; forwarded headers such as
 * X-Forwarded-For are ignored on purpose, since they cannot be trusted without
 * an explicitly configured proxy chain.
 */
final class RequestContext {

	public function context(): string {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return 'wp-cli';
		}

		if ( wp_doing_cron() ) {
			return 'cron';
		}

		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			return 'xmlrpc';
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return 'rest-api';
		}

		if ( wp_doing_ajax() ) {
			return 'ajax';
		}

		if ( is_admin() ) {
			return 'wp-admin';
		}

		return 'frontend';
	}

	public function ipAddress(): ?string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : null;

		if ( ! is_string( $ip ) || $ip === '' ) {
			return null;
		}

		return filter_var( $ip, FILTER_VALIDATE_IP ) !== false ? $ip : null;
	}

	public function userAgent(): ?string {
		$userAgent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : null;

		if ( ! is_string( $userAgent ) || $userAgent === '' ) {
			return null;
		}

		return mb_substr( $userAgent, 0, 512 );
	}
}
