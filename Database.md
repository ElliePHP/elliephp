# Database & QueryBuilder Documentation

## Table of Contents
- [Configuration](#configuration)
- [Database Class](#database-class)
- [QueryBuilder](#querybuilder)
- [Querying](#querying)
- [Inserting](#inserting)
- [Updating](#updating)
- [Deleting](#deleting)
- [Aggregates](#aggregates)
- [Transactions](#transactions)

---

## Configuration

### MySQL/MariaDB
```php
$config = [
    'driver' => 'mysql', // or 'mariadb'
    'host' => 'localhost',
    'dbname' => 'mydb',
    'username' => 'root',
    'password' => 'secret'
];
```

### PostgreSQL
```php
$config = [
    'driver' => 'pgsql',
    'host' => 'localhost',
    'port' => 5432, // optional, defaults to 5432
    'dbname' => 'mydb',
    'username' => 'postgres',
    'password' => 'secret'
];
```

### SQLite
```php
$config = [
    'driver' => 'sqlite',
    'database' => '/path/to/database.sqlite'
];
```

---

## Database Class

### Basic Usage

```php
$db = new Database($config);

// Check connection
if ($db->isConnected()) {
    echo "Connected!";
}

// Get raw PDO instance
$pdo = $db->raw();

// Get driver name
$driver = $db->getDriver(); // 'mysql', 'sqlite', 'pgsql', etc.
```

### Raw Queries

```php
// Fetch single row
$user = $db->fetch('SELECT * FROM users WHERE id = :id', [':id' => 1]);

// Fetch all rows
$users = $db->fetchAll('SELECT * FROM users WHERE status = :status', [':status' => 'active']);

// Fetch single column value
$count = $db->fetchColumn('SELECT COUNT(*) FROM users');

// Execute query (returns true if successful)
$db->exec('UPDATE users SET status = :status WHERE id = :id', [
    ':status' => 'inactive',
    ':id' => 1
]);

// Get affected rows count
$affected = $db->affectedRows('DELETE FROM users WHERE status = :status', [':status' => 'deleted']);
```

---

## QueryBuilder

### Getting Started

```php
// Via Database class
$query = $db->table('users');

// Direct instantiation
$query = new QueryBuilder($db, 'mysql'); // or 'sqlite', 'pgsql'
```

---

## Querying

### Basic Selects

```php
// Select all
$users = $db->table('users')->get();

// Select specific columns
$users = $db->table('users')
    ->select('id', 'name', 'email')
    ->get();

// Select with raw expression
$users = $db->table('users')
    ->selectRaw('COUNT(*)', 'total')
    ->get();

// Distinct
$emails = $db->table('users')
    ->distinct()
    ->select('email')
    ->get();

// Get first result
$user = $db->table('users')->where('id', 1)->first();

// Find by ID
$user = $db->table('users')->find(1); // defaults to 'id' column
$user = $db->table('users')->find('john@example.com', 'email');
```

### Where Clauses

```php
// Basic where
$users = $db->table('users')
    ->where('status', 'active')
    ->get();

// Where with operator
$users = $db->table('users')
    ->where('age', '>', 18)
    ->get();

// Multiple conditions (AND)
$users = $db->table('users')
    ->where('status', 'active')
    ->where('age', '>', 18)
    ->get();

// OR conditions
$users = $db->table('users')
    ->where('role', 'admin')
    ->orWhere('role', 'moderator')
    ->get();

// Where NULL
$users = $db->table('users')
    ->whereNull('deleted_at')
    ->get();

// Where NOT NULL
$users = $db->table('users')
    ->whereNotNull('email_verified_at')
    ->get();

// Where IN
$users = $db->table('users')
    ->whereIn('id', [1, 2, 3, 4, 5])
    ->get();

// Where NOT IN
$users = $db->table('users')
    ->whereNotIn('status', ['banned', 'deleted'])
    ->get();

// Where BETWEEN
$users = $db->table('users')
    ->whereBetween('age', 18, 65)
    ->get();

// Where LIKE
$users = $db->table('users')
    ->whereLike('name', '%John%')
    ->get();

// Where comparing columns
$users = $db->table('users')
    ->whereColumn('updated_at', '>', 'created_at')
    ->get();

// Where with date
$users = $db->table('orders')
    ->whereDate('created_at', '=', '2024-01-01')
    ->get();

// Raw where
$users = $db->table('users')
    ->whereRaw('YEAR(created_at) = ?', [2024])
    ->get();
```

### Complex Where Groups

```php
// WHERE (status = 'active' AND role = 'admin') OR (status = 'pending' AND role = 'moderator')
$users = $db->table('users')
    ->whereGroup(function($query) {
        $query->where('status', 'active')
              ->where('role', 'admin');
    })
    ->orWhere(function($query) {
        $query->where('status', 'pending')
              ->where('role', 'moderator');
    })
    ->get();
```

### Joins

```php
// Inner join
$users = $db->table('users')
    ->join('profiles', 'users.id', '=', 'profiles.user_id')
    ->get();

// Left join
$users = $db->table('users')
    ->leftJoin('orders', 'users.id', '=', 'orders.user_id')
    ->get();

// Right join
$users = $db->table('users')
    ->rightJoin('posts', 'users.id', '=', 'posts.author_id')
    ->get();

// Multiple joins
$data = $db->table('users')
    ->join('profiles', 'users.id', '=', 'profiles.user_id')
    ->leftJoin('orders', 'users.id', '=', 'orders.user_id')
    ->select('users.*', 'profiles.bio', 'orders.total')
    ->get();
```

### Ordering & Grouping

```php
// Order by
$users = $db->table('users')
    ->orderBy('created_at', 'DESC')
    ->get();

// Multiple order by
$users = $db->table('users')
    ->orderBy('status', 'ASC')
    ->orderBy('name', 'ASC')
    ->get();

// Order by raw
$users = $db->table('users')
    ->orderByRaw('RAND()')
    ->get();

// Group by
$stats = $db->table('orders')
    ->select('user_id', 'COUNT(*) as order_count')
    ->groupBy('user_id')
    ->get();

// Group by with HAVING
$users = $db->table('orders')
    ->select('user_id', 'SUM(total) as total_spent')
    ->groupBy('user_id')
    ->having('total_spent', '>', 1000)
    ->get();

// Having with OR
$users = $db->table('orders')
    ->select('user_id', 'COUNT(*) as order_count')
    ->groupBy('user_id')
    ->having('order_count', '>', 10)
    ->orHaving('order_count', '<', 2)
    ->get();

// Raw having
$users = $db->table('orders')
    ->select('user_id', 'SUM(total) as total')
    ->groupBy('user_id')
    ->havingRaw('SUM(total) > ?', [5000])
    ->get();
```

### Limiting & Pagination

```php
// Limit
$users = $db->table('users')
    ->limit(10)
    ->get();

// Limit with offset
$users = $db->table('users')
    ->limit(10)
    ->offset(20)
    ->get();

// Pagination
$result = $db->table('users')
    ->orderBy('created_at', 'DESC')
    ->paginate(15, 1); // 15 per page, page 1

// Returns:
// [
//     'data' => [...],           // Array of results
//     'total' => 150,            // Total records
//     'per_page' => 15,
//     'current_page' => 1,
//     'last_page' => 10,
//     'from' => 1,               // First item number
//     'to' => 15                 // Last item number
// ]
```

---

## Inserting

### Basic Insert

```php
// Insert single record
$id = $db->table('users')->insert([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'status' => 'active'
]);
// Returns: last insert ID
```

### Batch Insert

```php
// Insert multiple records
$count = $db->table('users')->insertMany([
    ['name' => 'Alice', 'email' => 'alice@example.com'],
    ['name' => 'Bob', 'email' => 'bob@example.com'],
    ['name' => 'Charlie', 'email' => 'charlie@example.com']
]);
// Returns: number of records inserted
```

### Upsert (Insert or Update)

```php
// MySQL - uses ON DUPLICATE KEY UPDATE
$id = $db->table('users')->upsert(
    ['email' => 'john@example.com', 'name' => 'John Doe', 'status' => 'active'],
    [],                    // conflict columns (not needed for MySQL)
    ['name', 'status']     // columns to update on conflict
);

// PostgreSQL/SQLite - requires conflict columns
$id = $db->table('users')->upsert(
    ['email' => 'john@example.com', 'name' => 'John Doe', 'status' => 'active'],
    ['email'],             // conflict column (UNIQUE constraint)
    ['name', 'status']     // columns to update on conflict
);

// If updateColumns is empty, all columns are updated
$id = $db->table('users')->upsert(
    ['email' => 'john@example.com', 'name' => 'John Doe'],
    ['email']
);
```

---

## Updating

### Basic Update

```php
// Update with where
$success = $db->table('users')
    ->where('id', 1)
    ->update(['status' => 'inactive']);
// Returns: true if successful

// Update multiple columns
$success = $db->table('users')
    ->where('status', 'pending')
    ->update([
        'status' => 'active',
        'verified_at' => date('Y-m-d H:i:s')
    ]);
```

### Increment & Decrement

```php
// Increment
$db->table('users')
    ->where('id', 1)
    ->increment('login_count');

// Increment by amount
$db->table('users')
    ->where('id', 1)
    ->increment('credits', 50);

// Increment with extra columns
$db->table('users')
    ->where('id', 1)
    ->increment('login_count', 1, [
        'last_login' => date('Y-m-d H:i:s')
    ]);

// Decrement
$db->table('users')
    ->where('id', 1)
    ->decrement('credits', 10);

// Decrement with extra columns
$db->table('products')
    ->where('id', 5)
    ->decrement('stock', 1, [
        'updated_at' => date('Y-m-d H:i:s')
    ]);
```

---

## Deleting

### Basic Delete

```php
// Delete with where (REQUIRED - prevents accidental full table deletion)
$success = $db->table('users')
    ->where('status', 'deleted')
    ->delete();

// Delete will throw error if no WHERE clause provided:
// RuntimeException: Attempted to delete without WHERE clause. Use truncate() to delete all records.
```

### Truncate

```php
// Delete all records (FAST but irreversible)
$success = $db->table('users')->truncate();

// Truncate will throw error if WHERE conditions exist:
// RuntimeException: Cannot truncate with WHERE conditions. Use delete() instead.

// Note: SQLite uses DELETE instead of TRUNCATE (no TRUNCATE support)
```

---

## Aggregates

### Count

```php
// Count all
$total = $db->table('users')->count();

// Count with where
$active = $db->table('users')
    ->where('status', 'active')
    ->count();

// Count specific column
$verified = $db->table('users')->count('email_verified_at');
```

### Min, Max, Avg, Sum

```php
// Maximum value
$maxPrice = $db->table('products')->max('price');

// Minimum value
$minPrice = $db->table('products')->min('price');

// Average
$avgPrice = $db->table('products')->avg('price');

// Sum
$totalRevenue = $db->table('orders')
    ->where('status', 'completed')
    ->sum('total');
```

### Exists

```php
// Check if records exist
$hasUsers = $db->table('users')->exists(); // returns bool

$hasActiveAdmin = $db->table('users')
    ->where('role', 'admin')
    ->where('status', 'active')
    ->exists();
```

---

## Transactions

### Basic Transaction

```php
$result = $db->transaction(function($db) {
    $userId = $db->table('users')->insert([
        'name' => 'John',
        'email' => 'john@example.com'
    ]);
    
    $db->table('profiles')->insert([
        'user_id' => $userId,
        'bio' => 'Hello world'
    ]);
    
    return $userId;
});

// If any exception occurs, transaction is automatically rolled back
```

### Manual Transaction Control

```php
$pdo = $db->raw();

try {
    $pdo->beginTransaction();
    
    // Your queries here
    $db->table('users')->insert([...]);
    $db->table('orders')->insert([...]);
    
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
}
```

---

## Advanced Features

### Get SQL Query

```php
// Get the SQL without executing
$sql = $db->table('users')
    ->where('status', 'active')
    ->orderBy('created_at', 'DESC')
    ->toSql();
// Returns: "SELECT * FROM users WHERE status = :status ORDER BY created_at DESC"

// Get bindings
$bindings = $db->table('users')
    ->where('status', 'active')
    ->getBindings();
// Returns: [':status' => 'active']
```

### Driver Detection

```php
$driver = $db->getDriver();

if ($driver === 'sqlite') {
    // SQLite-specific logic
} elseif ($driver === 'pgsql') {
    // PostgreSQL-specific logic
}
```

### Method Chaining

```php
$users = $db->table('users')
    ->select('id', 'name', 'email')
    ->where('status', 'active')
    ->whereNotNull('email_verified_at')
    ->whereIn('role', ['admin', 'moderator'])
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get();
```

---

## Security Features

### SQL Injection Prevention
- All values are **automatically parameterized** using PDO prepared statements
- Column/table names are **validated** to prevent injection
- No raw user input is directly interpolated into SQL

### Safe Practices

```php
// ✅ SAFE - Uses parameterized query
$db->table('users')->where('email', $userInput)->get();

// ✅ SAFE - Validated identifiers
$db->table('users')->select('id', 'name')->get();

// ⚠️ USE WITH CAUTION - Raw SQL (only for trusted input)
$db->table('users')->whereRaw('created_at > NOW() - INTERVAL 7 DAY')->get();

// ⚠️ USE WITH CAUTION - But still parameterized
$db->table('users')->whereRaw('age > ?', [$minAge])->get();
```

---

## Error Handling

### Validation Errors

```php
try {
    // Invalid table name
    $db->table('users; DROP TABLE users--')->get();
} catch (InvalidArgumentException $e) {
    // "Invalid table name: users; DROP TABLE users--"
}

try {
    // Missing table
    $db->where('id', 1)->get();
} catch (RuntimeException $e) {
    // "Table name must be set before executing query. Call table() first."
}

try {
    // Delete without WHERE
    $db->table('users')->delete();
} catch (RuntimeException $e) {
    // "Attempted to delete without WHERE clause. Use truncate() to delete all records."
}
```

### Connection Errors

```php
try {
    $db = new Database($config);
} catch (RuntimeException $e) {
    // "Unable to connect to the database. Check credentials or database file."
}

// Auto-reconnect on connection loss
// The Database class automatically reconnects if connection is lost during query
```

---

## Performance Tips

1. **Use select() to limit columns** - Only fetch what you need
   ```php
   $db->table('users')->select('id', 'name')->get(); // Better than SELECT *
   ```

2. **Use exists() instead of count()** - When you just need to check existence
   ```php
   if ($db->table('users')->where('email', $email)->exists()) { ... }
   ```

3. **Use pagination for large datasets**
   ```php
   $result = $db->table('users')->paginate(50, $page);
   ```

4. **Use transactions for bulk operations**
   ```php
   $db->transaction(function($db) {
       // Multiple inserts/updates
   });
   ```

5. **Index frequently queried columns** - Add database indexes on WHERE/JOIN columns

6. **Use insertMany() for batch inserts** - Faster than multiple single inserts
   ```php
   $db->table('logs')->insertMany($records); // Single query
   ```

---

## Database-Specific Notes

### MySQL/MariaDB
- ✅ Full support for all features
- ✅ `ON DUPLICATE KEY UPDATE` for upsert
- ✅ Auto-increment primary keys

### PostgreSQL
- ✅ Full support for all features
- ✅ `ON CONFLICT DO UPDATE` for upsert
- ⚠️ Requires explicit conflict columns for upsert
- ⚠️ May need sequence name for `lastInsertId()`

### SQLite
- ✅ Most features supported
- ✅ `ON CONFLICT DO UPDATE` for upsert
- ⚠️ Uses `DELETE` instead of `TRUNCATE`
- ⚠️ Limited JOIN types (no FULL OUTER JOIN)
- ⚠️ File-based, may lock during writes

---

## Quick Reference

| Method | Returns | Description |
|--------|---------|-------------|
| `table($name)` | QueryBuilder | Start query on table |
| `select(...$columns)` | QueryBuilder | Select columns |
| `where($col, $op, $val)` | QueryBuilder | Add WHERE condition |
| `get()` | array | Execute and fetch all |
| `first()` | ?array | Fetch first result |
| `find($id)` | ?array | Find by primary key |
| `count()` | int | Count records |
| `insert($data)` | int | Insert and return ID |
| `update($data)` | bool | Update records |
| `delete()` | bool | Delete records |
| `paginate($perPage, $page)` | array | Paginated results |
| `transaction($callback)` | mixed | Execute in transaction |

---

## Examples

### User Registration
```php
$userId = $db->transaction(function($db) use ($userData) {
    $userId = $db->table('users')->insert([
        'name' => $userData['name'],
        'email' => $userData['email'],
        'password' => password_hash($userData['password'], PASSWORD_DEFAULT),
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    $db->table('profiles')->insert([
        'user_id' => $userId,
        'bio' => '',
        'avatar' => 'default.png'
    ]);
    
    return $userId;
});
```

### Blog Post Query
```php
$posts = $db->table('posts')
    ->select('posts.*', 'users.name as author_name', 'COUNT(comments.id) as comment_count')
    ->join('users', 'posts.author_id', '=', 'users.id')
    ->leftJoin('comments', 'posts.id', '=', 'comments.post_id')
    ->where('posts.status', 'published')
    ->whereNull('posts.deleted_at')
    ->groupBy('posts.id')
    ->orderBy('posts.created_at', 'DESC')
    ->paginate(10, $page);
```

### Analytics Query
```php
$stats = $db->table('orders')
    ->select('DATE(created_at) as date', 'COUNT(*) as order_count', 'SUM(total) as revenue')
    ->where('status', 'completed')
    ->whereBetween('created_at', $startDate, $endDate)
    ->groupBy('date')
    ->orderBy('date', 'DESC')
    ->get();
```