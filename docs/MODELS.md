# MODELS Folder Documentation

## 📄 Files
- `validateLogin.php` - Database query untuk user authentication

---

## 🔍 validateLogin.php

### Purpose
Model layer untuk handle database logic:
- Query database untuk cari user berdasarkan username
- Verify password dengan bcrypt
- Return user data untuk session

**Separation of Concerns:**
```
Controller (login.php)  ← Handle HTTP request, session, redirect
        ↓
Model (validateLogin.php)  ← Handle database queries
        ↓
Database (database.php)  ← Handle connection
```

---

## 📋 Complete Code Flow

### Step 1: Include Database Class
```php
<?php
/*
 * Note: 
 * 1. Check if username exists in database
 * 2. Check if password matches username in database, return false if not
 * 3. If both match, check user status (admin or regular user), return status and user id
 */
require_once __DIR__ . '/../config/database.php';
```

**Why separate file?**
- Reusable dalam multiple controllers
- Easy to test (unit testing)
- Clean separation of concerns

---

## 🔐 Main Function: loginCheck()

### Function Signature
```php
function loginCheck($username, $password)
```

**Parameters:**
- `$username` (string) - Username from form input (sudah di-validate di controller)
- `$password` (string) - Plain text password from form (belum di-hash)

**Return Value:**
- **Success:** Array dengan user data
  ```php
  [
      'id' => 1,
      'username' => 'admin',
      'status' => 'admin'
      // 'hashed_password' REMOVED for security
  ]
  ```
- **Failed:** `false` (username not found atau password mismatch)

---

## 🔄 Code Flow Step by Step

### Step 1: Create Database Instance & Connection
```php
$database = new Database();
$db = $database->getConnection();
```

**What happens:**
```
new Database()
    ↓
__construct() if exists, atau just init properties
    ↓
$database->getConnection()
    ↓
Create PDO connection ke PostgreSQL unix socket
    ↓
Return $conn object (PDO)
```

### Step 2: Build Query dengan Prepared Statement
```php
try {
    // Ambil data user berdasarkan username melalui function
    $query = "SELECT * FROM get_user_for_auth(:username)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
```

**Query Details:**

#### What is `get_user_for_auth()`?
PostgreSQL function yang defined di database:
```sql
CREATE FUNCTION get_user_for_auth(p_username varchar)
RETURNS TABLE(
    id integer,
    username varchar,
    hashed_password varchar,
    status varchar
)
AS $function$
BEGIN
    RETURN QUERY
    SELECT u.id, u.username, u.password, u.status
    FROM username u
    WHERE u.username = p_username;
END;
$function$;
```

**Why use function?**
- Encapsulate query logic di database
- Reusable
- Centralized business logic
- Better for complex queries

#### Prepared Statement Security
```php
$query = "SELECT * FROM get_user_for_auth(:username)";
//                                          ↑
//                                    Named placeholder
$stmt->bindParam(':username', $username);
```

**Protection dari SQL Injection:**
```
Bad (vulnerable to SQL injection):
$query = "SELECT * FROM get_user_for_auth('$username')";
// Input: admin' OR '1'='1
// Result: SELECT * FROM get_user_for_auth('admin' OR '1'='1')
// Returns ALL users! 🚨

Good (safe with prepared statement):
$query = "SELECT * FROM get_user_for_auth(:username)";
$stmt->bindParam(':username', $username);
// Input: admin' OR '1'='1
// Treated as literal string, not SQL code ✓
```

---

### Step 3: Execute Query
```php
$stmt->execute();
```

**What happens:**
1. Send query ke PostgreSQL server
2. PostgreSQL execute query
3. Return result set

---

### Step 4: Fetch User Data
```php
$user = $stmt->fetch(PDO::FETCH_ASSOC);
```

**PDO::FETCH_ASSOC:**
```
Return hasil sebagai associative array (key => value)

Result dari database:
┌────┬──────────┬───────────────────┬────────┐
│ id │ username │ hashed_password   │ status │
├────┼──────────┼───────────────────┼────────┤
│ 1  │ admin    │ $2y$10$j26jMpv... │ admin  │
└────┴──────────┴───────────────────┴────────┘

Converted to PHP array:
$user = [
    'id' => 1,
    'username' => 'admin',
    'hashed_password' => '$2y$10$j26jMpv...',
    'status' => 'admin'
]
```

---

### Step 5: Verify Password dengan Bcrypt
```php
if ($user && password_verify($password, $user['hashed_password'])) {
    // Password correct!
    unset($user['hashed_password']);  // Remove password for security
    return $user;
}
```

#### What is `password_verify()`?
```php
bool password_verify(string $password, string $hash)
```

**How bcrypt verification works:**

```
1. Plain password dari form input:  "BismillaH97"
2. Hashed password dari database:  "$2y$10$j26jMpvRnl5UjWbgfpoZnenGaVGBBWWgxgWTWuYRN2O0nBaj18CEe"

3. password_verify() process:
   - Extract salt dari hash: $2y$10$j26jMpvRnl5UjWbgfpoZne
   - Hash input password dengan extracted salt
   - Compare dengan original hash
   - Return true jika match, false jika tidak

4. Why not just compare strings?
   - $password = "BismillaH97"
   - $hash = "$2y$10$..."
   - "BismillaH97" === "$2y$10$..." ? FALSE
   
   Harus gunakan password_verify() untuk bcrypt comparison
```

