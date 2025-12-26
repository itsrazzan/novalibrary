# 📚 NOVA Library - Application Documentation

**Comprehensive guide untuk memahami aplikasi login & authentication NOVA Library.**

---

## 🎯 Quick Start

### Prerequisites
- PHP 8.2+ (LAMPP)
- PostgreSQL 16
- Linux/Unix system

### Setup (5 minutes)
```bash
# 1. Start services
sudo /opt/lampp/lampp start
sudo systemctl start postgresql

# 2. Test login
curl -X POST http://localhost/NOVA-Library/controllers/login.php \
  -d "username=admin&password=BismillaH97"

# Expected: HTTP 302 redirect to /NOVA-Library/views/admin/dashboard.php
```

### Test Credentials
```
Admin:  username=admin,     password=BismillaH97
Member: username=member1,   password=pass123
         (member1 - member9)
```

---

## 📁 Documentation Structure

### Main Files
- **SETUP_GUIDE.md** ← Start here!
  - Problem & solution summary
  - Installation instructions
  - Important notes & checklist

- **docs/CONTROLLERS.md**
  - Login controller logic (step-by-step)
  - Authentication flow
  - Security features

- **docs/MODELS.md**
  - Database query logic
  - loginCheck() function
  - Bcrypt password verification

- **docs/CONFIG.md**
  - Database connection
  - Helper functions (path management)
  - Dynamic URL generation

- **docs/VIEWS.md**
  - HTML form structure
  - Dashboard pages
  - Session checking
  - XSS prevention

---

## 🗂️ Folder Structure

```
NOVA-Library/
│
├── config/
│   ├── database.php          ← PostgreSQL connection
│   └── helpers.php           ← Path & URL helpers
│
├── controllers/
│   └── login.php             ← Login logic & redirect
│
├── models/
│   └── validateLogin.php     ← Database query for auth
│
├── views/
│   ├── login.php             ← Login form page
│   ├── admin/
│   │   └── dashboard.php     ← Admin dashboard
│   └── user/
│       └── dashboard.php     ← Member/User dashboard
│
├── public/
│   ├── css/
│   │   └── login.css
│   ├── js/
│   │   └── login.js
│   └── index.php
│
├── SETUP_GUIDE.md            ← Setup & troubleshooting
└── docs/
    ├── CONTROLLERS.md        ← Controller documentation
    ├── MODELS.md             ← Model documentation
    ├── CONFIG.md             ← Config documentation
    └── VIEWS.md              ← View documentation
```

---

## 🔐 Authentication Flow

```
User submits login form
    ↓
POST /controllers/login.php
    ↓
✓ Validate input (format, not empty)
    ↓
✓ Query database for user
    ↓
✓ Verify password dengan bcrypt
    ↓
✓ Set session variables
    ↓
→ Redirect to dashboard (admin/user)
```

**Security Layers:**
1. **Input Validation** - Format check, empty check
2. **Input Sanitization** - htmlspecialchars(), trim()
3. **Database Security** - Prepared statements, PDO
4. **Password Security** - Bcrypt hashing, password_verify()
5. **Session Management** - Session variables, role checking
6. **Access Control** - Dashboard check session before display

---

## 📊 Database Schema

### Username Table
```sql
CREATE TABLE username (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,      -- Bcrypt hashed
    status VARCHAR(20),                  -- 'admin' or 'member'
    name VARCHAR(100),
    email VARCHAR(100),
    phone_number VARCHAR(15)
);
```

### Authentication Function
```sql
CREATE FUNCTION get_user_for_auth(p_username varchar)
RETURNS TABLE(id, username, hashed_password, status)
LANGUAGE plpgsql
AS $function$
BEGIN
    RETURN QUERY
    SELECT u.id, u.username, u.password, u.status
    FROM username u
    WHERE u.username = p_username;
END;
$function$;
```

---

## 🔄 File Communication Flow

```
┌─────────────────┐
│ Browser/Client  │
└────────┬────────┘
         │
         │ POST /controllers/login.php
         ↓
┌──────────────────────────┐
│ controllers/login.php    │
├──────────────────────────┤
│ - Validate input         │
│ - Call loginCheck()      │
│ - Set session            │
│ - Redirect               │
└────────┬────────────────┘
         │
         │ require_once
         ↓
┌──────────────────────────┐
│ models/validateLogin.php │
├──────────────────────────┤
│ - Query database         │
│ - Verify password        │
│ - Return user data       │
└────────┬────────────────┘
         │
         │ require_once
         ↓
┌──────────────────────────┐
│ config/database.php      │
├──────────────────────────┤
│ - Create PDO connection  │
│ - Return connection      │
└────────┬────────────────┘
         │
         │ Connect to
         ↓
┌──────────────────────────┐
│ PostgreSQL Database      │
└──────────────────────────┘

After authentication:
         ↓ Redirect
┌──────────────────────────┐
│ views/admin/dashboard    │
│ OR                       │
│ views/user/dashboard     │
└──────────────────────────┘
```

---

## 🚀 Key Features

### ✓ Implemented
- [x] User authentication (login)
- [x] Password hashing (bcrypt)
- [x] Role-based redirect (admin/user)
- [x] Session management
- [x] Error messages
- [x] Input validation
- [x] SQL injection prevention (prepared statements)
- [x] XSS prevention
- [x] Dynamic path generation (multi-environment support)

### ⚠️ Not Implemented (For Learning)
- [ ] CSRF token validation
- [ ] Rate limiting (brute force protection)
- [ ] Account lockout
- [ ] Login logging
- [ ] Two-factor authentication (2FA)
- [ ] Password reset functionality
- [ ] Remember me functionality
- [ ] Social login

---

## 🔍 Code Examples

