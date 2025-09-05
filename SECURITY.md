# Security Implementation Guide

This document outlines the comprehensive security measures implemented in the Asset Tracker application to protect sensitive information.

## 🔐 Security Features Implemented

### 1. Environment Variable Encryption
- **Configuration**: `config/security.php`
- **Features**:
  - Encrypted environment variables
  - Secure key management
  - Previous key support for key rotation

### 2. Database Field Encryption
- **Trait**: `App\Traits\EncryptsAttributes`
- **Encrypted Fields**:
  - User: email, phone, address, 2FA secrets
  - BankAccount: account_number, routing_number, swift_code
  - Person: email, tfn, phone, address, identification numbers
- **Implementation**: Automatic encryption/decryption on save/retrieve

### 3. File Storage Encryption
- **Driver**: `App\Filesystem\EncryptedFilesystemAdapter`
- **Features**:
  - Transparent file encryption
  - Encrypted disk configuration
  - Secure file uploads

### 4. Enhanced Authentication Security
- **Two-Factor Authentication**: `App\Services\TwoFactorService`
- **Password Security**: Strong password requirements
- **Rate Limiting**: Protection against brute force attacks
- **Session Security**: Encrypted sessions with secure cookies

### 5. Security Headers
- **Middleware**: `App\Http\Middleware\SecurityHeaders`
- **Headers Implemented**:
  - X-Content-Type-Options: nosniff
  - X-Frame-Options: DENY
  - X-XSS-Protection: 1; mode=block
  - Content-Security-Policy
  - Strict-Transport-Security (HSTS)
  - Referrer-Policy

### 6. Encrypted Backup System
- **Commands**:
  - `php artisan backup:encrypted` - Create encrypted backup
  - `php artisan backup:restore` - Restore from backup
  - `php artisan backup:schedule` - Schedule automated backups
- **Features**:
  - Full application backup with encryption
  - Database, files, and configuration backup
  - Automated cleanup of old backups
  - Compression support

## 🛡️ Security Configuration

### Environment Variables
Create a `.env` file with the following security settings:

```env
# Application Security
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your-32-character-key

# Encryption
ENCRYPTION_KEY=your-encryption-key
DB_ENCRYPTION_KEY=your-db-encryption-key
BACKUP_ENCRYPTION_KEY=your-backup-encryption-key

# Session Security
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict

# Security Headers
SECURE_HEADERS=true
FORCE_HTTPS=true

# Rate Limiting
RATE_LIMIT_ENABLED=true
RATE_LIMIT_ATTEMPTS=5
RATE_LIMIT_DECAY_MINUTES=15

# Password Security
PASSWORD_MIN_LENGTH=12
PASSWORD_REQUIRE_SPECIAL_CHARS=true
PASSWORD_REQUIRE_NUMBERS=true
PASSWORD_REQUIRE_UPPERCASE=true
PASSWORD_REQUIRE_LOWERCASE=true

# Two-Factor Authentication
TWO_FA_ISSUER="Asset Tracker"
```

## 🔧 Setup Instructions

### 1. Install Dependencies
```bash
composer install
```

### 2. Generate Application Key
```bash
php artisan key:generate
```

### 3. Generate Encryption Keys
```bash
# Generate additional encryption keys
php artisan key:generate --show
# Use the output for ENCRYPTION_KEY, DB_ENCRYPTION_KEY, BACKUP_ENCRYPTION_KEY
```

### 4. Run Migrations
```bash
php artisan migrate
```

### 5. Set Up File Permissions
```bash
# Set secure permissions
chmod 755 storage
chmod 755 bootstrap/cache
chmod 644 .env
```

### 6. Configure Web Server
- Enable HTTPS/SSL
- Set up proper security headers
- Configure firewall rules

## 🔍 Security Audit

### Run Security Audit
```bash
php artisan security:audit
```

### Fix Security Issues
```bash
php artisan security:audit --fix
```

### Detailed Security Report
```bash
php artisan security:audit --detailed
```

## 📋 Backup and Recovery

### Create Manual Backup
```bash
php artisan backup:encrypted --compress
```

### Restore from Backup
```bash
php artisan backup:restore backup_file.zip
```

### Schedule Automated Backups
```bash
php artisan backup:schedule
```

## 🚨 Security Best Practices

### 1. Regular Security Updates
- Keep Laravel and dependencies updated
- Monitor security advisories
- Apply security patches promptly

### 2. Access Control
- Use strong, unique passwords
- Enable 2FA for all users
- Implement role-based access control
- Regular access reviews

### 3. Monitoring and Logging
- Monitor failed login attempts
- Log security events
- Set up alerts for suspicious activity
- Regular security audits

### 4. Data Protection
- Encrypt sensitive data at rest
- Use HTTPS for all communications
- Implement data retention policies
- Regular backup testing

### 5. Network Security
- Use firewalls
- Implement VPN for remote access
- Regular network security scans
- Keep network infrastructure updated

## 🔐 Encryption Details

### Database Encryption
- Uses Laravel's built-in encryption
- AES-256-CBC cipher
- Automatic encryption/decryption
- Supports key rotation

### File Encryption
- Custom encrypted filesystem driver
- Transparent encryption/decryption
- Secure file upload handling
- Malware scanning capability

### Backup Encryption
- Full application backup
- Encrypted archives
- Secure key management
- Automated cleanup

## 📞 Security Support

For security-related issues or questions:
1. Run security audit: `php artisan security:audit`
2. Check logs in `storage/logs/`
3. Review configuration in `config/security.php`
4. Contact system administrator

## ⚠️ Important Notes

- **Never commit sensitive keys to version control**
- **Use strong, unique encryption keys**
- **Regularly rotate encryption keys**
- **Test backup and recovery procedures**
- **Monitor security logs regularly**
- **Keep security documentation updated**

## 🔄 Key Rotation

To rotate encryption keys:
1. Generate new keys
2. Update environment variables
3. Re-encrypt existing data
4. Test application functionality
5. Update backup procedures

This security implementation provides comprehensive protection for your sensitive asset tracking data while maintaining usability and performance.
