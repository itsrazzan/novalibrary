# VIEWS Folder Documentation

## 📄 Files
- `login.php` - Login form page
- `admin/dashboard.php` - Admin dashboard (after login)
- `user/dashboard.php` - User/Member dashboard (after login)

---

## 🎨 login.php

### Purpose
Render login form HTML page:
- Display login input form
- Show error messages jika login gagal
- Load CSS & JavaScript files
- Submit form ke login controller

---

## 📋 Complete HTML Structure

### Step 1: Session & Include Helpers
```php
<?php session_start();
require_once __DIR__ . '/../config/helpers.php';
?>
```

**Why session_start() di view?**
- Access session variables (untuk show error messages)
- Manage session state
- Must be before any output sent

**Helpers untuk dynamic paths:**
```php
getAssetUrl('public/css/login.css')  → /NOVA-Library/public/css/login.css
getAssetUrl('public/js/login.js')    → /NOVA-Library/public/js/login.js
```

---

### Step 2: HTML Head
```html
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Nova Academy Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo getAssetUrl('public/css/login.css'); ?>">
  </head>
```

**Meta Tags:**
- `charset="UTF-8"` - Character encoding
- `viewport` - Responsive design (mobile friendly)

**CSS:**
- Tailwind CDN - Utility-first CSS framework
- login.css - Custom styles

---

### Step 3: Login Form HTML

#### Form Container
```html
<form
  id="loginForm"
  action="../controllers/login.php"
  method="POST"
  class="space-y-6"
>
```

**Attributes:**
- `action="../controllers/login.php"` - Form submit ke login controller
  - **Note:** Relative path bekerja dari views folder
  - `../controllers/login.php` = go up 1 level, then enter controllers
  
- `method="POST"` - POST request (credentials di body, not URL)

- `class="space-y-6"` - Tailwind CSS spacing

#### Error Message Display
```php
<?php if (isset($_SESSION['error'])): ?>
<div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
  <div class="flex items-center">
    <p class="text-sm text-red-700"><?= $_SESSION['error']; ?></p>
  </div>
</div>
<?php endif; ?>
```

**Logic:**
1. Check apakah `$_SESSION['error']` exist
2. Jika ada, display error message dalam div
3. Jika tidak ada, tidak display apapun

**Why session error?**
- Controller set `$_SESSION['error']` sebelum redirect
- View check session saat page load
- Message persist selama session exists

**Example Flow:**
```
User submit wrong password
    ↓
Controller set $_SESSION['error'] = "Username atau password salah!"
    ↓
Redirect ke login.php
    ↓
login.php load, session masih exist
    ↓
if (isset($_SESSION['error'])) → TRUE
    ↓
Display error message
```

#### Username Input
```html
<div>
  <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">
    Username
  </label>
  <input
    type="text"
    id="username"
    name="username"
    required
    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
    placeholder="Masukkan username Anda"
  />
</div>
```

**HTML Input Details:**
- `type="text"` - Text input
- `id="username"` - Unique identifier (untuk CSS/JS target)
- `name="username"` - Form field name (untuk $_POST)
- `required` - HTML5 validation (client-side)
- `class="..."` - Tailwind CSS styling
- `placeholder` - Hint text

**How form submission works:**
```
User type: "admin"
    ↓
Click submit button
    ↓
Form submit dengan method=POST
    ↓
Browser send:
POST /controllers/login.php HTTP/1.1
...
username=admin&password=BismillaH97

Controller receive:
$_POST['username'] = "admin"
$_POST['password'] = "BismillaH97"
```

#### Password Input
```html
<div>
  <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
    Password
  </label>
  <input
    type="password"
    id="password"
    name="password"
    required
    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
    placeholder="Masukkan password Anda"
  />
</div>
```

**`type="password"`:**
- Hide input text dengan dots/asterisks
- Prevent shoulder surfing
- Not encrypted (use HTTPS for real security)

---

### Step 4: Submit Button
```html
<button
  type="submit"
  class="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-bold py-3 rounded-lg transition-all duration-300 shadow-lg hover:shadow-xl"
>
  Masuk
</button>
```

**Attributes:**
- `type="submit"` - Trigger form submission
- `class="..."` - Tailwind gradient & hover effects

---

### Step 5: Register Link
```html
<p class="text-center text-gray-600">
  Belum punya akun?
  <a
    href="register.html"
    class="text-blue-600 hover:underline font-medium"
  >
    Daftar sekarang
  </a>
</p>
```

**Purpose:**
- Link ke registration page
- User baru bisa mendaftar

---

### Step 6: JavaScript Include
```php
<script src="<?php echo getAssetUrl('public/js/login.js'); ?>"></script>
```

