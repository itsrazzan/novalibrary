# CONFIG Folder Documentation

## 📄 Files
- `database.php` - Database connection configuration
- `helpers.php` - Utility functions untuk path & URL management

---

## 🗄️ database.php

### Purpose
Menghandle koneksi ke PostgreSQL database dengan PDO (PHP Data Objects)

### Class: Database

#### Properties
```php
private $host = "localhost";          // Server host (not used for unix socket)
private $port = "5432";               // PostgreSQL port (not used for unix socket)
private $db_name = "novalibrary";     // Database name
private $username = "admin";          // Database user
private $password = "BismillaH97";    // Database password
public $conn;                         // PDO connection object
```

#### Method: getConnection()
```php
public function getConnection()
```

**Logic Flow:**
```
1. Initialize $this->conn = null (reset connection)
2. Build DSN (Data Source Name) string untuk PostgreSQL
   └─ Format: "pgsql:host=/var/run/postgresql;dbname=novalibrary"
   └─ Menggunakan unix socket (/var/run/postgresql) instead of TCP localhost:5432
   └─ Alasan: PostgreSQL default listen to unix socket, lebih secure

3. Create PDO connection dengan credentials
   └─ new PDO($dsn, $username, $password)
   └─ Set ERRMODE_EXCEPTION untuk better error handling

4. Catch PDOException jika koneksi gagal
   └─ Echo error message untuk debugging

5. Return $this->conn object
```

#### Usage Example
```php
require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

if ($conn) {
    // Execute queries
    $query = "SELECT * FROM username WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([1]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
}
```

#### Connection String Breakdown
```
DSN: "pgsql:host=/var/run/postgresql;dbname=novalibrary"

pgsql              → Driver type (PostgreSQL)
host=/var/run/...  → Unix socket path (not TCP host:port)
dbname=novalibrary → Database name
```

**Why unix socket?**
- More secure than TCP connection
- Default PostgreSQL setup on Linux uses socket
- Faster than TCP for local connections
- Cannot be accessed over network (good for security)

---

## 🛠️ helpers.php

### Purpose
Menyediakan utility functions untuk dynamic path dan URL management
Memungkinkan aplikasi bekerja di berbagai environment:
- `localhost/NOVA-Library`
- `localhost:8000`
- Domain production

### Functions

#### 1. getBasePath()
```php
function getBasePath()
```

**Purpose:** Mendeteksi base path aplikasi secara dinamis

**Logic:**
```
1. Ambil REQUEST_URI dari $_SERVER
   └─ REQUEST_URI = "/NOVA-Library/controllers/login.php"
   
2. Extract folder pertama dari URI
   └─ Parse "/NOVA-Library/controllers/login.php"
   └─ Dapatkan "NOVA-Library"
   
3. Check apakah folder pertama adalah reserved folder
   └─ Reserved: ['public', 'controllers', 'models', 'views', 'config', 'admin', 'user', ...]
   └─ Jika bukan reserved folder, itu adalah base path
   
4. Return base path
   └─ Contoh: "/NOVA-Library" atau "/" atau ""
```

**Return Value:**
```
localhost/NOVA-Library/views/login.php → "/NOVA-Library"
localhost/controllers/login.php         → "/" (root, return "")
localhost:8000/views/login.php          → "/" (root, return "")
```

#### 2. getRedirectUrl($path)
```php
function getRedirectUrl($path)
```

**Purpose:** Generate full redirect URL untuk header() function

**Parameters:**
- `$path` (string) - Relative path dari root aplikasi
  - Contoh: `'views/login.php'`, `'views/admin/dashboard.php'`

**Logic:**
```
1. Get base path dari getBasePath()
2. Trim leading slashes dari path parameter
3. Concatenate: base_path + "/" + path
4. Return full path untuk redirect
```

**Examples:**
```php
// Scenario 1: localhost/NOVA-Library
REQUEST_URI = "/NOVA-Library/controllers/login.php"
getRedirectUrl('views/admin/dashboard.php')
→ "/NOVA-Library" + "/" + "views/admin/dashboard.php"
→ "/NOVA-Library/views/admin/dashboard.php" ✓

// Scenario 2: localhost:8000 (root)
REQUEST_URI = "/controllers/login.php"
getRedirectUrl('views/admin/dashboard.php')
→ "" + "/" + "views/admin/dashboard.php"
→ "/views/admin/dashboard.php" ✓
```

