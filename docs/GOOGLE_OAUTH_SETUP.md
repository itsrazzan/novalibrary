# Google OAuth Login - Setup Guide

## ✅ **Implementation Complete!**

Semua file sudah dibuat dan siap digunakan. Tinggal update database dan test!

---

## 📁 **Files Created**

### **1. Database Schema**

- `add_google_oauth_columns.sql` - SQL script untuk update database

### **2. Backend (PHP)**

- `models/GoogleAuth.php` - Model untuk Google authentication
- `controllers/google-callback.php` - Controller untuk handle OAuth callback

### **3. Frontend (JavaScript)**

- `public/js/google-auth.js` - Handler untuk Google Sign-In button

### **4. Updated Files**

- `views/login.php` - Added Google Sign-In button

---

## 🚀 **Setup Instructions**

### **Step 1: Update Database Schema**

Jalankan SQL script untuk add kolom baru:

```bash
sudo -u postgres psql -d novalibrary -f /opt/lampp/htdocs/NOVA-Library/add_google_oauth_columns.sql
```

**Expected Output:**

```
ALTER TABLE
ALTER TABLE
ALTER TABLE
CREATE INDEX
CREATE INDEX
```

**Verify changes:**

```bash
sudo -u postgres psql -d novalibrary -c "\d username"
```

Pastikan ada kolom baru:

- `google_id` (varchar 255, nullable, unique)
- `auth_provider` (varchar 20, default 'local')
- `password` (varchar 255, **nullable**)

---

### **Step 2: Test Google Sign-In**

1. **Buka browser** dan akses login page:

   ```
   http://localhost:8000/NOVA-Library/views/login.php
   ```

2. **Klik tombol "Sign in with Google"**

3. **Pilih Google account** Anda

4. **Expected behavior:**
   - Jika user baru → Create account otomatis → Redirect ke user dashboard
   - Jika email sudah terdaftar → Link Google account → Login → Redirect ke dashboard
   - Jika sudah pernah login via Google → Direct login → Redirect ke dashboard

---

## 🔍 **Testing Scenarios**

### **Test 1: New User (First Time Google Login)**

**Steps:**

1. Login dengan Google account yang belum pernah signup
2. Check database:
   ```bash
   sudo -u postgres psql -d novalibrary -c "SELECT username, email, google_id, auth_provider FROM username WHERE email = 'your-email@gmail.com';"
   ```

**Expected:**

- ✅ New user created
- ✅ Username auto-generated dari email
- ✅ `google_id` filled
- ✅ `auth_provider` = 'google'
- ✅ `password` = NULL
- ✅ `status` = 'member'
- ✅ Redirected to user dashboard

---

### **Test 2: Existing User (Email Already Registered)**

**Steps:**

1. Signup via form biasa dengan email `test@gmail.com`
2. Login dengan Google menggunakan email yang sama
3. Check database:
   ```bash
   sudo -u postgres psql -d novalibrary -c "SELECT username, email, google_id, auth_provider FROM username WHERE email = 'test@gmail.com';"
   ```

**Expected:**

- ✅ Google account linked ke existing user
- ✅ `google_id` updated
- ✅ `auth_provider` changed to 'google'
- ✅ `password` tetap ada (dari signup sebelumnya)
- ✅ Redirected to dashboard

---

### **Test 3: Returning Google User**

**Steps:**

1. Login dengan Google account yang sudah pernah login sebelumnya
2. Check session:
   ```php
   var_dump($_SESSION);
   ```

**Expected:**

- ✅ Direct login (no account creation)
- ✅ Session set correctly
- ✅ Redirected to dashboard immediately

---

## 🐛 **Troubleshooting**

### **Error: "Google authentication failed. Invalid token"**

**Cause:** Token verification gagal

**Solution:**

1. Check internet connection (perlu akses ke Google API)
2. Verify Client ID di `models/GoogleAuth.php` line 14
3. Check Google Console - pastikan Client ID benar

---

### **Error: "Database connection failed"**

**Cause:** PostgreSQL tidak running atau config salah

**Solution:**

```bash
# Check PostgreSQL status
sudo systemctl status postgresql

# Restart jika perlu
sudo systemctl restart postgresql
```

---

### **Error: "Failed to create account"**

**Cause:** Database constraint violation atau error

**Solution:**

1. Check PostgreSQL logs:
   ```bash
   sudo tail -f /var/log/postgresql/postgresql-16-main.log
   ```
2. Verify database schema sudah di-update
3. Check duplicate username/email

---

### **Google Button Tidak Muncul**

**Cause:** Google Sign-In library belum load

**Solution:**

1. Check browser console untuk error
2. Verify internet connection
3. Clear browser cache
4. Check Client ID di `login.php` line 233

---

## 📊 **Database Schema Changes**

### **Before:**

```sql
username (
    id INTEGER,
    username VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,  -- NOT NULL
    status VARCHAR(100),
    name VARCHAR(100),
    email VARCHAR(100),
    phone_number VARCHAR(20)
)
```

### **After:**

```sql
username (
    id INTEGER,
    username VARCHAR(20) NOT NULL,
    password VARCHAR(255),              -- NULLABLE
    status VARCHAR(100),
    name VARCHAR(100),
    email VARCHAR(100),
    phone_number VARCHAR(20),
    google_id VARCHAR(255) UNIQUE,      -- NEW
    auth_provider VARCHAR(20) DEFAULT 'local'  -- NEW
)
```

---

## 🔐 **Security Features**

✅ **Token Verification** - Verify dengan Google API (tidak trust client)
✅ **Unique Constraints** - `google_id` unique untuk prevent duplicate
✅ **Session Management** - Same security dengan login biasa
✅ **Email Verification** - Google email sudah verified by Google
✅ **Auto-generated Username** - Unique username dari email

---

## 📝 **How It Works**

### **Flow Diagram:**

```
User clicks "Sign in with Google"
         ↓
Google Sign-In popup
         ↓
User selects account
         ↓
Google returns ID token
         ↓
JavaScript sends token to google-callback.php
         ↓
Verify token with Google API
         ↓
Extract user info (google_id, email, name)
         ↓
Check database:
  - google_id exists? → Login
  - email exists? → Link account + Login
  - New user? → Create account + Login
         ↓
Set session
         ↓
Redirect to dashboard
```

---

## 🎯 **Next Steps (Optional)**

1. **Add Google Sign-In to Register Page**

   - Copy Google button dari login.php
   - Same flow, same callback

2. **Add Profile Management**

   - Allow users to unlink Google account
   - Allow users to link multiple providers

3. **Add Login Analytics**
   - Track login method (local vs google)
   - Track user registration source

---

**Implementation Date:** December 27, 2025  
**Status:** ✅ Ready for Testing
