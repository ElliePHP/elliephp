<?php

namespace ElliePHP\Framework\Database;

use ElliePHP\Components\Support\Util\Str;
use ElliePHP\Framework\Exceptions\DatabaseException;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;

final class Database
{
    private PDO $pdo;
    private array $config;

    public const string SQLITE = 'sqlite';
    public const string MYSQL = 'mysql';
    public const string MARIADB = 'mariadb';
    public const string POSTGRESQL = 'pgsql';

    public function __construct(array $config)
    {
        $this->config = $this->validateConfig($config);
        $this->connect();
    }

    /**
     * Validate required config keys based on driver.
     */
    private function validateConfig(array $config): array
    {
        $driver = $config['driver'] ?? 'mysql';
        $config['driver'] = strtolower($driver);

        $supported = ['mysql', 'mariadb', 'sqlite', 'pgsql'];

        if (!in_array($config['driver'], $supported, true)) {
            throw new DatabaseException("Unsupported database driver: {$config['driver']}");
        }

        if ($config['driver'] === 'sqlite') {
            if (empty($config['database'])) {
                throw new DatabaseException("SQLite configuration requires 'database' (file path).");
            }
            return $config;
        }

        // MySQL / MariaDB / PostgreSQL required keys
        $required = ['host', 'dbname', 'username', 'password'];
        foreach ($required as $key) {
            if (empty($config[$key])) {
                throw new DatabaseException("Database configuration missing key: $key");
            }
        }

        return $config;
    }

    /**
     * Establish PDO connection depending on driver.
     */
    private function connect(): void
    {
        $driver = $this->config['driver'];

        switch ($driver) {
            case 'sqlite':
                $dsn = "sqlite:{$this->config['database']}";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];
                $username = null;
                $password = null;
                break;

            case 'mariadb':
            case 'mysql':
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=utf8mb4',
                    $this->config['host'],
                    $this->config['dbname']
                );

                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 5,
                ];

                $username = $this->config['username'];
                $password = $this->config['password'];
                break;

            case 'pgsql':
                $dsn = sprintf(
                    'pgsql:host=%s;dbname=%s',
                    $this->config['host'],
                    $this->config['dbname']
                );

                if (!empty($this->config['port'])) {
                    $dsn .= ";port={$this->config['port']}";
                }

                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 5,
                ];

                $username = $this->config['username'];
                $password = $this->config['password'];
                break;

            default:
                throw new DatabaseException("Unsupported driver: $driver");
        }

        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            $this->logError('Database connection failed', $e);
            throw new DatabaseException(
                'Unable to connect to the database. Check credentials or database file.',
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Execute a query safely with optional parameters.
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            if ($this->isConnectionLost($e)) {
                $this->logError('Connection lost, reconnecting...', $e);
                $this->connect();

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt;
            }

            $this->handleQueryError($sql, $params, $e);
        }
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch() ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchColumn(string $sql, array $params = []): mixed
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    public function insert(string $sql, array $params = []): int
    {
        $this->query($sql, $params);

        // SQLite and PostgreSQL use different methods for last insert ID
        if ($this->config['driver'] === 'pgsql') {
            // PostgreSQL requires sequence name, but we can try without it
            // Most tables use the default sequence naming: tablename_id_seq
            try {
                return (int)$this->pdo->lastInsertId();
            } catch (PDOException $e) {
                // If no sequence specified and auto-detection fails, return 0
                $this->logError('Could not retrieve last insert ID for PostgreSQL', $e);
                return 0;
            }
        }

        return (int)$this->pdo->lastInsertId();
    }

    public function exec(string $sql, array $params = []): int
    {
        // If no params, prefer PDO::exec which returns affected rows or false on error
        if (empty($params)) {
            try {
                $result = $this->pdo->exec($sql);
                return $result === false ? 0 : $result;
            } catch (PDOException $e) {
                if ($this->isConnectionLost($e)) {
                    $this->connect();
                    $result = $this->pdo->exec($sql);
                    return $result === false ? 0 : $result;
                }
                $this->handleQueryError($sql, $params, $e);
            }
        }

        // If there are params, use prepared statement and return rowCount()
        return $this->query($sql, $params)->rowCount();
    }


    /**
     * Get the number of affected rows from last query.
     */
    public function affectedRows(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    public function raw(): PDO
    {
        return $this->pdo;
    }

    /**
     * Get the configured driver name.
     */
    public function getDriver(): string
    {
        return $this->config['driver'];
    }

    /**
     * Get the QueryBuilder driver name (maps mariadb -> mysql).
     */
    private function getQueryBuilderDriver(): string
    {
        return $this->config['driver'] === 'mariadb' ? 'mysql' : $this->config['driver'];
    }

    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this, $this->getQueryBuilderDriver())->table($table);
    }

    public function transaction(callable $callback): mixed
    {
        try {
            $this->pdo->beginTransaction();
            $result = $callback($this);
            $this->pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->logError('Transaction failed', $e);
            throw new DatabaseException('Transaction failed: ' . $e->getMessage(), 0, $e);
        }
    }

    private function handleQueryError(string $sql, array $params, PDOException $e): never
    {
        $this->logError('Query failed', $e, [
            'sql' => $sql,
            'params' => $params,
        ]);

        throw new DatabaseException('Database query failed: ' . $e->getMessage(), 0, $e);
    }

    private function isConnectionLost(PDOException $e): bool
    {
        $msg = strtolower($e->getMessage());

        return Str::containsAny($msg, [
            'server has gone away',
            'no connection',
            'lost connection',
            'broken pipe',
            'connection reset',
            'sqlite busy',
            'database is locked'
        ]);
    }

    private function logError(string $message, Throwable $e, array $context = []): void
    {
        report()->error($message, array_merge($context, [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]));
    }

    public function isConnected(): bool
    {
        try {
            if ($this->config['driver'] === 'sqlite') {
                return file_exists($this->config['database']);
            }

            $this->pdo->query('SELECT 1');
            return true;
        } catch (PDOException) {
            return false;
        }
    }
}