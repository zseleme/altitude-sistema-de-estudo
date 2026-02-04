# Security Fixes Applied - 2026-01-19

This document summarizes the critical security fixes applied to the Altitude Sistema de Estudo project.

## Critical Issues Fixed

### 1. ✅ Weak Encryption Key Generation (CRITICAL)

**File:** `includes/encryption_helper.php`

**Problem:** The encryption key fallback used predictable sources (file metadata, server info) that could be guessed by attackers, compromising all encrypted API keys.

**Solution:**
- Removed all predictable fallback key generation
- Implemented secure key loading from:
  1. Environment variable `ENCRYPTION_KEY` (production)
  2. Key file `config/encryption.key` (development/shared hosting)
- Auto-generates secure key in development environments only
- Throws exception in production if key is not configured
- Added `.gitignore` entry for `config/encryption.key`

**Action Required:**
```bash
# For production deployment, set environment variable:
export ENCRYPTION_KEY="$(php -r 'echo bin2hex(random_bytes(32));')"

# OR create a key file (for shared hosting):
php -r 'file_put_contents("config/encryption.key", bin2hex(random_bytes(32)));'
chmod 600 config/encryption.key
```

---

### 2. ✅ Missing CSRF Protection on Admin Operations (CRITICAL)

**File:** `admin/usuarios.php`

**Problem:** User deletion and permission changes used GET requests without CSRF protection, allowing attackers to craft malicious links.

**Solution:**
- Converted all state-changing operations from GET to POST
- Added CSRF token validation to all admin operations:
  - User creation
  - User deletion
  - Permission changes (toggle admin)
- Updated frontend to use POST forms with CSRF tokens
- Added confirmation dialogs for destructive actions

**Action Required:** None - changes are automatic

---

### 3. ✅ Command Injection Risk in Database Backup (CRITICAL)

**File:** `admin/database.php`

**Problem:** PostgreSQL backup/restore used shell commands with potentially unsafe path handling and password exposure via `putenv()`.

**Solution:**
- Added filename validation with regex whitelist (`[a-zA-Z0-9_-]`)
- Validated temp directory accessibility
- Replaced `putenv('PGPASSWORD')` with secure `PGPASSFILE` method
- All shell arguments properly escaped with `escapeshellarg()`
- Added file size validation (100MB max for restore)
- Immediate cleanup of temporary password files

**Action Required:** None - changes are automatic

---

### 4. ✅ Unsafe exec() Usage (HIGH)

**Files:**
- `setup_postgres.php`
- `config/database.example.php`
- `admin/configuracoes_banco.php`

**Problem:** Schema names used in SQL without validation, potential SQL injection risk.

**Solution:**
- Added regex validation for all schema names: `/^[a-zA-Z_][a-zA-Z0-9_]*$/`
- Used `PDO::quote()` for schema names in CREATE SCHEMA statements
- Validates before using in `SET search_path`
- Throws exception for invalid schema names

**Action Required:** None - defensive validation added

---

### 5. ✅ Weak Password Requirements (HIGH)

**File:** `admin/usuarios.php`

**Problem:** Minimum password length of 6 characters is far too weak.

**Solution:**
- Increased minimum password length from 6 to 12 characters
- Updated both backend validation and frontend HTML5 validation
- Updated user-facing messages

**Action Required:**
- Inform existing users about new password policy
- Consider implementing password strength meter
- Consider checking against common password lists

---

## Security Improvements Summary

| Issue | Severity | Status | Files Changed |
|-------|----------|--------|---------------|
| Weak encryption keys | CRITICAL | ✅ Fixed | `includes/encryption_helper.php`, `.gitignore` |
| CSRF on admin ops | CRITICAL | ✅ Fixed | `admin/usuarios.php` |
| Command injection | CRITICAL | ✅ Fixed | `admin/database.php` |
| Unsafe exec() | HIGH | ✅ Fixed | `setup_postgres.php`, `config/database.example.php`, `admin/configuracoes_banco.php` |
| Weak passwords | HIGH | ✅ Fixed | `admin/usuarios.php` |

---

## Deployment Checklist

### Before Deploying to Production:

1. **Set Encryption Key** (REQUIRED):
   ```bash
   # Generate and set as environment variable
   export ENCRYPTION_KEY="$(php -r 'echo bin2hex(random_bytes(32));')"

   # Make it permanent (add to .bashrc, .profile, or use hosting control panel)
   echo 'export ENCRYPTION_KEY="your-generated-key-here"' >> ~/.bashrc
   ```

2. **Verify CSRF Protection**:
   - Test admin user management operations
   - Ensure forms have CSRF tokens
   - Check JavaScript console for errors

3. **Update Existing Database** (if you have existing database.php):
   ```bash
   # The system will auto-generate encryption.key in development
   # For production, ensure ENCRYPTION_KEY environment variable is set
   ```

4. **Password Policy Communication**:
   - Notify users about new 12-character minimum
   - Existing passwords are grandfathered (not affected)
   - New passwords and password changes require 12+ characters

---

## Testing Recommendations

1. **Test Encryption Key**:
   ```bash
   # Should work in development (auto-generates key)
   php -r "require 'includes/encryption_helper.php'; echo 'OK';"
   ```

2. **Test CSRF Protection**:
   - Try admin operations without reloading page
   - Verify CSRF token expiry (1 hour)
   - Test with different browsers/sessions

3. **Test Database Backup**:
   - Download backup (SQLite or PostgreSQL)
   - Verify file integrity
   - Test restore functionality

---

## Additional Recommendations

While the critical issues are fixed, consider these additional security improvements:

1. **High Priority**:
   - Implement two-factor authentication for admin accounts
   - Add security audit logging (login attempts, admin actions)
   - Monitor failed login attempts (brute force protection)
   - Implement password strength meter

2. **Medium Priority**:
   - Tighten CSP headers (remove `unsafe-inline`, `unsafe-eval`)
   - Add privacy notice for AI feature usage
   - Implement request size limits
   - Add file upload validation (if applicable)

3. **Long Term**:
   - Regular security audits
   - Penetration testing
   - Automated security scanning (PHPStan, Psalm)
   - Bug bounty program

---

## Support

If you encounter any issues with these security fixes:

1. Check the error logs: `error_log` in PHP
2. Verify encryption key is set correctly
3. Clear browser cache and cookies
4. Test in incognito/private browsing mode

For deployment-specific issues, refer to:
- `CLAUDE.md` - Project documentation
- `.github/workflows/ftp-deploy.yml` - Deployment configuration

---

## Security Contact

For security vulnerabilities, please report privately through GitHub Security Advisories or contact the project maintainers directly.

**Do not** disclose security issues publicly until they are fixed.
