<?php

declare(strict_types=1);

namespace WPLogWatch\Application\DTOs;

/**
 * The result of an export: the serialized content plus download metadata.
 */
final class ExportResult {

	public function __construct(
		public readonly string $content,
		public readonly string $filename,
		public readonly string $contentType,
	) {
	}
}