**Usage:**
```php
if ($userData['status'] === 'admin') {
    header("Location: " . getRedirectUrl('views/admin/dashboard.php'));
    exit();
}
```

#### 3. getAssetUrl($path)
```php
function getAssetUrl($path)
```

**Purpose:** Generate URL untuk asset files (CSS, JS, images)

**Parameters:**
- `$path` (string) - Relative path dari root aplikasi
  - Contoh: `'public/css/login.css'`, `'public/js/login.js'`

**Logic:**
Sama dengan `getRedirectUrl()` - calls it internally

**Examples:**
```php
// Di HTML
<link rel="stylesheet" href="<?php echo getAssetUrl('public/css/login.css'); ?>">

// Result untuk localhost/NOVA-Library
→ <link rel="stylesheet" href="/NOVA-Library/public/css/login.css">

// Result untuk localhost:8000
→ <link rel="stylesheet" href="/public/css/login.css">
```

#### 4. getBaseUrl()
```php
function getBaseUrl()
```

**Purpose:** Generate lengkap URL dengan protocol dan domain
Berguna untuk API calls, email links, redirect absolute URLs

**Logic:**
```
1. Detect protocol (http vs https)
   └─ Check $_SERVER['HTTPS'] dan $_SERVER['SERVER_PORT']
   
2. Get domain dari $_SERVER['HTTP_HOST']
   └─ Contoh: "localhost", "nova-library.com"
   
3. Append base path
   └─ Combine: protocol + domain + base_path
   
4. Remove trailing slashes
   └─ rtrim($url, '/')
```

**Examples:**
```php
// Scenario 1: localhost/NOVA-Library
getBaseUrl()
→ "http://localhost/NOVA-Library"

// Scenario 2: localhost:8000
getBaseUrl()
→ "http://localhost"

// Scenario 3: Production
getBaseUrl()
→ "https://nova-library.com/library"
```

**Usage:**
```php
// Untuk fetch API
fetch(getBaseUrl() + '/api/books')

// Untuk absolute redirect
header("Location: " . getBaseUrl() . '/views/login.php');

// Untuk email links
$login_link = getBaseUrl() . '/views/login.php';
```

---

## 🔄 Path Detection Flow Chart

```
REQUEST dibuat ke: /NOVA-Library/views/login.php
        ↓
        ↓ $_SERVER['REQUEST_URI']
        ↓
[helpers.php] getBasePath()
        ↓
        ├─ Parse URI: "/NOVA-Library/views/login.php"
        ├─ Extract folder pertama: "NOVA-Library"
        ├─ Check apakah reserved? NO → itu base path!
        ├─ Cache result: "/NOVA-Library"
        ↓
Ketika getAssetUrl('public/css/login.css') dipanggil
        ↓
        ├─ Get cached base path: "/NOVA-Library"
        ├─ Build path: "/NOVA-Library" + "/" + "public/css/login.css"
        ├─ Return: "/NOVA-Library/public/css/login.css"
        ↓
HTML Output:
<link rel="stylesheet" href="/NOVA-Library/public/css/login.css">
```

---

## 🐛 Debugging Tips

### Check current base path
```php
require_once 'config/helpers.php';

echo "Base Path: " . getBasePath();
echo "Asset URL: " . getAssetUrl('public/css/login.css');
echo "Redirect URL: " . getRedirectUrl('views/login.php');
echo "Base URL: " . getBaseUrl();
```

### Check $_SERVER variables
```php
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'];
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'];
echo "HTTP_HOST: " . $_SERVER['HTTP_HOST'];
```

### Test path generation
```bash
# Test dari command line
php -r "
\$_SERVER['REQUEST_URI'] = '/NOVA-Library/controllers/login.php';
require 'config/helpers.php';
echo getRedirectUrl('views/admin/dashboard.php');
"
```

---

## ⚙️ Configuration Best Practices

### For Different Environments

#### Development
```php
// database.php
private $password = "BismillaH97"; // Safe untuk dev only!
```

#### Production
```php
// Use environment variables
private $password = getenv('DB_PASSWORD'); // atau $_ENV['DB_PASSWORD']
// Set di .env file atau server config
```

### Security Notes
- ❌ Jangan hardcode credentials di code
- ✅ Gunakan environment variables
- ✅ Gunakan unix socket untuk database
- ✅ Gunakan PDO prepared statements
- ✅ Enable exception mode untuk PDO

