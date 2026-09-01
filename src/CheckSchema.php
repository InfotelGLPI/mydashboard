<?php

/**
 * -------------------------------------------------------------------------
 * mydashboard plugin for GLPI
 * Copyright (C) 2016-2026 by the mydashboard Development Team.
 *
 * https://github.com/InfotelGLPI/mydashboard
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of mydashboard.
 *
 * mydashboard is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * mydashboard is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Mydashboard;

use CommonDBTM;
use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use Glpi\System\Diagnostic\DatabaseSchemaIntegrityChecker;
use Plugin;

/**
 *
 */
class CheckSchema extends CommonDBTM
{
    public static $rightname = 'plugin_mydashboard';
    private $table = "";

    /**
     * functions mandatory
     * getTypeName(), canCreate(), canView()
     *
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return __('Schema check', 'mydashboard');
    }

    public static function getIcon()
    {
        return "ti ti-check";
    }

    public static function getTable($classname = null)
    {
        return "glpi_plugin_mydashboard_configs";
    }

    /**
     * @param \CommonGLPI $item
     * @param int $withtemplate
     *
     * @return string
     * @see CommonGLPI::getTabNameForItem()
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == Config::class) {
            return self::createTabEntry(self::getTypeName());
        }
        return '';
    }

    /**
     * @param \CommonGLPI $item
     * @param int $tabnum
     * @param int $withtemplate
     *
     * @return bool
     * @see CommonGLPI::displayTabContentForItem()
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() == Config::class) {
            $self = new self();
            $self->checkSchema(PLUGIN_MYDASHBOARD_VERSION);
        }
        return true;
    }

    /**
     * Get the path to the empty SQL schema file
     *
     * @return string|null
     */
    public function getSchemaPath(?string $version = null): ?string
    {
        if ($version === null) {
            $version = PLUGIN_MYDASHBOARD_VERSION;
        }

        // Drop suffixes for alpha, beta, rc versions
        $matches = [];
        preg_match('/^(\d+\.\d+\.\d+)/', $version, $matches);
        $version = $matches[1];

        return Plugin::getPhpDir('mydashboard') . "/install/sql/empty.sql";
    }

    /**
     * Check the schema of all tables of the plugin against the expected schema of the given version
     *
     * @return boolean
     */
    public function checkSchema(
        string $version,
        bool $strict = false,
        bool $ignore_innodb_migration = true,
        bool $ignore_timestamps_migration = true,
        bool $ignore_utf8mb4_migration = true,
        bool $ignore_dynamic_row_format_migration = true,
        bool $ignore_unsigned_keys_migration = true
    ): bool {
        global $DB;

        $schemaFile = $this->getSchemaPath($version);

        $checker = new DatabaseSchemaIntegrityChecker(
            $DB,
            $strict,
            $ignore_innodb_migration,
            $ignore_timestamps_migration,
            $ignore_utf8mb4_migration,
            $ignore_dynamic_row_format_migration,
            $ignore_unsigned_keys_migration,
        );

        try {
            if ($schemaFile === null || !file_exists($schemaFile)) {
                throw new \RuntimeException('Schema file not available.');
            }
            $differences = $checker->checkCompleteSchema($schemaFile, true, 'plugin:mydashboard');
            // Raw (not normalized) "CREATE TABLE" queries, used to build the suggested queries.
            $expected_queries = $checker->extractSchemaFromFile($schemaFile);
        } catch (\Throwable $e) {
            TemplateRenderer::getInstance()->display('@mydashboard/checkschema_result.html.twig', [
                'error'           => __('Failed to check the sanity of the tables!', 'mydashboard'),
                'results'         => [],
                'all_safe'        => [],
                'all_destructive' => [],
            ]);
            return false;
        }

        $results         = [];
        $all_safe        = [];
        $all_destructive = [];
        foreach ($differences as $table_name => $difference) {
            $queries = $this->buildFixQueries($table_name, $difference, $expected_queries);

            $results[] = [
                'table'       => $table_name,
                'type'        => $difference['type'],
                'diff'        => $difference['diff'],
                'safe'        => $queries['safe'],
                'destructive' => $queries['destructive'],
            ];

            $all_safe        = array_merge($all_safe, $queries['safe']);
            $all_destructive = array_merge($all_destructive, $queries['destructive']);
        }

        TemplateRenderer::getInstance()->display('@mydashboard/checkschema_result.html.twig', [
            'error'           => '',
            'results'         => $results,
            'all_safe'        => $all_safe,
            'all_destructive' => $all_destructive,
        ]);

        return count($results) === 0;
    }