#### Why Bcrypt?
```
Plaintext password: "BismillaH97"
❌ Stored as plaintext: VERY BAD
   - Database breach = all passwords leaked
   
❌ Stored with MD5/SHA1:
   Hash = MD5("BismillaH97") = "abc123..."
   - Rainbow tables available
   - Fast to crack

✓ Stored with Bcrypt:
   Hash = bcrypt("BismillaH97") = "$2y$10$..."
   - Computationally expensive to crack
   - Has salt (prevents rainbow tables)
   - Adaptive (cost parameter increases over time)
```

**Bcrypt Hash Structure:**
```
$2y$10$j26jMpvRnl5UjWbgfpoZnenGaVGBBWWgxgWTWuYRN2O0nBaj18CEe
└─┘└┘└ ┘└──────────────────────────┬──────────────────────────┘
 │ │ │  │                          │
 │ │ │  │                          └─ Hash (31 chars)
 │ │ │  └─ Salt (22 chars)
 │ │ └─ Cost parameter (10 = 2^10 iterations)
 │ └─ Algorithm identifier (2y = PHP 5.3.7+)
 └─ Format identifier ($2a, $2x, $2y)
```

---

### Step 6: Return User Data (Minus Password)
```php
unset($user['hashed_password']);  // Remove untuk security
return $user;
```

**Result:**
```php
// Before unset:
$user = [
    'id' => 1,
    'username' => 'admin',
    'hashed_password' => '$2y$10$...',  ← Password hash
    'status' => 'admin'
]

// After unset:
$user = [
    'id' => 1,
    'username' => 'admin',
    'status' => 'admin'
]
// Password hash removed dari memory
```

**Why remove password?**
- Unnecessary di controller/session
- Reduce memory usage
- Extra security (less exposure)

---

### Step 7: Handle Failed Cases

```php
// If password wrong atau username not found
if ($user && password_verify(...)) {
    // Success
} 

return false;  // Default fail
```

**Conditions untuk return false:**

| Condition | Why | $user | password_verify() |
|-----------|-----|-------|-------------------|
| Username not found | User doesn't exist | null | Not executed |
| Password wrong | Wrong credentials | array | false |
| Both | All cases fail | - | - |

```php
if ($user && password_verify(...))
     ↓        ↓
  Check1   Check2

Check1: $user exist? (not null/false)
  - null/false dari fetch = username not found
  - array = username found

Check2: password_verify() = true?
  - true = password correct
  - false = password wrong

Both must be true untuk login success
```

---

### Step 8: Error Handling
```php
} catch (PDOException $e) {
    return false;
}
```

**PDOException cases:**
- Database connection failed
- SQL syntax error
- Permission denied
- Other database errors

**Return false:**
- Fail safely (tidak expose error details)
- Let controller handle error message
- Better security (tidak leak DB info)

---

## 📊 Data Flow Diagram

```
┌─────────────────────────────────┐
│ loginCheck('admin', 'pass123')  │
└────────────┬────────────────────┘
             ↓
        ┌─────────────────────────┐
        │ Connect to database     │
        │ via PDO                 │
        └────────┬────────────────┘
                 ↓
        ┌─────────────────────────┐
        │ Prepare statement:      │
        │ "SELECT * FROM          │
        │ get_user_for_auth(:u)"  │
        └────────┬────────────────┘
                 ↓
        ┌─────────────────────────┐
        │ Bind parameter:         │
        │ :username = 'admin'     │
        └────────┬────────────────┘
                 ↓
        ┌─────────────────────────┐
        │ Execute query           │
        │ PostgreSQL:             │
        │ function(p_username)    │
        └────────┬────────────────┘
                 ↓
        ┌─────────────────────────┐
        │ Fetch result as array   │
        │ [id, username, hash,    │
        │  status]                │
        └────┬────────┬───────────┘
             │        │
        Found│        │Not found
             ↓        ↓
        ┌──────────┐  │
        │Verify    │  │
        │password? │  │
        └┬────────┬┘  │
        Match  │      │
          ↓    │No    │
       ┌────┐  │      ↓
       │✓   │  │   return false
       │    │  │
       │Rmv │  │
       │pwd │  │
       │    │  │
       │ret │  │
       └──┬─┘  │
          ↓    ↓
        return [id, username, status]
```

---

## 🔒 Security Analysis

### Vulnerabilities Prevented
✓ **SQL Injection** - Prepared statements
✓ **Plaintext Passwords** - Bcrypt hashing
✓ **Weak Hashing** - Bcrypt (not MD5/SHA)
✓ **Password Exposure** - unset() sebelum return
✓ **Timing Attack** - password_verify() time-safe

### Remaining Risks (For Learning Project)
- ❌ No rate limiting (vulnerable to brute force)
- ❌ No account lockout
- ❌ No login logging
- ❌ Credentials hardcoded (should use env vars)

---

## 🧪 Testing Examples

### Test 1: Successful Login
```php
$result = loginCheck('admin', 'BismillaH97');

// Result:
// Array
// (
//     [id] => 1
//     [username] => admin
//     [status] => admin
// )
```

### Test 2: Wrong Password
```php
$result = loginCheck('admin', 'wrongpass');

// Result: false (password_verify() fails)
```

### Test 3: User Not Found
```php
$result = loginCheck('nonexistent', 'pass123');

// Result: false (fetch returns null)
```

### Test 4: Database Error
```php
// Database connection fails
$result = loginCheck('admin', 'pass123');

// Result: false (catch PDOException)
```

---

## 🔗 Related Files

- **config/database.php** - Database connection
- **config/helpers.php** - Path helpers
- **controllers/login.php** - Uses this function
- **Database function:** `get_user_for_auth()` in PostgreSQL

