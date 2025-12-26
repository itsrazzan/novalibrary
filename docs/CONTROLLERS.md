# CONTROLLERS Folder Documentation

## 📄 Files
- `login.php` - Handle login form submission & user authentication

---

## 🔐 login.php

### Purpose
Controller untuk handle login process:
1. Menerima form submission dari login page
2. Validasi input (username, password)
3. Authenticate user dengan database
4. Set session variables
5. Redirect ke dashboard yang sesuai (admin/user)

---

## 📋 Complete Code Flow

### Step 1: Initialize & Include
```php
<?php
session_start();  // Start/resume session untuk store user data
require_once __DIR__ . '/../config/helpers.php';  // Load path helpers
```

**Why `__DIR__`?**
- `__DIR__` = absolute path direktori file saat ini (/NOVA-Library/controllers)
- Relative path `../config/helpers.php` bisa error jika PHP bekerja di folder berbeda
- `__DIR__` selalu bekerja regardless of where script is called from

### Step 2: Check Request Method
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Hanya process jika form di-submit dengan POST
}
```

**Why POST?**
- GET: credentials visible di URL history (security risk)
- POST: credentials di request body (lebih aman)

---

## 🔄 Main Authentication Logic

### Step 3: Load Model & Get Input
```php
require_once __DIR__ . '/../models/validateLogin.php';

$username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8'));
$password = $_POST['password'] ?? '';
```

**Security Details:**

#### `trim()`
Menghapus whitespace awal/akhir
```
Input: "  admin  "
Output: "admin"
```

#### `htmlspecialchars()`
Prevent XSS (Cross-Site Scripting) attack
```
Input: "<script>alert('xss')</script>"
Output: "&lt;script&gt;alert('xss')&lt;/script&gt;"
```

#### `ENT_QUOTES`
Escape single & double quotes
```
Input: 'It\'s "quoted"'
Output: 'It&#039;s &quot;quoted&quot;'
```

#### Password handling
```php
$password = $_POST['password'] ?? '';
```
**Note:** Password TIDAK di-sanitize karena:
- Bcrypt hash = specific bytes
- `htmlspecialchars()` bisa corrupt hash
- Password di-hash di database (safe from SQL injection)

---

### Step 4: Validate Input (First Layer)

#### 4a. Check Empty Fields
```php
if (empty($username) || empty($password)) {
    $_SESSION['error'] = "Semua field harus diisi!";
    header("Location: " . getRedirectUrl('views/login.php'));
    exit();
}
```

**Logic:**
- `empty()` checks untuk: "", 0, null, false, "0", array()
- Set error message di session
- Redirect kembali ke login page dengan error message

#### 4b. Validate Username Format
```php
elseif (!preg_match('/^[a-zA-Z0-9]{4,20}$/', $username)) {
    $_SESSION['error'] = "Username: huruf/angka, 4-20 karakter!";
    header("Location: " . getRedirectUrl('views/login.php'));
    exit();
}
```

**Regex Pattern:** `/^[a-zA-Z0-9]{4,20}$/`
```
^           = Start of string
[a-zA-Z0-9] = Hanya huruf & angka
{4,20}      = Minimal 4, maksimal 20 karakter
$           = End of string
```

**Valid Examples:**
- ✓ admin
- ✓ member1
- ✓ User1234

**Invalid Examples:**
- ✗ ad (terlalu pendek, < 4)
- ✗ admin@123 (ada special char)
- ✗ adminnnnnnnnnnnnnnnnnn (terlalu panjang, > 20)

---

### Step 5: Database Authentication (Second Layer)

```php
else {
    $userData = loginCheck($username, $password);
    
    if ($userData && is_array($userData)) {
        // Login success
    } else {
        // Login failed
    }
}
```

**What `loginCheck()` does:**
1. Query database dengan `get_user_for_auth()` function
2. Ambil user data (id, username, hashed_password, status)
3. Verify password dengan `password_verify()`
4. Return user data (minus password) atau false

**Flow:**
```
loginCheck('admin', 'BismillaH97')
    ↓
Query: SELECT * FROM get_user_for_auth('admin')
    ↓
Return:
{
    'id': 1,
    'username': 'admin',
    'hashed_password': '$2y$10$...',  ← bcrypt hash
    'status': 'admin'
}
    ↓
password_verify('BismillaH97', '$2y$10$...')
    ↓
