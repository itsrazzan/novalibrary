# 📖 NOVA Library - Documentation Index

## Quick Navigation

### 🚀 Start Here
- **[README.md](README.md)** - Main documentation, quick start, troubleshooting
- **[SETUP_GUIDE.md](SETUP_GUIDE.md)** - Problems, solutions, database setup

---

## 📚 Detailed Documentation by Folder

### 🔧 CONFIG Folder
**File:** [docs/CONFIG.md](docs/CONFIG.md)

**Contains:**
- `database.php` - PostgreSQL connection with PDO
- `helpers.php` - Dynamic path management

**Key Concepts:**
- Database connection (unix socket)
- Path detection (localhost vs localhost/NOVA-Library)
- URL generation functions

**Read this if you want to:**
- Understand database connection
- Learn how path helpers work
- See multi-environment support

---

### 🔐 CONTROLLERS Folder
**File:** [docs/CONTROLLERS.md](docs/CONTROLLERS.md)

**Contains:**
- `login.php` - Main authentication controller

**Key Concepts:**
- HTTP request handling (POST)
- Input validation & sanitization
- Database authentication
- Session management
- Role-based redirect

**Read this if you want to:**
- Understand login flow
- See input validation in action
- Learn about security layers
- Understand session variables

---

### 🗄️ MODELS Folder
**File:** [docs/MODELS.md](docs/MODELS.md)

**Contains:**
- `validateLogin.php` - Database query & password verification

**Key Concepts:**
- Prepared statements (SQL injection prevention)
- Bcrypt password hashing
- password_verify() function
- Database function usage

**Read this if you want to:**
- Understand password hashing
- Learn about prepared statements
- See database query logic
- Understand password verification process

---

### 🎨 VIEWS Folder
**File:** [docs/VIEWS.md](docs/VIEWS.md)

**Contains:**
- `login.php` - Login form page
- `admin/dashboard.php` - Admin dashboard
- `user/dashboard.php` - User dashboard

**Key Concepts:**
- HTML form structure
- Session variable usage
- Error message display
- Access control on dashboard
- XSS prevention

**Read this if you want to:**
- Understand HTML form submission
- See session checking
- Learn dashboard access control
- Understand dynamic path usage in views

---

## 🔄 Reading Order (Recommended)

### For Complete Understanding (1-2 hours)
1. **README.md** - Overview & quick start (10 min)
2. **SETUP_GUIDE.md** - Learn what was fixed (10 min)
3. **docs/CONFIG.md** - Connection & helpers (15 min)
4. **docs/MODELS.md** - Database layer (20 min)
5. **docs/CONTROLLERS.md** - Business logic (25 min)
6. **docs/VIEWS.md** - Presentation layer (15 min)

### For Quick Reference (15 minutes)
1. **README.md** - Skim the flow diagram
2. Jump to specific file's documentation based on interest

### For Specific Topics

**"How does login work?"**
→ CONTROLLERS.md + MODELS.md

**"How is password stored safely?"**
→ MODELS.md (bcrypt section)

**"How are paths generated dynamically?"**
→ CONFIG.md (helpers section)

**"How do error messages appear?"**
→ VIEWS.md + CONTROLLERS.md

**"How is SQL injection prevented?"**
→ MODELS.md (prepared statements)

---

## 📊 Documentation Structure Map

```
README.md (Start here!)
    ├─ Overview
    ├─ Quick Start
    ├─ Authentication Flow
    ├─ Database Schema
    ├─ File Communication
    ├─ Security Checklist
    └─ Troubleshooting

SETUP_GUIDE.md (Problems & Solutions)
    ├─ Path Include Error → Solution
    ├─ PDO PostgreSQL Driver → Solution
    ├─ Login Not Redirecting → Solution
    ├─ CSS/JS Path Not Working → Solution
    ├─ Member Login Failed → Solution
    ├─ Setup Instructions
    └─ Testing Credentials

docs/CONFIG.md (Connection & Helpers)
    ├─ database.php
    │  ├─ PDO Connection
    │  └─ Unix Socket
    ├─ helpers.php
    │  ├─ getBasePath()
    │  ├─ getRedirectUrl()
    │  ├─ getAssetUrl()
    │  └─ getBaseUrl()
    └─ Debugging Tips

docs/MODELS.md (Database Layer)
    ├─ validateLogin.php
    │  ├─ loginCheck() function
    │  ├─ Prepared Statements
    │  ├─ Bcrypt Verification
    │  └─ Error Handling
    └─ Security Analysis

docs/CONTROLLERS.md (Business Logic)
    ├─ login.php
    │  ├─ Input Validation
    │  ├─ Input Sanitization
    │  ├─ Database Authentication
    │  ├─ Session Management
    │  ├─ Role-Based Redirect
    │  └─ Error Handling
    └─ Security Features

docs/VIEWS.md (Presentation Layer)
    ├─ login.php
    │  ├─ HTML Form
    │  ├─ Error Display
    │  ├─ CSS & Styling
    │  └─ JavaScript
    ├─ admin/dashboard.php
    │  └─ Access Control
    └─ user/dashboard.php
       └─ Access Control
```

