<?php
final class SchemaWeave_WordPress_Installer
{
    public const VERSION_OPTION = 'schemaweave_db_version';

    public static function boot(): void
    {
        add_action('plugins_loaded', [self::class, 'maybeUpgrade'], 5);
    }

    public static function activate(): void
    {
        SchemaWeave_WordPress_Settings::activate();
        update_option(self::VERSION_OPTION, SCHEMAWEAVE_VERSION);
    }

    public static function maybeUpgrade(): void
    {
        $stored = (string) get_option(self::VERSION_OPTION, '');
        if ($stored === SCHEMAWEAVE_VERSION) {
            return;
        }

        $settings = get_option(SchemaWeave_WordPress_Settings::OPTION, []);
        if (!is_array($settings)) {
            $settings = [];
        }

        // Re-sanitize against the current defaults so new keys are added safely
        // without discarding existing valid configuration.
        update_option(
            SchemaWeave_WordPress_Settings::OPTION,
            SchemaWeave_WordPress_Settings::sanitize(
                array_replace_recursive(SchemaWeave_WordPress_Settings::defaults(), $settings)
            )
        );

        update_option(self::VERSION_OPTION, SCHEMAWEAVE_VERSION);
    }
}