**Purpose:**
- Load JavaScript untuk form validation
- Client-side enhancements

---

## 🎯 Dashboard Pages

### Admin Dashboard (admin/dashboard.php)
```php
<?php
session_start();

// Check apakah user sudah login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: /NOVA-Library/views/login.php");
    exit();
}

// Check apakah user adalah admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: /NOVA-Library/views/user/dashboard.php");
    exit();
}
?>
<!-- Admin Dashboard HTML -->
```

**Security Checks:**

1. **Session Check:**
```php
if (!isset($_SESSION['logged_in']))
    // User not logged in, redirect ke login
```

2. **Role Check:**
```php
if ($_SESSION['role'] !== 'admin')
    // User bukan admin, redirect ke user dashboard
```

**Why these checks?**
- Prevent unauthorized access
- User tidak bisa langsung akses /admin/dashboard.php
- Even jika user manipulate URL, harus login dulu
- Even jika user login as member, tidak bisa akses admin panel

---

### User Dashboard (user/dashboard.php)
```php
<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: /NOVA-Library/views/login.php");
    exit();
}

if ($_SESSION['role'] !== 'user' && $_SESSION['role'] !== 'member') {
    header("Location: /NOVA-Library/views/admin/dashboard.php");
    exit();
}
?>
<!-- User Dashboard HTML -->
```

**Same logic sebagai admin:**
- Check login status
- Check role (user or member)

---

## 🔄 Complete Page Flow

```
User visit /NOVA-Library/views/login.php
    ↓
    ├─ Session start
    ├─ Include helpers
    ├─ Load CSS/JS dengan dynamic paths
    ├─ Display form
    └─ Check $_SESSION['error']
        ├─ If exist → Show error message
        └─ If not → Show form only

User input credentials & click submit
    ↓
Browser POST ke /NOVA-Library/controllers/login.php
    ↓
Controller:
    ├─ Validate input
    ├─ Query database
    ├─ Verify password
    ├─ Set session variables
    └─ Redirect ke dashboard

Browser follow redirect
    ↓
Dashboard load:
    ├─ Check session (logged_in)
    ├─ Check role
    └─ Display appropriate dashboard
```

---

## 🎨 CSS & Styling

### Tailwind CSS
```html
<div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
```

**Breakdown:**
- `p-4` - Padding: 1rem (16px)
- `bg-red-50` - Background color: light red
- `border-l-4` - Left border: 4px
- `border-red-500` - Border color: red
- `rounded-lg` - Border radius: large

### Custom CSS (login.css)
```css
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.gradient-bg {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
}

.floating-shape {
  animation: float 6s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-20px); }
}
```

---

## 🔍 XSS Prevention

### Error Message Display (Safe)
```php
<?= $_SESSION['error']; ?>
```

**Why is this safe?**
1. Controller use `htmlspecialchars()` untuk username (input)
2. Password tidak di-display
3. Error message adalah hardcoded string dari controller
4. No user input directly echo ke HTML

**Example:**
```
If user input: <script>alert('xss')</script>
    ↓
Controller: htmlspecialchars() = &lt;script&gt;...&lt;/script&gt;
    ↓
Database: store as escaped string
    ↓
View: echo = display sebagai text, not HTML
```

---

## 📱 Responsive Design

```html
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
```

**Mobile Friendly:**
- Viewport meta tag - Proper scaling di mobile
- Tailwind CSS - Mobile-first responsive classes
- Flexbox layout - Adapts ke screen size

---

## 🔗 Related Files

- **controllers/login.php** - Form submit destination
- **config/helpers.php** - Dynamic path generation
- **public/css/login.css** - Custom styling
- **public/js/login.js** - Client-side logic
- **admin/dashboard.php** - After admin login
- **user/dashboard.php** - After member login

---

## 🧪 Testing

### Test 1: Visit Login Page
```bash
curl http://localhost/NOVA-Library/views/login.php
```
Expected: HTML form page loads

### Test 2: Submit Form
```bash
curl -X POST http://localhost/NOVA-Library/controllers/login.php \
  -d "username=admin&password=BismillaH97" \
  -L  # Follow redirect
```
Expected: Redirect ke admin dashboard, HTTP 200

### Test 3: Error Message Display
```bash
curl -X POST http://localhost/NOVA-Library/controllers/login.php \
  -d "username=admin&password=wrongpass" \
  -L
```
Expected: Redirect ke login, show error message

### Test 4: Direct Dashboard Access (No Login)
```bash
curl http://localhost/NOVA-Library/views/admin/dashboard.php
```
Expected: Redirect ke login.php

