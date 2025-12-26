# NOVA Library - Setup & Development Guide

## 📋 Daftar Isi
1. [Permasalahan & Solusi](#permasalahan--solusi)
2. [Setup Instructions](#setup-instructions)
3. [Folder Structure](#folder-structure)
4. [Testing Credentials](#testing-credentials)

---

## 🔧 Permasalahan & Solusi

### Problem 1: Path Include Error (Case-Sensitive)
**Error:** `Failed opening required '../config/Database.php'`
- **Root Cause:** Linux case-sensitive, file bernama `database.php` (lowercase)
- **Solution:** 
  - Ubah `require_once '../config/Database.php'` → `require_once __DIR__ . '/../config/database.php'`
  - Gunakan `__DIR__` untuk absolute path yang reliable

**Files Fixed:**
- `models/validateLogin.php`
- `controllers/login.php`

---

### Problem 2: PDO PostgreSQL Driver Not Found
**Error:** `could not find driver`
- **Root Cause:** `pdo_pgsql.so` extension tidak ter-load di LAMPP
- **Solutions Tried:**
  1. Check extension di php.ini → sudah compiled-in
  2. Update `pg_hba.conf` dari `peer` ke `scram-sha-256` authentication
  3. Use unix socket `/var/run/postgresql` instead of `localhost:5432`

**Changes Made:**
```bash
# Enable scram-sha-256 authentication
sudo sed -i 's/^local   all             all                                     peer$/local   all             all                                     scram-sha-256/' /etc/postgresql/16/main/pg_hba.conf

# Restart PostgreSQL
sudo systemctl restart postgresql
```

**File Modified:**
- `config/database.php` - Connection string updated to use unix socket

---

### Problem 3: Login Not Redirecting (Stuck at Controller)
**Error:** Request POST ke login.php tidak redirect, tetap di controller page
- **Root Cause:** `.htaccess` rewrite all requests to public folder, causing 500 error
- **Solution:** Disable `.htaccess` untuk development (learning project)

**Files Disabled:**
- `.htaccess` (root) → renamed to `.htaccess.backup`
- `public/.htaccess` → renamed to `public/.htaccess.backup`

---

### Problem 4: CSS/JS Path Not Working (localhost/NOVA-Library)
**Error:** CSS file di `/NOVA-Library/public/css/login.css` tidak load ketika akses via `localhost/NOVA-Library`
- **Root Cause:** 
  - `.htaccess` rewrite mengubah path
  - Path hardcoded dengan `/` yang tidak fleksibel
- **Solution:** Create dynamic path helper function

**File Created:**
- `config/helpers.php` - Path management functions

---

### Problem 5: Member Login Failed (Password Not Hashed)
**Error:** Member account tidak bisa login, password masih plaintext
- **Root Cause:** Password di database belum di-hash dengan bcrypt
- **Solution:** Hash semua member password dengan bcrypt

**Database Update:**
```sql
-- Generate hash untuk pass123
-- Hash: $2y$10$jZaN4ZwdhPLjt9Y4qnryHOAdqMhIlHdEiuq4vr/oLQGzeitcrB2BK

UPDATE username 
SET password = '$2y$10$jZaN4ZwdhPLjt9Y4qnryHOAdqMhIlHdEiuq4vr/oLQGzeitcrB2BK'
WHERE status = 'member';
```

**Controller Updated:**
- `controllers/login.php` - Handle status 'member' dalam redirect logic

---

## 🚀 Setup Instructions

### Prerequisites
- PHP 8.2+ (LAMPP)
- PostgreSQL 16
- CURL (untuk testing)

### Step 1: Start Services
```bash
# Start LAMPP
sudo /opt/lampp/lampp start

# Start PostgreSQL
sudo systemctl start postgresql
```

### Step 2: Database Setup
```bash
# Create database owner user 'admin'
sudo -u postgres psql -c "CREATE USER admin WITH PASSWORD 'BismillaH97';"

# Create database
sudo -u postgres createdb -O admin novalibrary

# Change auth method
sudo sed -i 's/^local   all             all                                     peer$/local   all             all                                     scram-sha-256/' /etc/postgresql/16/main/pg_hba.conf

# Restart PostgreSQL
sudo systemctl restart postgresql
```

### Step 3: Create Function & Data
```sql
-- Connect to novalibrary database
sudo -u postgres psql -d novalibrary

-- Create function get_user_for_auth
CREATE OR REPLACE FUNCTION public.get_user_for_auth(p_username character varying)
 RETURNS TABLE(id integer, username character varying, hashed_password character varying, status character varying) 
 LANGUAGE plpgsql
AS $function$
BEGIN
     RETURN QUERY
     SELECT u.id, u.username, u.password, u.status
     FROM username u
     WHERE u.username = p_username;
END;
$function$;

-- Hash passwords
-- admin: BismillaH97 → $2y$10$j26jMpvRnl5UjWbgfpoZnenGaVGBBWWgxgWTWuYRN2O0nBaj18CEe
-- pass123 → $2y$10$jZaN4ZwdhPLjt9Y4qnryHOAdqMhIlHdEiuq4vr/oLQGzeitcrB2BK

UPDATE username SET password = '$2y$10$j26jMpvRnl5UjWbgfpoZnenGaVGBBWWgxgWTWuYRN2O0nBaj18CEe' WHERE username = 'admin';
UPDATE username SET password = '$2y$10$jZaN4ZwdhPLjt9Y4qnryHOAdqMhIlHdEiuq4vr/oLQGzeitcrB2BK' WHERE status = 'member';
```

### Step 4: Test Login
```bash
# Admin login
curl -X POST http://localhost/NOVA-Library/controllers/login.php \
  -d "username=admin&password=BismillaH97"

# Member login
curl -X POST http://localhost/NOVA-Library/controllers/login.php \
  -d "username=member1&password=pass123"
```

---

## 📁 Folder Structure
```
NOVA-Library/
├── config/
│   ├── database.php        # Database connection (PostgreSQL)
│   └── helpers.php         # Path & URL helper functions
├── controllers/
│   └── login.php           # Login logic & validation
├── models/
│   └── validateLogin.php   # Database query for user auth
├── views/
│   ├── login.php           # Login page (with relative paths)
│   ├── admin/
│   │   └── dashboard.php   # Admin dashboard
│   └── user/
│       └── dashboard.php   # User/Member dashboard
├── public/
│   ├── css/
│   │   └── login.css       # Login styling
│   ├── js/
│   │   └── login.js        # Login script
│   └── index.php           # Entry point
└── .htaccess.backup        # Disabled (for development)
```

---

## 🧪 Testing Credentials

### Admin Account
```
Username: admin
Password: BismillaH97
Status:   admin
```

### Member Accounts
```
Username: member1 - member9
Password: pass123
Status:   member
```

---

## ⚠️ Important Notes

### For Development
- `.htaccess` is disabled (renamed to `.htaccess.backup`)
- Direct path access works fine
- CSS/JS path menggunakan dynamic helper functions

### For Production
- Restore `.htaccess` files dari `.backup`
- Add proper URL rewriting rules
- Use environment variables untuk sensitive data
- Implement proper logging & error handling

---

## 🔐 Security Checklist

- [x] Password menggunakan bcrypt hashing
- [x] Username divalidasi dengan regex
- [x] Session management implemented
- [x] Error messages safe (tidak reveal DB details)
- [ ] CSRF token (TODO untuk production)
- [ ] Rate limiting (TODO untuk production)
- [ ] SQL injection protection via prepared statements ✓

