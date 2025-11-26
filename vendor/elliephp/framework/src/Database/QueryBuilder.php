<?php

declare(strict_types=1);

namespace ElliePHP\Framework\Database;

use ElliePHP\Framework\Exceptions\DatabaseException;
use InvalidArgumentException;

/**
 * Class QueryBuilder
 *
 * A powerful fluent query builder for constructing SQL queries safely.
 * Supports MySQL, PostgreSQL, and SQLite.
 */
final class QueryBuilder
{
    private Database $db;
    private string $table = '';
    private array $select = ['*'];
    private array $conditions = [];
    private array $bindings = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $join = [];
    private array $groupBy = [];
    private array $having = [];
    private array $havingBindings = [];
    private bool $distinct = false;
    private string $driver = 'mysql'; // mysql, pgsql, sqlite

    public function __construct(Database $db, string $driver = 'mysql')
    {
        $this->db = $db;
        $this->setDriver($driver);
    }

    /**
     * Set the database driver.
     */
    public function setDriver(string $driver): self
    {
        $driver = strtolower($driver);
        if (!in_array($driver, ['mysql', 'pgsql', 'sqlite'], true)) {
            throw new InvalidArgumentException("Unsupported driver: $driver");
        }
        $this->driver = $driver;
        return $this;
    }

    /**
     * Get the current driver.
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Set the table to query.
     */
    public function table(string $table): self
    {
        $this->validateIdentifier($table, 'table');
        $this->table = $table;
        return $this;
    }

    /**
     * Set columns to select.
     */
    public function select(string ...$columns): self
    {
        foreach ($columns as $column) {
            // Allow expressions but validate simple column names
            if (!str_contains($column, '(') && !str_contains($column, ' ')) {
                $this->validateIdentifier($column, 'column');
            }
        }
        $this->select = $columns;
        return $this;
    }

    /**
     * Add raw select expression.
     */
    public function selectRaw(string $expression, string $alias = ''): self
    {
        if ($alias) {
            $this->validateIdentifier($alias, 'alias');
            $this->select[] = "$expression AS $alias";
        } else {
            $this->select[] = $expression;
        }
        return $this;
    }

    /**
     * Add DISTINCT to query.
     */
    public function distinct(): self
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * Internal helper to add generic conditions.
     */
    private function addCondition(string $type, string $sql, array $bindings = []): self
    {
        $this->conditions[] = [
            'type' => $type,
            'sql'  => $sql
        ];
        $this->bindings = array_merge($this->bindings, $bindings);
        return $this;
    }

    /**
     * Add a WHERE condition.
     */
    public function where(string $column, string $operator, mixed $value = null, string $boolean = 'AND'): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->validateIdentifier($column, 'column');
        $this->validateOperator($operator);

        $placeholder = $this->generatePlaceholder($column);
        $sql = "$column $operator $placeholder";