    /**
     * Build the queries that could be run to remove the differences found on a table.
     *
     * @param string $table_name
     * @param array $difference        Entry returned by DatabaseSchemaIntegrityChecker::checkCompleteSchema()
     * @param array $expected_queries  Raw "CREATE TABLE" queries, indexed by table name
     *
     * @return array{safe: array<int, string>, destructive: array<int, string>}
     */
    private function buildFixQueries(string $table_name, array $difference, array $expected_queries): array
    {
        $queries = ['safe' => [], 'destructive' => []];

        switch ($difference['type']) {
            case DatabaseSchemaIntegrityChecker::RESULT_TYPE_MISSING_TABLE:
                if (isset($expected_queries[$table_name])) {
                    $queries['safe'][] = rtrim(trim($expected_queries[$table_name]), ';') . ';';
                }
                break;

            case DatabaseSchemaIntegrityChecker::RESULT_TYPE_UNKNOWN_TABLE:
                $queries['destructive'][] = sprintf('DROP TABLE `%s`;', $table_name);
                break;

            case DatabaseSchemaIntegrityChecker::RESULT_TYPE_ALTERED_TABLE:
                $queries = $this->buildAlterQueries(
                    $table_name,
                    $difference['diff'],
                    $expected_queries[$table_name] ?? '',
                );
                break;
        }

        return $queries;
    }

    /**
     * Build the "ALTER TABLE" queries corresponding to the differences found on a table.
     *
     * Definitions found in the diff are normalized by the core checker (implicit "DEFAULT NULL" removed,
     * "longtext" reduced to "text", "unsigned" removed on keys, ...), so they are only used to know which
     * columns/indexes differ. Emitted queries always rely on the raw definitions of the schema file.
     *
     * @param string $table_name
     * @param string $diff                   Diff computed by the core checker
     * @param string $expected_create_query  Raw "CREATE TABLE" query of the schema file
     *
     * @return array{safe: array<int, string>, destructive: array<int, string>}
     */
    private function buildAlterQueries(string $table_name, string $diff, string $expected_create_query): array
    {
        $expected = $this->splitDefinitions($expected_create_query);
        $changes  = $this->parseDiff($diff);

        $alter_clauses = [];
        $drop_clauses  = [];

        // Handle columns in the order of the schema file, so that "AFTER" clauses stay valid when
        // several missing columns are added by the same query.
        $column_names    = array_keys($expected['columns']);
        $changed_columns = $changes['expected']['columns'];
        uksort(
            $changed_columns,
            function (string $a, string $b) use ($column_names): int {
                $a_position = array_search($a, $column_names, true);
                $b_position = array_search($b, $column_names, true);
                return ($a_position === false ? PHP_INT_MAX : $a_position)
                    <=> ($b_position === false ? PHP_INT_MAX : $b_position);
            },
        );

        foreach ($changed_columns as $name => $normalized_definition) {
            $definition = $expected['columns'][$name] ?? $normalized_definition;

            if (isset($changes['effective']['columns'][$name])) {
                $alter_clauses[] = 'MODIFY ' . $definition;
                continue;
            }

            $position = array_search($name, $column_names, true);
            if ($position === false) {
                $alter_clauses[] = 'ADD ' . $definition;
            } elseif ($position === 0) {
                $alter_clauses[] = 'ADD ' . $definition . ' FIRST';
            } else {
                $alter_clauses[] = 'ADD ' . $definition . sprintf(' AFTER `%s`', $column_names[$position - 1]);
            }
        }

        foreach (array_keys($changes['effective']['columns']) as $name) {
            if (!isset($changes['expected']['columns'][$name])) {
                $drop_clauses[] = sprintf('DROP COLUMN `%s`', $name);
            }
        }

        foreach ($changes['expected']['indexes'] as $name => $change) {
            if (isset($changes['effective']['indexes'][$name])) {
                // Index exists but differs: it has to be dropped before being added again.
                $alter_clauses[] = $this->getDropKeyClause($name, $changes['effective']['indexes'][$name]['kind']);
            }
            $alter_clauses[] = 'ADD ' . ($expected['indexes'][$name]['definition'] ?? $change['definition']);
        }

        foreach ($changes['effective']['indexes'] as $name => $change) {
            if (!isset($changes['expected']['indexes'][$name])) {
                $drop_clauses[] = $this->getDropKeyClause($name, $change['kind']);
            }
        }

        $safe = [];
        if (count($alter_clauses) > 0) {
            $safe[] = sprintf("ALTER TABLE `%s`\n    %s;", $table_name, implode(",\n    ", $alter_clauses));
        }
        if ($changes['expected']['properties'] !== null && $expected['properties'] !== '') {
            $safe[] = sprintf('ALTER TABLE `%s` %s;', $table_name, $expected['properties']);
        }

        $destructive = [];
        if (count($drop_clauses) > 0) {
            $destructive[] = sprintf("ALTER TABLE `%s`\n    %s;", $table_name, implode(",\n    ", $drop_clauses));
        }

        return ['safe' => $safe, 'destructive' => $destructive];
    }

