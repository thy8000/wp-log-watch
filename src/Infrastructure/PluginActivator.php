<?php

namespace WPTrace\Infrastructure;

use WPTrace\Infrastructure\Database\Migrations\CreateAuditEventsTable;

final class PluginActivator
{
    public static function activate(): void
    {
        $migration = new CreateAuditEventsTable();

        $migration->up();
    }
}