true → Return user data (tanpa password)
false → Return false
```

---

### Step 6: Handle Login Success

```php
if ($userData && is_array($userData)) {
    // Login sukses + role handling
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['role'] = $userData['status'];
    
    // Clear any previous error
    if (isset($_SESSION['error'])) unset($_SESSION['error']);
```

**Session Variables Set:**

| Variable | Value | Purpose |
|----------|-------|---------|
| `logged_in` | true | Flag user sudah authenticated |
| `user_id` | 1 | User ID untuk query dashboard data |
| `role` | "admin" or "member" | Determine access permissions |

**Example Session Data:**
```php
$_SESSION = [
    'PHPSESSID' => 'abc123def...',
    'logged_in' => true,
    'user_id' => 1,
    'role' => 'admin'
]
```

---

### Step 7: Role-Based Redirect

```php
if ($userData['status'] === 'admin') {
    header("Location: " . getRedirectUrl('views/admin/dashboard.php'));
    exit();
} elseif ($userData['status'] === 'user' || $userData['status'] === 'member') {
    header("Location: " . getRedirectUrl('views/user/dashboard.php'));
    exit();
}
```

**Redirect Logic:**

```
User status = "admin"
    ↓
Check: $userData['status'] === 'admin'? YES
    ↓
Redirect ke: /NOVA-Library/views/admin/dashboard.php
    ↓
exit() = Stop execution, jangan lanjut ke code berikutnya
```

```
User status = "member"
    ↓
Check: $userData['status'] === 'admin'? NO
    ↓
Check: $userData['status'] === 'user' || 'member'? YES
    ↓
Redirect ke: /NOVA-Library/views/user/dashboard.php
    ↓
exit()
```

---

### Step 8: Handle Login Failed

```php
} else {
    $_SESSION['error'] = "Username belum terdaftar atau password salah!";
    header("Location: " . getRedirectUrl('views/login.php'));
    exit();
}
```

**Security Note:**
❌ **Bad:** "Username tidak ditemukan" (reveals if user exists)
❌ **Bad:** "Password salah" (confirms username exists)
✓ **Good:** "Username atau password salah" (generic message)

Alasan: Prevent brute force username enumeration attack

---

## 📊 Complete Process Diagram

```
┌─────────────────────────────────┐
│ User Submit Login Form (POST)   │
│ username=admin                  │
│ password=BismillaH97            │
└────────────┬────────────────────┘
             ↓
        ┌─────────────────────────┐
        │ Start Session           │
        │ Include Helpers         │
        └────────┬────────────────┘
                 ↓
        ┌─────────────────────────┐
        │ Validate Input          │
        │ - Not empty?            │
        │ - Format valid?         │
        └────────┬────────────────┘
                 ↓ (success)
        ┌─────────────────────────┐
        │ Query Database          │
        │ loginCheck($u, $p)      │
        └────────┬────────────────┘
                 ↓
        ┌──────────────┬──────────────┐
        ↓              ↓              ↓
    Found   ┌────────────┐        Not Found
            │ Verify     │        or
            │ password   │        Password Wrong
            └──┬─────┬──┘         │
            ✓  │     │  ✗         ↓
               ↓     └────────────┐
        ┌─────────────────────┐   │
        │ Set Session         │   │
        │ $_SESSION['logged_in']=true│
        │ $_SESSION['user_id']=1 │   │
        │ $_SESSION['role']='admin'  │   │
        └────┬────────────────┘   │
             ↓                    │
      ┌──────────────┐            │
      │ Check role   │            │
      │ admin?       │            │
      └──┬──────┬────┘            │
      YES│      │NO               │
         ↓      ↓                 ↓
    /admin/  /user/    ┌──────────────────┐
    dashboard dashboard│ Set error msg    │
         ↓      ↓      │ Redirect to login│
      REDIRECT  REDIRECT└──────────────────┘
         ↓      ↓              ↓
      [end]  [end]          [end]
```

---

## 🧪 Testing Examples

### Test 1: Admin Login
```bash
curl -X POST http://localhost/NOVA-Library/controllers/login.php \
  -d "username=admin&password=BismillaH97" \
  -c /tmp/cookies.txt

# Response: HTTP 302 redirect to /NOVA-Library/views/admin/dashboard.php
```

### Test 2: Member Login
```bash
curl -X POST http://localhost/NOVA-Library/controllers/login.php \
  -d "username=member1&password=pass123" \
  -c /tmp/cookies.txt

# Response: HTTP 302 redirect to /NOVA-Library/views/user/dashboard.php
```

### Test 3: Wrong Password
```bash
curl -X POST http://localhost/NOVA-Library/controllers/login.php \
  -d "username=admin&password=wrongpass"

# Response: HTTP 302 redirect to /NOVA-Library/views/login.php
# Session error: "Username belum terdaftar atau password salah!"
```

### Test 4: Empty Username
```bash
curl -X POST http://localhost/NOVA-Library/controllers/login.php \
  -d "username=&password=BismillaH97"

# Response: HTTP 302 redirect to /NOVA-Library/views/login.php
# Session error: "Semua field harus diisi!"
```

---

## 🔒 Security Features Implemented

✓ **Input Validation**
- Username format check (regex)
- Empty field check

✓ **Input Sanitization**
- XSS prevention (htmlspecialchars)
- Whitespace trimming

✓ **Password Security**
- Bcrypt hashing (not plaintext)
- password_verify() untuk comparison
- Password tidak di-sanitize (preserve hash)

✓ **Session Management**
- Session variables untuk authentication state
- Generic error messages (prevent user enumeration)

✓ **Database Security**
- Prepared statements (SQL injection prevention)
- PDO dengan exception handling

---

## ⚠️ Future Improvements

### Should Add (untuk production)
- [ ] CSRF token validation
- [ ] Rate limiting (prevent brute force)
- [ ] IP whitelisting
- [ ] Login attempt logging
- [ ] Account lockout setelah N failed attempts
- [ ] Two-factor authentication (2FA)
- [ ] Session timeout
- [ ] Secure cookie flags (HttpOnly, Secure, SameSite)

### Current Limitations (for learning)
- No CSRF protection
- No rate limiting
- No account lockout
- Session timeout = browser dependent
- Credentials hardcoded (use env vars in production)