        return $this->addCondition($boolean, $sql, [$placeholder => $value]);
    }

    /**
     * Add an OR WHERE condition.
     */
    public function orWhere(string $column, string $operator, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Add WHERE with custom logic group.
     */
    public function whereGroup(callable $callback, string $boolean = 'AND'): self
    {
        $query = new self($this->db, $this->driver);
        $query->table = $this->table; // Set table for context
        $callback($query);

        if (empty($query->conditions)) {
            return $this;
        }

        // Get nested bindings and conditions
        $nestedBindings = $query->getBindings();
        $finalBindings = [];
        $placeholderMap = [];

        // Create unique placeholders for nested query to avoid collisions
        foreach ($nestedBindings as $key => $val) {
            $newKey = $key;

            // If this key already exists in parent bindings, create a unique one
            if (array_key_exists($key, $this->bindings)) {
                $counter = 1;
                $baseKey = ltrim($key, ':');
                do {
                    $newKey = ":{$baseKey}_grp{$counter}";
                    $counter++;
                } while (array_key_exists($newKey, $this->bindings) || array_key_exists($newKey, $finalBindings));

                $placeholderMap[$key] = $newKey;
            }

            $finalBindings[$newKey] = $val;
        }

        // Build the nested SQL with proper placeholder replacement
        $parts = [];
        foreach ($query->conditions as $i => $cond) {
            $condSql = $cond['sql'];

            // Replace any colliding placeholders in this condition's SQL
            foreach ($placeholderMap as $oldPlaceholder => $newPlaceholder) {
                // Use word boundary to avoid partial replacements
                $condSql = preg_replace('/\b' . preg_quote($oldPlaceholder, '/') . '\b/', $newPlaceholder, $condSql);
            }

            // First condition in group doesn't need a logical operator prefix
            $prefix = ($i === 0) ? '' : $cond['type'] . ' ';
            $parts[] = $prefix . $condSql;
        }

        $groupedSql = '(' . implode(' ', $parts) . ')';

        return $this->addCondition($boolean, $groupedSql, $finalBindings);
    }

    /**
     * Add WHERE NULL condition.
     */
    public function whereNull(string $column, string $boolean = 'AND'): self
    {
        $this->validateIdentifier($column, 'column');
        return $this->addCondition($boolean, "$column IS NULL");
    }

    /**
     * Add WHERE NOT NULL condition.
     */
    public function whereNotNull(string $column, string $boolean = 'AND'): self
    {
        $this->validateIdentifier($column, 'column');
        return $this->addCondition($boolean, "$column IS NOT NULL");
    }

    /**
     * Add a WHERE IN condition.
     */
    public function whereIn(string $column, array $values, string $boolean = 'AND'): self
    {
        $this->validateIdentifier($column, 'column');

        if (empty($values)) {
            // Return a condition that always evaluates to false
            // Use proper SQL syntax instead of 1=0 to avoid OR confusion
            return $this;
        }

        $placeholders = [];
        $bindings = [];

        foreach ($values as $i => $value) {
            $key = $this->generatePlaceholder("{$column}_in_{$i}");
            $placeholders[] = $key;
            $bindings[$key] = $value;
        }

        $sql = "$column IN (" . implode(', ', $placeholders) . ")";
        return $this->addCondition($boolean, $sql, $bindings);
    }

    /**
     * Add a WHERE NOT IN condition.
     */
    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): self
    {
        $this->validateIdentifier($column, 'column');

        if (empty($values)) {
            return $this; // No condition needed
        }

        $placeholders = [];
        $bindings = [];

        foreach ($values as $i => $value) {
            $key = $this->generatePlaceholder("{$column}_notin_{$i}");
            $placeholders[] = $key;
            $bindings[$key] = $value;
        }

        $sql = "$column NOT IN (" . implode(', ', $placeholders) . ")";
        return $this->addCondition($boolean, $sql, $bindings);
    }

    /**
     * Add a WHERE BETWEEN condition.
     */
    public function whereBetween(string $column, mixed $start, mixed $end, string $boolean = 'AND'): self
    {
        $this->validateIdentifier($column, 'column');

        $pStart = $this->generatePlaceholder($column . '_start');
        $pEnd = $this->generatePlaceholder($column . '_end');

        $sql = "$column BETWEEN $pStart AND $pEnd";
        return $this->addCondition($boolean, $sql, [$pStart => $start, $pEnd => $end]);
    }

    /**
     * Add a WHERE LIKE condition.
     */
    public function whereLike(string $column, string $pattern, string $boolean = 'AND'): self
    {
        $this->validateIdentifier($column, 'column');

        $placeholder = $this->generatePlaceholder($column);
        return $this->addCondition($boolean, "$column LIKE $placeholder", [$placeholder => $pattern]);
    }

    /**
     * Add raw WHERE condition.
     */
    public function whereRaw(string $condition, array $bindings = [], string $boolean = 'AND'): self
    {
        return $this->addCondition($boolean, $condition, $bindings);
    }

    /**
     * Add a WHERE condition comparing two columns.
     */
    public function whereColumn(string $column1, string $operator, string $column2, string $boolean = 'AND'): self
    {
        $this->validateIdentifier($column1, 'column');
        $this->validateIdentifier($column2, 'column');
        $this->validateOperator($operator);

        return $this->addCondition($boolean, "$column1 $operator $column2");
    }

    /**
     * Add a date-based WHERE condition.
     */
    public function whereDate(string $column, string $operator, string $date, string $boolean = 'AND'): self
    {
        $this->validateIdentifier($column, 'column');
        $this->validateOperator($operator);

        $placeholder = $this->generatePlaceholder($column);
        return $this->addCondition($boolean, "DATE($column) $operator $placeholder", [$placeholder => $date]);
    }

    /**
     * Add an ORDER BY clause.
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->validateIdentifier($column, 'column');
        $direction = strtoupper($direction);

        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            throw new InvalidArgumentException("Invalid ORDER BY direction: $direction");
        }

        $this->orderBy[] = "$column $direction";
        return $this;
    }

    /**
     * Order by raw expression.
     */
    public function orderByRaw(string $expression): self
    {
        $this->orderBy[] = $expression;
        return $this;
    }

    /**
     * Add GROUP BY clause.
     */
    public function groupBy(string ...$columns): self
    {
        foreach ($columns as $column) {
            $this->validateIdentifier($column, 'column');
        }
        $this->groupBy = array_merge($this->groupBy, $columns);
        return $this;
    }

    /**
     * Add HAVING clause.
     */
    public function having(string $column, string $operator, mixed $value, string $boolean = 'AND'): self
    {
        $this->validateIdentifier($column, 'column');
        $this->validateOperator($operator);

        $placeholder = $this->generatePlaceholder('having_' . $column);

        $this->having[] = [
            'type' => $boolean,
            'sql' => "$column $operator $placeholder"
        ];
        $this->havingBindings[$placeholder] = $value;

        return $this;
    }

    /**
     * Add HAVING with OR boolean.
     */
    public function orHaving(string $column, string $operator, mixed $value): self
    {
        return $this->having($column, $operator, $value, 'OR');
    }

    /**
     * Add raw HAVING clause.
     */
    public function havingRaw(string $condition, array $bindings = [], string $boolean = 'AND'): self
    {
        $this->having[] = [
            'type' => $boolean,
            'sql' => $condition
        ];
        $this->havingBindings = array_merge($this->havingBindings, $bindings);

        return $this;
    }

    /**
     * Set LIMIT.
     */
    public function limit(int $limit): self
    {
        if ($limit < 0) {
            throw new InvalidArgumentException("LIMIT must be non-negative");
        }
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set OFFSET.
     */
    public function offset(int $offset): self
    {
        if ($offset < 0) {
            throw new InvalidArgumentException("OFFSET must be non-negative");
        }
        $this->offset = $offset;
        return $this;
    }

    /**
     * Add a JOIN clause.
     */
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->validateIdentifier($table, 'table');
        $this->validateIdentifier($first, 'column');
        $this->validateIdentifier($second, 'column');
        $this->validateOperator($operator);

        $type = strtoupper($type);
        $validTypes = ['INNER', 'LEFT', 'RIGHT', 'FULL', 'CROSS'];

        if (!in_array($type, $validTypes, true)) {
            throw new InvalidArgumentException("Invalid JOIN type: $type");
        }

        $this->join[] = "$type JOIN $table ON $first $operator $second";
        return $this;
    }

    /**
     * Add a LEFT JOIN.
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * Add a RIGHT JOIN.
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * Execute and fetch all results.
     */
    public function get(): array
    {
        $this->ensureTableSet();
        $sql = $this->buildSelect();
        $bindings = $this->getAllBindings();
        return $this->db->fetchAll($sql, $bindings);
    }

    /**
     * Execute and fetch first result.
     */
    public function first(): ?array
    {
        $this->ensureTableSet();
        $currentLimit = $this->limit;
        $this->limit(1);
        $sql = $this->buildSelect();
        $bindings = $this->getAllBindings();

        // Restore limit for reusing builder object if necessary
        $this->limit = $currentLimit;

        return $this->db->fetch($sql, $bindings);
    }

    /**
     * Find a record by primary key.
     */
    public function find(int|string $id, string $column = 'id'): ?array
    {
        return $this->where($column, '=', $id)->first();
    }

    /**
     * Get count of rows.
     */
    public function count(string $column = '*'): int
    {
        $this->ensureTableSet();
        $sql = $this->buildAggregate('COUNT', $column);
        $bindings = $this->getAllBindings();
        return (int)$this->db->fetchColumn($sql, $bindings);
    }

    /**
     * Get maximum value.
     */
    public function max(string $column): mixed
    {
        $this->ensureTableSet();
        $this->validateIdentifier($column, 'column');
        $sql = $this->buildAggregate('MAX', $column);
        return $this->db->fetchColumn($sql, $this->getAllBindings());
    }

    /**
     * Get minimum value.
     */
    public function min(string $column): mixed
    {
        $this->ensureTableSet();
        $this->validateIdentifier($column, 'column');
        $sql = $this->buildAggregate('MIN', $column);
        return $this->db->fetchColumn($sql, $this->getAllBindings());
    }

    /**
     * Get average value.
     */
    public function avg(string $column): mixed
    {
        $this->ensureTableSet();
        $this->validateIdentifier($column, 'column');
        $sql = $this->buildAggregate('AVG', $column);
        return $this->db->fetchColumn($sql, $this->getAllBindings());
    }

    /**
     * Get sum of values.
     */
    public function sum(string $column): mixed
    {
        $this->ensureTableSet();
        $this->validateIdentifier($column, 'column');
        $sql = $this->buildAggregate('SUM', $column);
        return $this->db->fetchColumn($sql, $this->getAllBindings());
    }

    /**
     * Check if records exist.
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Paginate results.
     */
    public function paginate(int $perPage = 15, int $page = 1): array
    {
        if ($perPage < 1) {
            throw new InvalidArgumentException("Per page must be at least 1");
        }
        if ($page < 1) {
            throw new InvalidArgumentException("Page must be at least 1");
        }

        // Create a separate query for counting
        $countQuery = clone $this;
        $countQuery->orderBy = []; // Remove ordering for count
        $countQuery->limit = null; // Remove limit
        $countQuery->offset = null; // Remove offset
        $total = $countQuery->count();

        $offset = ($page - 1) * $perPage;
        $items = $this->limit($perPage)->offset($offset)->get();

        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int)ceil($total / $perPage),
            'from' => $total === 0 ? 0 : $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
    }

    /**
     * Insert a record.
     */
    public function insert(array $data): int
    {
        $this->ensureTableSet();

        if (empty($data)) {
            throw new DatabaseException('Cannot insert empty data');
        }

        $columns = array_keys($data);
        foreach ($columns as $col) {
            $this->validateIdentifier($col, 'column');
        }

        $placeholders = [];
        $bindings = [];

        foreach ($data as $col => $val) {
            $key = ":{$col}";
            $placeholders[] = $key;
            $bindings[$key] = $val;
        }

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        return $this->db->insert($sql, $bindings);
    }

    /**
     * Insert multiple records.
     */
    public function insertMany(array $records): int
    {
        $this->ensureTableSet();

        if (empty($records)) {
            throw new DatabaseException('Cannot insert empty records');
        }

        $firstRecord = reset($records);
        if (!is_array($firstRecord)) {
            throw new DatabaseException('Records must be an array of arrays');
        }

        $columns = array_keys($firstRecord);
        foreach ($columns as $col) {
            $this->validateIdentifier($col, 'column');
        }

        $valueSets = [];
        $bindings = [];
        $rowIndex = 0;

        foreach ($records as $record) {
            $placeholders = [];
            foreach ($columns as $col) {
                $key = ":{$col}_{$rowIndex}";
                $placeholders[] = $key;
                $bindings[$key] = $record[$col] ?? null;
            }
            $valueSets[] = '(' . implode(', ', $placeholders) . ')';
            $rowIndex++;
        }

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES %s",
            $this->table,
            implode(', ', $columns),
            implode(', ', $valueSets)
        );

        $this->db->exec($sql, $bindings);
        return count($records);
    }

    /**
     * Insert or update on duplicate key (database-specific).
     */
    public function upsert(array $data, array $conflictColumns = [], array $updateColumns = []): int
    {
        $this->ensureTableSet();

        if (empty($data)) {
            throw new DatabaseException('Cannot upsert empty data');
        }

        $columns = array_keys($data);
        foreach ($columns as $col) {
            $this->validateIdentifier($col, 'column');
        }

        $placeholders = [];
        $bindings = [];

        foreach ($data as $key => $value) {
            $placeholders[] = ":{$key}";
            $bindings[":{$key}"] = $value;
        }

        // Determine which columns to update
        if (empty($updateColumns)) {
            $updateColumns = $columns;
        }

        // Build database-specific upsert query
        switch ($this->driver) {
            case 'mysql':
                return $this->mysqlUpsert($columns, $placeholders, $updateColumns, $bindings);

            case 'pgsql':
                if (empty($conflictColumns)) {
                    throw new DatabaseException('PostgreSQL upsert requires conflict columns');
                }
                return $this->pgsqlUpsert($columns, $placeholders, $conflictColumns, $updateColumns, $bindings);

            case 'sqlite':
                if (empty($conflictColumns)) {
                    throw new DatabaseException('SQLite upsert requires conflict columns');
                }
                return $this->sqliteUpsert($columns, $placeholders, $conflictColumns, $updateColumns, $bindings);

            default:
                throw new DatabaseException("Upsert not supported for driver: {$this->driver}");
        }
    }

    /**
     * MySQL-specific upsert.
     */
    private function mysqlUpsert(array $columns, array $placeholders, array $updateColumns, array $bindings): int
    {
        $updates = [];
        foreach ($updateColumns as $col) {
            $updates[] = "$col = VALUES($col)";
        }

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s",
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders),
            implode(', ', $updates)
        );

        return $this->db->insert($sql, $bindings);
    }

    /**
     * PostgreSQL-specific upsert.
     */
    private function pgsqlUpsert(array $columns, array $placeholders, array $conflictColumns, array $updateColumns, array $bindings): int
    {
        $updates = [];
        foreach ($updateColumns as $col) {
            $updates[] = "$col = EXCLUDED.$col";
        }

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s) ON CONFLICT (%s) DO UPDATE SET %s",
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders),
            implode(', ', $conflictColumns),
            implode(', ', $updates)
        );

        return $this->db->insert($sql, $bindings);
    }

    /**
     * SQLite-specific upsert.
     */
    private function sqliteUpsert(array $columns, array $placeholders, array $conflictColumns, array $updateColumns, array $bindings): int
    {
        $updates = [];
        foreach ($updateColumns as $col) {
            $updates[] = "$col = EXCLUDED.$col";
        }

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s) ON CONFLICT (%s) DO UPDATE SET %s",
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders),
            implode(', ', $conflictColumns),
            implode(', ', $updates)
        );

        return $this->db->insert($sql, $bindings);
    }

    /**
     * Update records.
     */
    public function update(array $data): bool
    {
        $this->ensureTableSet();

        if (empty($data)) {
            throw new DatabaseException('Cannot update with empty data');
        }

        $set = [];
        $bindings = [];

        foreach ($data as $key => $value) {
            $this->validateIdentifier($key, 'column');
            $placeholder = ":set_$key";
            $set[] = "$key = $placeholder";
            $bindings[$placeholder] = $value;
        }

        $bindings = array_merge($this->bindings, $bindings);

        $sql = sprintf(
            "UPDATE %s SET %s%s",
            $this->table,
            implode(', ', $set),
            $this->compileConditionsString(true)
        );

        return $this->db->exec($sql, $bindings);
    }

    /**
     * Increment a column value safely.
     */
    public function increment(string $column, int $amount = 1, array $extra = []): bool
    {
        $this->ensureTableSet();
        $this->validateIdentifier($column, 'column');

        if ($amount < 0) {
            throw new InvalidArgumentException("Increment amount must be non-negative");
        }

        $placeholder = $this->generatePlaceholder('inc_amt');
        $bindings = array_merge($this->bindings, [$placeholder => $amount]);

        $set = ["$column = $column + $placeholder"];

        // Add any extra columns to update
        return $this->addAnyExtraColumnsToUpdate($extra, $set, $bindings);
    }

    /**
     * Decrement a column value safely.
     */
    public function decrement(string $column, int $amount = 1, array $extra = []): bool
    {
        $this->ensureTableSet();
        $this->validateIdentifier($column, 'column');

        if ($amount < 0) {
            throw new InvalidArgumentException("Decrement amount must be non-negative");
        }

        $placeholder = $this->generatePlaceholder('dec_amt');
        $bindings = array_merge($this->bindings, [$placeholder => $amount]);

        $set = ["$column = $column - $placeholder"];

        // Add any extra columns to update
        return $this->addAnyExtraColumnsToUpdate($extra, $set, $bindings);
    }

    /**
     * Delete records.
     */
    public function delete(): bool
    {
        $this->ensureTableSet();

        if (empty($this->conditions)) {
            throw new DatabaseException(
                'Attempted to delete without WHERE clause. Use truncate() to delete all records.'
            );
        }

        $sql = sprintf(
            "DELETE FROM %s%s",
            $this->table,
            $this->compileConditionsString(true)
        );

        return $this->db->exec($sql, $this->bindings);
    }

    /**
     * Truncate the table (delete all records).
     */
    public function truncate(): bool
    {
        $this->ensureTableSet();

        if (!empty($this->conditions)) {
            throw new DatabaseException(
                'Cannot truncate with WHERE conditions. Use delete() instead.'
            );
        }

        // SQLite doesn't support TRUNCATE, use DELETE instead
        if ($this->driver === 'sqlite') {
            return $this->db->exec("DELETE FROM $this->table");
        }

        return $this->db->exec("TRUNCATE TABLE $this->table");
    }

    /**
     * Get the SQL query string.
     */
    public function toSql(): string
    {
        $this->ensureTableSet();
        return $this->buildSelect();
    }

    /**
     * Get all bindings (where + having).
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Get all bindings including having bindings.
     */
    private function getAllBindings(): array
    {
        return array_merge($this->bindings, $this->havingBindings);
    }

    /**
     * Ensure table name has been set.
     */
    private function ensureTableSet(): void
    {
        if (empty($this->table)) {
            throw new DatabaseException('Table name must be set before executing query. Call table() first.');
        }
    }

    /**
     * Validate identifier (table/column name) to prevent SQL injection.
     */
    private function validateIdentifier(string $identifier, string $type = 'identifier'): void
    {
        // Allow dots for table.column syntax, wildcards, and underscores
        if (!preg_match('/^[a-zA-Z0-9_.*]+$/', $identifier)) {
            throw new InvalidArgumentException(
                "Invalid $type name: $identifier. Only alphanumeric characters, dots, underscores, and wildcards are allowed."
            );
        }
    }

    /**
     * Validate SQL operator.
     */
    private function validateOperator(string $operator): void
    {
        $validOperators = [
            '=', '!=', '<>', '<', '>', '<=', '>=',
            'LIKE', 'NOT LIKE', 'ILIKE', 'NOT ILIKE',
            'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN',
            'IS', 'IS NOT'
        ];

        $operator = strtoupper($operator);

        if (!in_array($operator, $validOperators, true)) {
            throw new InvalidArgumentException("Invalid operator: $operator");
        }
    }

    private function buildSelect(): string
    {
        $distinct = $this->distinct ? 'DISTINCT ' : '';

        $sql = sprintf(
            "SELECT %s%s FROM %s",
            $distinct,
            implode(', ', $this->select),
            $this->table
        );

        $sql .= $this->buildJoins();
        $sql .= $this->compileConditionsString(true);
        $sql .= $this->buildGroupBy();
        $sql .= $this->buildHaving();
        $sql .= $this->buildOrderBy();
        $sql .= $this->buildLimit();

        return $sql;
    }

    private function buildAggregate(string $function, string $column): string
    {
        $sql = sprintf("SELECT %s(%s) FROM %s", $function, $column, $this->table);
        $sql .= $this->buildJoins();
        $sql .= $this->compileConditionsString(true);
        $sql .= $this->buildGroupBy();
        $sql .= $this->buildHaving();
        return $sql;
    }

    private function buildJoins(): string
    {
        return empty($this->join) ? '' : ' ' . implode(' ', $this->join);
    }

    /**
     * Compiles the logic stack into a SQL string.
     */
    private function compileConditionsString(bool $withWhereKeyword = false): string
    {
        if (empty($this->conditions)) {
            return '';
        }

        $parts = [];
        foreach ($this->conditions as $i => $cond) {
            // The logic operator (AND/OR) goes before the term, except the very first term
            if ($i === 0) {
                $parts[] = $cond['sql'];
            } else {
                $parts[] = "{$cond['type']} {$cond['sql']}";
            }
        }

        $sql = implode(' ', $parts);
        return $withWhereKeyword ? " WHERE $sql" : $sql;
    }

    private function buildGroupBy(): string
    {
        return empty($this->groupBy) ? '' : ' GROUP BY ' . implode(', ', $this->groupBy);
    }

    private function buildHaving(): string
    {
        if (empty($this->having)) {
            return '';
        }

        $parts = [];
        foreach ($this->having as $i => $cond) {
            if ($i === 0) {
                $parts[] = $cond['sql'];
            } else {
                $parts[] = "{$cond['type']} {$cond['sql']}";
            }
        }

        return ' HAVING ' . implode(' ', $parts);
    }

    private function buildOrderBy(): string
    {
        return empty($this->orderBy) ? '' : ' ORDER BY ' . implode(', ', $this->orderBy);
    }

    private function buildLimit(): string
    {
        $sql = '';
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }
        return $sql;
    }

    private function generatePlaceholder(string $column): string
    {
        $base = str_replace(['.', '-', ' ', '*'], '_', $column);
        $counter = 0;
        $placeholder = ":$base";

        while (array_key_exists($placeholder, $this->bindings) ||
            array_key_exists($placeholder, $this->havingBindings)) {
            $placeholder = ":{$base}_" . ++$counter;
        }

        return $placeholder;
    }

    /**
     * @param array $extra
     * @param array $set
     * @param array $bindings
     * @return bool
     */
    private function addAnyExtraColumnsToUpdate(array $extra, array $set, array $bindings): bool
    {
        foreach ($extra as $key => $value) {
            $this->validateIdentifier($key, 'column');
            $extraPlaceholder = ":set_$key";
            $set[] = "$key = $extraPlaceholder";
            $bindings[$extraPlaceholder] = $value;
        }

        $sql = sprintf(
            "UPDATE %s SET %s%s",
            $this->table,
            implode(', ', $set),
            $this->compileConditionsString(true)
        );

        return $this->db->exec($sql, $bindings);
    }
}