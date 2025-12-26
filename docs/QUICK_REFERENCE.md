# QUICK REFERENCE - NOVA Library Authentication

## 📊 Complete System Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                     USER INTERACTION                        │
│                                                             │
│  Browser → POST /controllers/login.php                    │
│           {username, password}                            │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ↓
        ┌─────────────────────────────┐
        │  CONTROLLER LAYER           │
        │  (login.php)                │
        ├─────────────────────────────┤
        │ 1. Validate input           │
        │ 2. Call loginCheck()        │
        │ 3. Set session              │
        │ 4. Redirect                 │
        └────────────┬────────────────┘
                     │
                     ↓
        ┌─────────────────────────────┐
        │  MODEL LAYER                │
        │  (validateLogin.php)        │
        ├─────────────────────────────┤
        │ 1. Query get_user_for_auth()│
        │ 2. Fetch user data          │
        │ 3. password_verify()        │
        │ 4. Return user or false     │
        └────────────┬────────────────┘
                     │
                     ↓
        ┌─────────────────────────────┐
        │  DATABASE LAYER             │
        │  (database.php)             │
        ├─────────────────────────────┤
        │ PDO PostgreSQL              │
        │ Unix socket connection      │
        │ Prepared statements         │
        └────────────┬────────────────┘
                     │
                     ↓
        ┌─────────────────────────────┐
        │  DATABASE                   │
        │  PostgreSQL 16              │
        └─────────────────────────────┘
```

---

## 🔑 Key Files & Functions

| File | Function | Purpose |
|------|----------|---------|
| `controllers/login.php` | (main) | Login form handler |
| `models/validateLogin.php` | `loginCheck()` | Database auth |
| `config/database.php` | `getConnection()` | DB connection |
| `config/helpers.php` | `getBasePath()` | Detect app root |
| `config/helpers.php` | `getRedirectUrl()` | Dynamic redirects |
| `config/helpers.php` | `getAssetUrl()` | Dynamic asset URLs |
| `views/login.php` | (HTML/PHP) | Login form page |

---

## 🔐 Authentication Flow (In 30 Seconds)

```
1. User submits form
   ↓
2. Controller validates & sanitizes input
   ↓
3. Controller calls loginCheck(username, password)
   ↓
4. Model queries database for user
   ↓
5. Model verifies password with bcrypt
   ↓
6. If match: Set session → Redirect to dashboard
   If no match: Set error → Redirect to login
   ↓
7. Dashboard checks session before displaying
```

---

## 🛡️ Security Layers

1. **Input Layer**
   - Empty check
   - Format validation (regex)
   - htmlspecialchars() sanitization

2. **Database Layer**
   - Prepared statements (SQL injection prevention)
   - Bcrypt hashing (password security)
   - password_verify() (time-safe comparison)

3. **Session Layer**
   - Session variables for state
   - Role checking before dashboard access
   - Generic error messages

---

## 📝 Testing Commands

### Admin Login
```bash
curl -X POST http://localhost/NOVA-Library/controllers/login.php \
  -d "username=admin&password=BismillaH97" -L
```
Result: Redirect to admin dashboard

### Member Login
```bash
curl -X POST http://localhost/NOVA-Library/controllers/login.php \
  -d "username=member1&password=pass123" -L
```
Result: Redirect to user dashboard

### Wrong Password
```bash
curl -X POST http://localhost/NOVA-Library/controllers/login.php \
  -d "username=admin&password=wrongpass" -L
```
Result: Redirect to login with error message

### Check Database
```bash
sudo -u postgres psql -d novalibrary -c \
  "SELECT id, username, status FROM username;"
```
Result: User list

---

## 🔑 Test Credentials

```
┌──────────┬──────────────────┬─────────────────┐
│ Username │ Password         │ Status          │
├──────────┼──────────────────┼─────────────────┤
│ admin    │ BismillaH97      │ admin           │
│ member1  │ pass123          │ member (user)   │
│ member2  │ pass123          │ member (user)   │
│ ...      │ pass123          │ member (user)   │
│ member9  │ pass123          │ member (user)   │
└──────────┴──────────────────┴─────────────────┘
```

---

## 🔧 Common Errors & Fixes

| Error | Cause | Fix |
|-------|-------|-----|
| Connection failed | PostgreSQL not running | `sudo systemctl start postgresql` |
| CSS not loading | .htaccess rewrite | `mv .htaccess .htaccess.backup` |
| Login stuck | Path issue | Use `__DIR__` in require_once |
| Member can't login | Password not hashed | Update password with bcrypt hash |

---

## 📂 Path Structure

```
/NOVA-Library/
├─ /config/database.php          (DB connection)
├─ /config/helpers.php            (Path helpers)
├─ /controllers/login.php         (Login handler)
├─ /models/validateLogin.php      (Auth query)
├─ /views/login.php               (Login form)
├─ /views/admin/dashboard.php     (Admin view)
├─ /views/user/dashboard.php      (User view)
├─ /public/css/login.css          (Styling)
├─ /public/js/login.js            (Scripts)
│
├─ README.md                       (Main docs)
├─ SETUP_GUIDE.md                 (Problems/solutions)
├─ INDEX.md                        (Navigation)
└─ /docs/
   ├─ CONFIG.md                  (Connection & helpers)
   ├─ MODELS.md                  (Database logic)
   ├─ CONTROLLERS.md             (Login logic)
   └─ VIEWS.md                   (HTML & forms)