### Example 1: Login as Admin
```bash
curl -X POST http://localhost/NOVA-Library/controllers/login.php \
  -d "username=admin&password=BismillaH97" \
  -L

# Response: HTTP 302 → 200 OK (admin dashboard)
```

### Example 2: Login as Member
```bash
curl -X POST http://localhost/NOVA-Library/controllers/login.php \
  -d "username=member1&password=pass123" \
  -c cookies.txt \
  -L

# Response: HTTP 302 → 200 OK (user dashboard)
# Session stored in cookies.txt
```

### Example 3: Test Wrong Password
```bash
curl -X POST http://localhost/NOVA-Library/controllers/login.php \
  -d "username=admin&password=wrongpass" \
  -L

# Response: HTTP 302 → 200 OK (back to login with error message)
```

### Example 4: Direct Dashboard Access (No Login)
```bash
curl http://localhost/NOVA-Library/views/admin/dashboard.php

# Response: HTTP 302 redirect to login.php
# (Unauthorized access prevented)
```

---

## 🐛 Troubleshooting

### Problem: Database connection error
```
Error: SQLSTATE[08006] connection failed
```
**Solution:**
1. Check PostgreSQL running: `sudo systemctl status postgresql`
2. Check socket path: `ls -la /var/run/postgresql/`
3. Verify user exists: `sudo -u postgres psql -c "\du"`
4. Reset auth: Change pg_hba.conf to scram-sha-256

See **SETUP_GUIDE.md** for detailed steps.

### Problem: CSS/JS not loading
```
HTTP 404: /NOVA-Library/public/css/login.css not found
```
**Solution:**
1. Disable .htaccess: `mv .htaccess .htaccess.backup`
2. Check file exists: `ls -la public/css/login.css`
3. Use helpers: `getAssetUrl('public/css/login.css')`

### Problem: Login not redirecting
```
POST returns 500 error or stuck at controller
```
**Solution:**
1. Disable .htaccess files
2. Check error_log: `tail /opt/lampp/logs/error_log`
3. Verify all require_once paths use __DIR__

---

## 📈 Learning Path

**Recommended reading order:**

1. **SETUP_GUIDE.md** (5 min)
   - Understand problems & solutions
   - Setup database

2. **docs/CONFIG.md** (10 min)
   - Database connection
   - Path helpers

3. **docs/MODELS.md** (15 min)
   - Database queries
   - Bcrypt password verification

4. **docs/CONTROLLERS.md** (20 min)
   - Authentication flow
   - Input validation & sanitization

5. **docs/VIEWS.md** (10 min)
   - HTML forms
   - Session usage
   - XSS prevention

6. **Read actual code** (30 min)
   - Open files & read alongside documentation
   - Trace through one login attempt

---

## 🔒 Security Checklist

### Input Security
- [x] Validation (format check)
- [x] Sanitization (htmlspecialchars)
- [x] Trimming (remove whitespace)

### Database Security
- [x] Prepared statements (SQL injection prevention)
- [x] PDO with exception handling
- [x] Parameter binding

### Password Security
- [x] Bcrypt hashing (strong algorithm)
- [x] password_verify() (time-safe comparison)
- [x] Salt included (prevents rainbow tables)

### Session Security
- [x] Session variables (after authentication)
- [x] Role-based access (check before dashboard)
- [x] Generic error messages (prevent user enumeration)
- [ ] HttpOnly cookies (TODO)
- [ ] Secure flag (TODO)
- [ ] Session timeout (TODO)

### Development
- [x] .htaccess disabled (for development)
- [ ] Environment variables (TODO for production)
- [ ] Error logging (TODO)
- [ ] CSRF protection (TODO)

---

## 📚 Additional Resources

### PostgreSQL
- Unix socket connections: More secure than TCP
- Function usage: Encapsulate database logic
- PDO/prepared statements: Prevent SQL injection

### PHP Security
- password_hash() / password_verify(): Modern password handling
- htmlspecialchars(): XSS prevention
- Session management: Keep state between requests

### Web Development
- HTTP status codes: 200 (OK), 302 (redirect), 500 (error)
- Form submission: GET vs POST
- Client-side validation: User experience
- Server-side validation: Security (always required)

---

## 🎓 Study Tips

1. **Trace Code Execution** - Follow one login from form → database
2. **Test Different Scenarios** - Right password, wrong password, user not found
3. **Break It On Purpose** - Remove validation, remove password check, etc.
4. **Read Error Messages** - They tell you what's wrong
5. **Use Browser Dev Tools** - Network tab to see requests/redirects
6. **Add Debug Statements** - echo, var_dump() untuk understand flow

---

## ✍️ Notes for Future

### Database Improvements
- [ ] User role management (admin panel)
- [ ] Password change functionality
- [ ] User profile information
- [ ] Login history tracking
- [ ] Account recovery email

### Features to Add
- [ ] Remember me functionality
- [ ] Social login (Google, GitHub)
- [ ] Two-factor authentication
- [ ] Email verification
- [ ] Permission system (beyond roles)

### Production Hardening
- [ ] Environment variables (.env file)
- [ ] Error logging (file, database, sentry)
- [ ] Rate limiting
- [ ] CSRF token generation
- [ ] HTTPS enforcement
- [ ] Security headers (CSP, X-Frame-Options)
- [ ] Database backups
- [ ] Log rotation

---

## 📞 Support & Questions

For understanding:
1. Check relevant documentation file (CONTROLLERS, MODELS, CONFIG, VIEWS)
2. Look at code comments & examples
3. Run test curl commands to see flow
4. Add debug statements to trace execution

---

**Last Updated:** December 26, 2025
**Environment:** LAMPP + PostgreSQL 16 (Linux)
**PHP Version:** 8.2.4