---

## 🎯 By Learning Style

### Visual Learner
→ Read the flow diagrams in README.md
→ Look at file communication flow in CONTROLLERS.md

### Want Code Examples
→ Jump to testing examples in each doc file
→ Check SETUP_GUIDE.md for curl commands

### Want Deep Understanding
→ Read all of MODELS.md (most detailed)
→ Read all of CONTROLLERS.md (step-by-step)

### Just Want to Get It Working
→ Read SETUP_GUIDE.md
→ Skim the file names in CONFIG/MODELS/CONTROLLERS/VIEWS

---

## 📋 File List

### Root Level
- **README.md** ← START HERE
- **SETUP_GUIDE.md** ← Problems & solutions
- **INDEX.md** ← You are here

### docs/ Folder
- **docs/CONFIG.md** ← Database & path helpers
- **docs/MODELS.md** ← Database queries
- **docs/CONTROLLERS.md** ← Login logic
- **docs/VIEWS.md** ← HTML & forms

### Source Code
- `config/database.php` - Documented in docs/CONFIG.md
- `config/helpers.php` - Documented in docs/CONFIG.md
- `models/validateLogin.php` - Documented in docs/MODELS.md
- `controllers/login.php` - Documented in docs/CONTROLLERS.md
- `views/login.php` - Documented in docs/VIEWS.md
- `views/admin/dashboard.php` - Documented in docs/VIEWS.md
- `views/user/dashboard.php` - Documented in docs/VIEWS.md

---

## 🔍 Search by Keyword

### Authentication
- SETUP_GUIDE.md → "Problem 5: Member Login Failed"
- docs/CONTROLLERS.md → "Main Authentication Logic"
- docs/MODELS.md → "Main Function: loginCheck()"

### Password Security
- docs/MODELS.md → "Verify Password dengan Bcrypt"
- docs/MODELS.md → "Why Bcrypt?"
- SETUP_GUIDE.md → "Step 3: Create Function & Data"

### Path Management
- docs/CONFIG.md → "helpers.php section"
- SETUP_GUIDE.md → "Problem 4: CSS/JS Path Not Working"
- docs/VIEWS.md → "Dynamic Paths"

### Error Handling
- docs/CONTROLLERS.md → "Step 8: Handle Login Failed"
- docs/MODELS.md → "Step 8: Error Handling"
- docs/VIEWS.md → "Error Message Display"

### SQL Injection Prevention
- docs/MODELS.md → "Prepared Statement Security"
- docs/MODELS.md → "Step 2: Build Query"

### XSS Prevention
- docs/VIEWS.md → "XSS Prevention section"
- docs/CONTROLLERS.md → "Input Sanitization"

### Session Management
- docs/CONTROLLERS.md → "Session Management"
- docs/VIEWS.md → "Session Check in Dashboard"

---

## 🎓 Learning Objectives

After reading all documentation, you should understand:

**Concepts**
- [ ] How web authentication works
- [ ] Why password hashing is important
- [ ] Difference between client-side & server-side validation
- [ ] What is SQL injection & how to prevent it
- [ ] What is XSS & how to prevent it
- [ ] Session vs cookies
- [ ] HTTP redirects
- [ ] Role-based access control

**Code Patterns**
- [ ] PDO prepared statements
- [ ] Bcrypt password hashing/verification
- [ ] Session variables
- [ ] htmlspecialchars() sanitization
- [ ] Dynamic path generation
- [ ] Error message handling

**Security Practices**
- [ ] Input validation (format check)
- [ ] Input sanitization (encode harmful chars)
- [ ] Prepared statements (prevent SQL injection)
- [ ] Password hashing (bcrypt)
- [ ] Generic error messages
- [ ] Session checking before displaying data

---

## ❓ FAQ

**Q: Where do I start reading?**
A: Start with README.md, then SETUP_GUIDE.md

**Q: How long does it take to understand everything?**
A: 1-2 hours for complete understanding, 15 min for quick overview

**Q: Can I just read one file?**
A: Yes! Each file is standalone. Look at the "Search by Keyword" section.

**Q: What if I want to modify the code?**
A: After understanding the flow from docs, you can confidently modify any part.

**Q: Where are the actual source files?**
A: They're not in docs/ - look for `config/`, `controllers/`, `models/`, `views/` folders

**Q: How do I debug an issue?**
A: See Troubleshooting section in README.md or SETUP_GUIDE.md

---

## 📞 Notes

- All documentation was created December 26, 2025
- Written for LAMPP + PostgreSQL 16 setup
- Designed for learning purposes (not production)
- Includes security concepts & best practices
- Code examples use curl for testing

**Last Updated:** December 26, 2025