    /**
     * Get the clause used to drop a key.
     *
     * @param string $name
     * @param string $kind  One of the kinds returned by self::classifyDefinition()
     *
     * @return string
     */
    private function getDropKeyClause(string $name, string $kind): string
    {
        return match ($kind) {
            'primary'    => 'DROP PRIMARY KEY',
            'constraint' => sprintf('DROP FOREIGN KEY `%s`', $name),
            default      => sprintf('DROP KEY `%s`', $name),
        };
    }

    /**
     * Extract the definitions that differ from the diff computed by the core checker.
     * "expected" entries come from the schema file, "effective" ones from the database.
     *
     * @param string $diff
     *
     * @return array<string, array{columns: array<string, string>, indexes: array<string, array{kind: string, definition: string}>, properties: string|null}>
     */
    private function parseDiff(string $diff): array
    {
        $changes = [
            'expected'  => ['columns' => [], 'indexes' => [], 'properties' => null],
            'effective' => ['columns' => [], 'indexes' => [], 'properties' => null],
        ];

        $lines = preg_split('/\R/', $diff);
        foreach ($lines === false ? [] : $lines as $line) {
            if (
                $line === ''
                || str_starts_with($line, '---')
                || str_starts_with($line, '+++')
                || str_starts_with($line, '@@')
            ) {
                continue;
            }

            $side = match ($line[0]) {
                '-'     => 'expected',
                '+'     => 'effective',
                default => null,
            };
            if ($side === null) {
                continue;
            }

            $definition = rtrim(trim(substr($line, 1)), ',');
            $info       = $this->classifyDefinition($definition);
            if ($info === null) {
                continue;
            }

            if ($info['kind'] === 'column') {
                $changes[$side]['columns'][$info['name']] = $definition;
            } elseif ($info['kind'] === 'properties') {
                $changes[$side]['properties'] = $definition;
            } else {
                $changes[$side]['indexes'][$info['name']] = [
                    'kind'       => $info['kind'],
                    'definition' => $definition,
                ];
            }
        }

        return $changes;
    }