```

---

## 🎯 Code Patterns

### Input Validation (Controller)
```php
if (empty($username) || empty($password)) {
    // Set error, redirect
}
if (!preg_match('/^[a-zA-Z0-9]{4,20}$/', $username)) {
    // Set error, redirect
}
```

### Input Sanitization (Controller)
```php
$username = trim(htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8'));
// Removes whitespace, escapes HTML special chars
```

### Database Query (Model)
```php
$query = "SELECT * FROM get_user_for_auth(:username)";
$stmt = $db->prepare($query);
$stmt->bindParam(':username', $username);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
```

### Password Verification (Model)
```php
if ($user && password_verify($password, $user['hashed_password'])) {
    // Login success
    unset($user['hashed_password']);
    return $user;
}
return false;  // Login failed
```

### Session Management (Controller)
```php
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = $userData['id'];
$_SESSION['role'] = $userData['status'];
```

### Access Control (View)
```php
<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: /NOVA-Library/views/login.php");
    exit();
}
?>
```

---

## 📊 HTTP Status Codes

| Status | Meaning | Usage |
|--------|---------|-------|
| 200 | OK | Dashboard loaded successfully |
| 302 | Found (redirect) | Login redirect to dashboard/login |
| 500 | Server error | Database/code error |

---

## 🔐 Bcrypt Hash Format

```
$2y$10$j26jMpvRnl5UjWbgfpoZnenGaVGBBWWgxgWTWuYRN2O0nBaj18CEe
└─┘└┘└ ┘└────────────────────────┬────────────────────────┘
 │ │ │  │                        │
 │ │ │  │                        └─ Hash (31 chars)
 │ │ │  └─ Salt (22 chars)
 │ │ └─ Cost (10 = 2^10 iterations)
 │ └─ Algorithm version (2y = PHP 5.3.7+)
 └─ Format identifier ($2a, $2x, $2y)
```

---

## 🗂️ Data Flow Example

### Admin Login Success
```
Input:
  username=admin
  password=BismillaH97

Processing:
  1. Validate: "admin" matches regex ✓
  2. Sanitize: trim & htmlspecialchars
  3. Query: SELECT * FROM get_user_for_auth('admin')
  4. Result: {id:1, username:'admin', hashed_password:'$2y$10$...', status:'admin'}
  5. Verify: password_verify('BismillaH97', '$2y$10$...') → TRUE
  6. Session: logged_in=true, user_id=1, role=admin
  7. Redirect: /NOVA-Library/views/admin/dashboard.php

Output:
  HTTP 302 redirect to admin dashboard
  Browser loads dashboard with session active
```

---

## 🚀 Development Setup

```bash
# Start services
sudo /opt/lampp/lampp start
sudo systemctl start postgresql

# Verify connection
curl -X POST http://localhost/NOVA-Library/controllers/login.php \
  -d "username=admin&password=BismillaH97"

# Expected: HTTP 302 redirect
```

---

## 📈 Production TODOs

- [ ] Move credentials to .env file
- [ ] Implement CSRF token
- [ ] Add rate limiting
- [ ] Add login logging
- [ ] Implement account lockout
- [ ] Add password reset
- [ ] Enforce HTTPS
- [ ] Add security headers
- [ ] Database backups
- [ ] Error logging (sentry/datadog)

---

## 📚 Documentation Files

- **README.md** - Overview, quick start, troubleshooting
- **SETUP_GUIDE.md** - Problems solved, database setup
- **INDEX.md** - Documentation navigation guide
- **docs/CONFIG.md** - Connection & helpers explained
- **docs/MODELS.md** - Database queries explained
- **docs/CONTROLLERS.md** - Login logic step-by-step
- **docs/VIEWS.md** - HTML & forms explained

---

**Generated:** December 26, 2025
**Project:** NOVA Library Authentication System
**Environment:** LAMPP + PostgreSQL 16

