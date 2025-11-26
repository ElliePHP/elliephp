# Query Builder Documentation

A comprehensive, Laravel-inspired query builder for PHP with fluent interface, automatic parameter binding, and powerful features for building complex SQL queries safely.

## Table of Contents

- [Installation & Setup](#installation--setup)
- [Basic Usage](#basic-usage)
- [SELECT Queries](#select-queries)
- [WHERE Clauses](#where-clauses)
- [JOIN Operations](#join-operations)
- [Ordering Results](#ordering-results)
- [Grouping & Aggregates](#grouping--aggregates)
- [Pagination](#pagination)
- [INSERT Operations](#insert-operations)
- [UPDATE Operations](#update-operations)
- [DELETE Operations](#delete-operations)
- [Transactions](#transactions)
- [Advanced Features](#advanced-features)
- [Performance Tips](#performance-tips)
- [Security Best Practices](#security-best-practices)

---

## Installation & Setup

### Initialize Database Connection

```php
use ElliePHP\Framework\Database\Database;

$db = new Database([
    'host' => 'localhost',
    'dbname' => 'your_database',
    'username' => 'your_username',
    'password' => 'your_password'
]);
```

### Create Query Builder Instance

```php
// Method 1: Using the table() method
$query = $db->table('users');

// Method 2: Direct instantiation (less common)
$query = new QueryBuilder($db);
$query->table('users');
```

---

## Basic Usage

### Simple SELECT

```php
// Get all records
$users = $db->table('users')->get();

// Get first record
$user = $db->table('users')->first();

// Find by ID
$user = $db->table('users')->find(5);
```

### Method Chaining

All query methods return `$this`, allowing elegant chaining:

```php
$activeAdmins = $db->table('users')
    ->where('status', 'active')
    ->where('role', 'admin')
    ->orderBy('name')
    ->limit(10)
    ->get();
```

---

## SELECT Queries

### Selecting Columns

```php
// Select all columns (default)
$users = $db->table('users')->get();

// Select specific columns
$users = $db->table('users')
    ->select('id', 'name', 'email')
    ->get();

// Select with alias using raw expressions
$stats = $db->table('orders')
    ->selectRaw('COUNT(*)', 'total_orders')
    ->selectRaw('SUM(amount)', 'total_revenue')
    ->first();
```

### DISTINCT Results

```php
// Get unique genres
$genres = $db->table('stations')
    ->select('genre')
    ->distinct()
    ->get();
```

### Mixed SELECT

```php
$report = $db->table('products')
    ->select('category_id', 'category_name')
    ->selectRaw('COUNT(*)', 'product_count')
    ->selectRaw('AVG(price)', 'avg_price')
    ->groupBy('category_id', 'category_name')
    ->get();
```

---

## WHERE Clauses

### Basic WHERE

```php
// Equal comparison (default operator is '=')
$user = $db->table('users')
    ->where('email', 'john@example.com')
    ->first();

// With explicit operator
$products = $db->table('products')
    ->where('price', '>', 100)
    ->get();

// Multiple conditions (AND)
$results = $db->table('orders')
    ->where('status', 'completed')
    ->where('amount', '>=', 50)
    ->where('user_id', 123)
    ->get();
```

### OR WHERE

```php
// Simple OR condition
$users = $db->table('users')
    ->where('role', 'admin')
    ->orWhere('role', 'moderator')
    ->get();

// Complex OR logic
$products = $db->table('products')
    ->where('category', 'electronics')
    ->where('in_stock', true)
    ->orWhere('allow_backorder', true)
    ->get();
```

### WHERE NULL / NOT NULL

```php
// Check for NULL values
$incomplete = $db->table('profiles')
    ->whereNull('bio')
    ->get();

// Check for NOT NULL values
$complete = $db->table('profiles')
    ->whereNotNull('bio')
    ->whereNotNull('avatar')
    ->get();
```

### WHERE IN / NOT IN

```php
// IN clause
$users = $db->table('users')
    ->whereIn('id', [1, 2, 3, 4, 5])
    ->get();

// NOT IN clause
$activeUsers = $db->table('users')
    ->whereNotIn('status', ['banned', 'suspended', 'deleted'])
    ->get();
```

### WHERE BETWEEN

```php
// Price range
$affordable = $db->table('products')
    ->whereBetween('price', 50, 200)
    ->get();

// Date range
$recentOrders = $db->table('orders')
    ->whereBetween('created_at', '2024-01-01', '2024-12-31')
    ->get();
```

### WHERE LIKE / NOT LIKE

```php
// LIKE pattern matching
$users = $db->table('users')
    ->whereLike('name', 'John%')
    ->get();

// Search in multiple fields
$products = $db->table('products')
    ->whereLike('name', '%headphones%')
    ->get();

// NOT LIKE
$products = $db->table('products')
    ->whereNotLike('name', '%refurbished%')
    ->get();
```

### WHERE Column Comparison

```php
// Compare two columns
$highPerformers = $db->table('stations')
    ->whereColumn('current_listeners', '>', 'average_listeners')
    ->get();

$outdated = $db->table('products')
    ->whereColumn('updated_at', '<', 'created_at')
    ->get();
```

### WHERE Date Filters

```php
// Filter by date
$today = $db->table('logs')
    ->whereDate('created_at', '=', date('Y-m-d'))
    ->get();

// Filter by year
$thisYear = $db->table('orders')
    ->whereYear('created_at', '=', 2024)
    ->get();
```

### Complex WHERE Groups

```php
// Grouped conditions: (active AND (admin OR moderator))
$users = $db->table('users')
    ->where('active', true)
    ->whereGroup(function($query) {
        $query->where('role', 'admin')
              ->orWhere('role', 'moderator');
    })
    ->get();
```

### Raw WHERE (Use with Caution)

```php
// Full-text search
$results = $db->table('articles')
    ->whereRaw('MATCH(title, content) AGAINST(? IN BOOLEAN MODE)', ['php database'])
    ->get();

// Complex date logic
$users = $db->table('users')
    ->whereRaw('DATEDIFF(NOW(), last_login) > ?', [30])
    ->get();
```

---

## JOIN Operations

### INNER JOIN

```php
$results = $db->table('orders')
    ->select('orders.*', 'users.name', 'users.email')
    ->join('users', 'orders.user_id', '=', 'users.id')
    ->where('orders.status', 'completed')
    ->get();
```

### LEFT JOIN

```php
// Get all users with their profiles (even if profile doesn't exist)
$users = $db->table('users')
    ->select('users.*', 'profiles.bio', 'profiles.avatar')
    ->leftJoin('profiles', 'users.id', '=', 'profiles.user_id')
    ->get();
```

### RIGHT JOIN

```php
// Find profiles without users (orphaned records)
$orphans = $db->table('profiles')
    ->select('profiles.*')
    ->rightJoin('users', 'profiles.user_id', '=', 'users.id')
    ->whereNull('profiles.id')
    ->get();
```

### CROSS JOIN

```php
// Generate all combinations
$combinations = $db->table('colors')
    ->crossJoin('sizes')
    ->get();
```

### Multiple JOINS

```php
$detailed = $db->table('orders')
    ->select(
        'orders.*',
        'users.name as customer_name',
        'products.name as product_name',
        'categories.name as category'
    )
    ->join('users', 'orders.user_id', '=', 'users.id')
    ->join('products', 'orders.product_id', '=', 'products.id')
    ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
    ->where('orders.status', 'shipped')
    ->get();
```

---

## Ordering Results

### Basic Ordering

```php
// Order by single column
$users = $db->table('users')
    ->orderBy('name', 'ASC')
    ->get();

// Order descending
$recent = $db->table('posts')
    ->orderBy('created_at', 'DESC')
    ->get();
```

### Multiple ORDER BY

```php
$stations = $db->table('stations')
    ->orderBy('genre', 'ASC')
    ->orderBy('listeners', 'DESC')
    ->orderBy('name', 'ASC')
    ->get();
```

### Random Ordering

```php
// Get 5 random stations
$random = $db->table('stations')
    ->orderByRandom()
    ->limit(5)
    ->get();
```

### Custom Ordering

```php
// Order by custom field values
$tasks = $db->table('tasks')
    ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
    ->get();

// Order by computed value
$products = $db->table('products')
    ->orderByRaw('(price * discount_percent / 100) DESC')
    ->get();
```

---

## Grouping & Aggregates

### GROUP BY

```php
// Count users by role
$byRole = $db->table('users')
    ->select('role')
    ->selectRaw('COUNT(*)', 'count')
    ->groupBy('role')
    ->get();

// Multiple grouping
$sales = $db->table('orders')
    ->select('year', 'quarter', 'region')
    ->selectRaw('SUM(amount)', 'total')
    ->groupBy('year', 'quarter', 'region')
    ->get();
```

### HAVING Clause

```php
// Groups with more than 10 items
$popular = $db->table('products')
    ->select('category_id')
    ->selectRaw('COUNT(*)', 'product_count')
    ->groupBy('category_id')
    ->having('product_count', '>', 10)
    ->get();

// Complex HAVING with raw SQL
$valuable = $db->table('orders')
    ->select('user_id')
    ->selectRaw('COUNT(*)', 'order_count')
    ->selectRaw('SUM(amount)', 'total_spent')
    ->groupBy('user_id')
    ->havingRaw('SUM(amount) > ?', [1000])
    ->get();
```

### Aggregate Functions

```php
// COUNT
$totalUsers = $db->table('users')->count();
$activeUsers = $db->table('users')->where('active', true)->count();

// SUM
$totalRevenue = $db->table('orders')
    ->where('status', 'completed')
    ->sum('amount');

// AVERAGE
$avgRating = $db->table('reviews')
    ->where('product_id', 123)
    ->avg('rating');

// MIN / MAX
$oldestUser = $db->table('users')->min('created_at');
$newestUser = $db->table('users')->max('created_at');
$cheapest = $db->table('products')->min('price');
$expensive = $db->table('products')->max('price');
```

### Exists / Doesn't Exist

```php
// Check if records exist
if ($db->table('users')->where('email', 'john@example.com')->exists()) {
    echo "Email already taken!";
}

// Check if no records exist
if ($db->table('notifications')->where('user_id', 5)->doesntExist()) {
    echo "No notifications found";
}
```

---

## Pagination

### Basic Pagination

```php
// Get page 1 with 20 items per page
$result = $db->table('products')
    ->where('active', true)
    ->orderBy('name')
    ->paginate(20, 1);

/*
Returns:
[
    'data' => [...],           // Array of records
    'total' => 150,            // Total records
    'per_page' => 20,          // Items per page
    'current_page' => 1,       // Current page
    'last_page' => 8,          // Last page number
    'from' => 1,               // First item number
    'to' => 20                 // Last item number
]
*/
```

### Pagination with Filters

```php
$page = $_GET['page'] ?? 1;
$perPage = 15;

$products = $db->table('products')
    ->select('products.*', 'categories.name as category')
    ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
    ->whereLike('products.name', '%' . $_GET['search'] . '%')
    ->whereBetween('products.price', $_GET['min_price'], $_GET['max_price'])
    ->orderBy('products.rating', 'DESC')
    ->paginate($perPage, $page);
```

### Simple Limit/Offset

```php
// Laravel-style aliases
$recent = $db->table('posts')
    ->take(10)      // LIMIT 10
    ->skip(20)      // OFFSET 20
    ->get();

// Traditional style
$recent = $db->table('posts')
    ->limit(10)
    ->offset(20)
    ->get();
```

---

## INSERT Operations

### Single Insert

```php
// Insert one record
$userId = $db->table('users')->insert([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => password_hash('secret', PASSWORD_DEFAULT),
    'created_at' => date('Y-m-d H:i:s')
]);

echo "New user ID: $userId";
```

### Bulk Insert

```php
// Insert multiple records at once
$success = $db->table('stations')->insertMany([
    [
        'name' => 'Rock FM',
        'frequency' => 95.5,
        'genre_id' => 1,
        'status' => 'active'
    ],
    [
        'name' => 'Jazz FM',
        'frequency' => 102.1,
        'genre_id' => 2,
        'status' => 'active'
    ],
    [
        'name' => 'Classic FM',
        'frequency' => 89.3,
        'genre_id' => 3,
        'status' => 'pending'
    ]
]);
```

### Upsert (Insert or Update)

```php
// Insert or update on duplicate key
$id = $db->table('settings')->upsert(
    [
        'key' => 'site_theme',
        'value' => 'dark',
        'updated_at' => date('Y-m-d H:i:s')
    ],
    ['value', 'updated_at']  // Columns to update on duplicate
);

// Update only specific columns
$id = $db->table('user_stats')->upsert(
    [
        'user_id' => 5,
        'login_count' => 1,
        'last_login' => date('Y-m-d H:i:s')
    ],
    ['login_count', 'last_login']
);
```

---

## UPDATE Operations

### Basic Update

```php
// Update with WHERE
$updated = $db->table('users')
    ->where('id', 5)
    ->update([
        'name' => 'Jane Doe',
        'updated_at' => date('Y-m-d H:i:s')
    ]);

if ($updated) {
    echo "User updated successfully";
}
```

### Conditional Update

```php
// Update multiple records
$count = $db->table('products')
    ->where('category_id', 3)
    ->where('stock', '<', 10)
    ->update([
        'status' => 'low_stock',
        'alert_sent' => true
    ]);

echo "$count products marked as low stock";
```

### Increment / Decrement

```php
// Increment a counter
$db->table('posts')
    ->where('id', 123)
    ->increment('views');

// Increment by specific amount
$db->table('users')
    ->where('id', 5)
    ->increment('points', 50);

// Decrement stock
$db->table('products')
    ->where('id', 10)
    ->decrement('stock', 2);

// Decrement with condition
$db->table('products')
    ->where('id', 10)
    ->where('stock', '>=', 2)
    ->decrement('stock', 2);
```

---

## DELETE Operations

### Basic Delete

```php
// Delete by ID
$deleted = $db->table('users')
    ->where('id', 5)
    ->delete();

if ($deleted) {
    echo "User deleted";
}
```

### Conditional Delete

```php
// Delete multiple records
$count = $db->table('logs')
    ->whereDate('created_at', '<', date('Y-m-d', strtotime('-30 days')))
    ->delete();

echo "$count old logs deleted";

// Delete inactive users
$db->table('users')
    ->where('active', false)
    ->where('last_login', '<', date('Y-m-d', strtotime('-1 year')))
    ->delete();
```

### Truncate Table

```php
// Clear all data (faster than DELETE)
$db->table('temporary_cache')->truncate();

// WARNING: This cannot be rolled back!
```

---

## Transactions

### Basic Transaction

```php
try {
    $db->transaction(function($db) {
        // Create order
        $orderId = $db->table('orders')->insert([
            'user_id' => 5,
            'total' => 150.00,
            'status' => 'pending'
        ]);
        
        // Add order items
        $db->table('order_items')->insert([
            'order_id' => $orderId,
            'product_id' => 10,
            'quantity' => 2,
            'price' => 75.00
        ]);
        
        // Update product stock
        $db->table('products')
            ->where('id', 10)
            ->decrement('stock', 2);
    });
    
    echo "Order created successfully!";
} catch (Exception $e) {
    echo "Order failed: " . $e->getMessage();
}
```

### Complex Transaction

```php
$db->transaction(function($db) {
    // Transfer money between accounts
    $amount = 100.00;
    
    // Deduct from sender
    $db->table('accounts')
        ->where('user_id', 1)
        ->where('balance', '>=', $amount)
        ->decrement('balance', $amount);
    
    // Add to receiver
    $db->table('accounts')
        ->where('user_id', 2)
        ->increment('balance', $amount);
    
    // Log transaction
    $db->table('transactions')->insert([
        'from_user_id' => 1,
        'to_user_id' => 2,
        'amount' => $amount,
        'type' => 'transfer',
        'created_at' => date('Y-m-d H:i:s')
    ]);
});
```

---

## Advanced Features

### Chunking Large Datasets

```php
// Process 1000 records at a time
$db->table('users')
    ->where('active', true)
    ->chunk(1000, function($users, $page) {
        foreach ($users as $user) {
            // Send email
            sendNewsletter($user['email']);
        }
        
        echo "Processed page $page\n";
        
        // Return false to stop processing
        // return false;
    });
```

### Get Single Value

```php
// Get just the email
$email = $db->table('users')
    ->where('id', 5)
    ->value('email');

// Get first matching name
$name = $db->table('users')
    ->where('role', 'admin')
    ->orderBy('created_at')
    ->value('name');
```

### Pluck Column Values

```php
// Get array of IDs
$ids = $db->table('products')
    ->where('category_id', 3)
    ->pluck('id');
// Returns: [1, 5, 8, 12, 15]

// Get array of names
$names = $db->table('users')
    ->where('active', true)
    ->orderBy('name')
    ->pluck('name');
// Returns: ['Alice', 'Bob', 'Charlie']
```

### Query Cloning

```php
// Create base query
$baseQuery = $db->table('products')
    ->where('active', true)
    ->where('in_stock', true);

// Clone for different categories
$electronics = $baseQuery->clone()
    ->where('category', 'electronics')
    ->get();

$clothing = $baseQuery->clone()
    ->where('category', 'clothing')
    ->get();

// Original query unchanged
$all = $baseQuery->get();
```

### Debug Queries

```php
// Build query without executing
$query = $db->table('users')
    ->where('active', true)
    ->where('role', 'admin')
    ->orderBy('name');

// Get SQL
$sql = $query->toSql();
echo $sql;
// Output: SELECT * FROM users WHERE active = :active AND role = :role ORDER BY name ASC

// Get bindings
$bindings = $query->getBindings();
print_r($bindings);
// Output: [':active' => true, ':role' => 'admin']

// Now execute
$results = $query->get();
```

---

## Real-World Use Cases

### User Authentication

```php
function authenticateUser($email, $password) {
    global $db;
    
    $user = $db->table('users')
        ->where('email', $email)
        ->where('active', true)
        ->first();
    
    if ($user && password_verify($password, $user['password'])) {
        // Update last login
        $db->table('users')
            ->where('id', $user['id'])
            ->update([
                'last_login' => date('Y-m-d H:i:s'),
                'login_count' => $db->raw()->quote('login_count + 1')
            ]);
        
        return $user;
    }
    
    return null;
}
```

### E-commerce Product Search

```php
function searchProducts($filters) {
    global $db;
    
    $query = $db->table('products')
        ->select('products.*', 'categories.name as category')
        ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
        ->where('products.active', true);
    
    // Search term
    if (!empty($filters['search'])) {
        $query->whereLike('products.name', '%' . $filters['search'] . '%');
    }
    
    // Price range
    if (!empty($filters['min_price']) && !empty($filters['max_price'])) {
        $query->whereBetween('products.price', $filters['min_price'], $filters['max_price']);
    }
    
    // Category filter
    if (!empty($filters['categories'])) {
        $query->whereIn('products.category_id', $filters['categories']);
    }
    
    // In stock only
    if (!empty($filters['in_stock'])) {
        $query->where('products.stock', '>', 0);
    }
    
    // Sorting
    $sortBy = $filters['sort'] ?? 'name';
    $sortDir = $filters['direction'] ?? 'ASC';
    $query->orderBy("products.$sortBy", $sortDir);
    
    // Pagination
    $page = $filters['page'] ?? 1;
    return $query->paginate(24, $page);
}
```

### Analytics Dashboard

```php
function getDashboardStats($startDate, $endDate) {
    global $db;
    
    // Total revenue
    $revenue = $db->table('orders')
        ->whereBetween('created_at', $startDate, $endDate)
        ->where('status', 'completed')
        ->sum('total');
    
    // Orders by status
    $ordersByStatus = $db->table('orders')
        ->select('status')
        ->selectRaw('COUNT(*)', 'count')
        ->selectRaw('SUM(total)', 'revenue')
        ->whereBetween('created_at', $startDate, $endDate)
        ->groupBy('status')
        ->get();
    
    // Top products
    $topProducts = $db->table('order_items')
        ->select('products.name', 'products.id')
        ->selectRaw('SUM(order_items.quantity)', 'total_sold')
        ->selectRaw('SUM(order_items.price * order_items.quantity)', 'revenue')
        ->join('products', 'order_items.product_id', '=', 'products.id')
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->whereBetween('orders.created_at', $startDate, $endDate)
        ->where('orders.status', 'completed')
        ->groupBy('products.id', 'products.name')
        ->orderBy('total_sold', 'DESC')
        ->limit(10)
        ->get();
    
    // New customers
    $newCustomers = $db->table('users')
        ->whereBetween('created_at', $startDate, $endDate)
        ->count();
    
    return [
        'revenue' => $revenue,
        'orders_by_status' => $ordersByStatus,
        'top_products' => $topProducts,
        'new_customers' => $newCustomers
    ];
}
```

### Social Media Feed

```php
function getUserFeed($userId, $page = 1) {
    global $db;
    
    return $db->table('posts')
        ->select(
            'posts.*',
            'users.name as author_name',
            'users.avatar as author_avatar'
        )
        ->selectRaw('COUNT(DISTINCT likes.id)', 'like_count')
        ->selectRaw('COUNT(DISTINCT comments.id)', 'comment_count')
        ->join('users', 'posts.user_id', '=', 'users.id')
        ->leftJoin('likes', 'posts.id', '=', 'likes.post_id')
        ->leftJoin('comments', 'posts.id', '=', 'comments.post_id')
        ->whereGroup(function($query) use ($userId) {
            // User's own posts
            $query->where('posts.user_id', $userId);
            // Posts from followed users
            $query->orWhereRaw(
                'posts.user_id IN (SELECT followed_id FROM follows WHERE follower_id = ?)',
                [$userId]
            );
        })
        ->groupBy('posts.id', 'users.name', 'users.avatar')
        ->orderBy('posts.created_at', 'DESC')
        ->paginate(20, $page);
}
```

### Report Generation

```php
function generateSalesReport($year, $month = null) {
    global $db;
    
    $query = $db->table('orders')
        ->select('products.name as product')
        ->selectRaw('COUNT(order_items.id)', 'units_sold')
        ->selectRaw('SUM(order_items.price * order_items.quantity)', 'revenue')
        ->selectRaw('AVG(order_items.price)', 'avg_price')
        ->join('order_items', 'orders.id', '=', 'order_items.order_id')
        ->join('products', 'order_items.product_id', '=', 'products.id')
        ->where('orders.status', 'completed')
        ->whereYear('orders.created_at', '=', $year);
    
    if ($month) {
        $query->whereRaw('MONTH(orders.created_at) = ?', [$month]);
    }
    
    return $query
        ->groupBy('products.id', 'products.name')
        ->having('units_sold', '>', 0)
        ->orderBy('revenue', 'DESC')
        ->get();
}
```

---

## Performance Tips

### 1. Use Indexes

```sql
-- Create indexes for commonly queried columns
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_products_category ON products(category_id);
```

### 2. Select Only Needed Columns

```php
// Bad: Fetches all columns
$users = $db->table('users')->get();

// Good: Fetch only what you need
$users = $db->table('users')
    ->select('id', 'name', 'email')
    ->get();
```

### 3. Use Chunking for Large Datasets

```php
// Bad: Loads everything into memory
$users = $db->table('users')->get();
foreach ($users as $user) {
    processUser($user);
}

// Good: Process in chunks
$db->table('users')->chunk(1000, function($users) {
    foreach ($users as $user) {
        processUser($user);
    }
});
```

### 4. Eager Load Relationships

```php
// Bad: N+1 queries
$orders = $db->table('orders')->get();
foreach ($orders as $order) {
    $user = $db->table('users')->find($order['user_id']);
}

// Good: Single query with JOIN
$orders = $db->table('orders')
    ->select('orders.*', 'users.name', 'users.email')
    ->join('users', 'orders.user_id', '=', 'users.id')
    ->get();
```

### 5. Use Exists Instead of Count

```php
// Bad: Counts all records
if ($db->table('users')->where('email', $email)->count() > 0) {
    // ...
}

// Good: Stops after finding first match
if ($db->table('users')->where('email', $email)->exists()) {
    // ...
}
```

---

## Security Best Practices

### 1. Never Use Raw User Input

```php
// DANGEROUS - SQL Injection risk
$email = $_GET['email'];
$users = $db->table('users')
    ->whereRaw("email = '$email'")  // DON'T DO THIS!
    ->get();

// SAFE - Uses parameter binding
$email = $_GET['email'];
$users = $db->table('users')
    ->where('email', $email)  // ✓ Safe
    ->get();