    /**
     * Split a raw "CREATE TABLE" query into its column, index and table properties definitions.
     *
     * @param string $create_query
     *
     * @return array{columns: array<string, string>, indexes: array<string, array{kind: string, definition: string}>, properties: string}
     */
    private function splitDefinitions(string $create_query): array
    {
        $definitions = ['columns' => [], 'indexes' => [], 'properties' => ''];

        $body_start = strpos($create_query, '(');
        $body_end   = strrpos($create_query, ')');
        if ($body_start === false || $body_end === false || $body_end < $body_start) {
            return $definitions;
        }

        $definitions['properties'] = $this->normalizeSpaces(substr($create_query, $body_end + 1));

        $body = substr($create_query, $body_start + 1, $body_end - $body_start - 1);
        foreach ($this->splitOnCommas($body) as $definition) {
            $definition = $this->normalizeSpaces($definition);
            $info       = $this->classifyDefinition($definition);
            if ($info === null || $info['kind'] === 'properties') {
                continue;
            }

            if ($info['kind'] === 'column') {
                $definitions['columns'][$info['name']] = $definition;
            } else {
                $definitions['indexes'][$info['name']] = [
                    'kind'       => $info['kind'],
                    'definition' => $definition,
                ];
            }
        }

        return $definitions;
    }

    /**
     * Split the body of a "CREATE TABLE" query on the commas separating its definitions.
     * Commas found inside backticks, quotes or parenthesis (index fields list, "decimal(10,2)", ...)
     * are not separators.
     *
     * @param string $body
     *
     * @return array<int, string>
     */
    private function splitOnCommas(string $body): array
    {
        $parts        = [];
        $current      = '';
        $depth        = 0;
        $in_backticks = false;
        $in_quotes    = false;
        $length       = strlen($body);

        for ($i = 0; $i < $length; $i++) {
            $char = $body[$i];

            if ($char === '\\' && ($in_backticks || $in_quotes)) {
                // Escaped char, keep it as is
                $current .= $char;
                if ($i + 1 < $length) {
                    $current .= $body[++$i];
                }
                continue;
            }

            if ($char === '`' && !$in_quotes) {
                $in_backticks = !$in_backticks;
            } elseif ($char === "'" && !$in_backticks) {
                $in_quotes = !$in_quotes;
            } elseif (!$in_backticks && !$in_quotes) {
                if ($char === '(') {
                    $depth++;
                } elseif ($char === ')') {
                    $depth--;
                } elseif ($char === ',' && $depth === 0) {
                    $parts[] = $current;
                    $current = '';
                    continue;
                }
            }

            $current .= $char;
        }

        if (trim($current) !== '') {
            $parts[] = $current;
        }

        return $parts;
    }

    /**
     * Get the kind and the name of a column/index/properties definition.
     *
     * @param string $definition
     *
     * @return array{kind: string, name: string}|null
     */
    private function classifyDefinition(string $definition): ?array
    {
        $matches = [];

        if (preg_match('/^`(?<name>\w+)`/', $definition, $matches) === 1) {
            return ['kind' => 'column', 'name' => $matches['name']];
        }
        if (preg_match('/^PRIMARY\s+KEY/i', $definition) === 1) {
            return ['kind' => 'primary', 'name' => 'PRIMARY'];
        }
        if (preg_match('/^CONSTRAINT\s+`(?<name>\w+)`/i', $definition, $matches) === 1) {
            return ['kind' => 'constraint', 'name' => $matches['name']];
        }
        if (preg_match('/^((UNIQUE|FULLTEXT|SPATIAL)\s+)?(KEY|INDEX)\s+`(?<name>\w+)`/i', $definition, $matches) === 1) {
            return ['kind' => 'index', 'name' => $matches['name']];
        }
        if (str_starts_with($definition, ')')) {
            return ['kind' => 'properties', 'name' => 'properties'];
        }

        return null;
    }

    /**
     * Collapse the whitespaces that are not part of a quoted value.
     *
     * @param string $definition
     *
     * @return string
     */
    private function normalizeSpaces(string $definition): string
    {
        $normalized = preg_replace('/\s+(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', ' ', $definition);

        return trim($normalized === null ? $definition : $normalized);
    }
}
