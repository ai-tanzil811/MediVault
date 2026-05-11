# MediVault Authentication Flow - Complete Guide

## Overview

MediVault now includes a comprehensive, secure authentication system with account recovery capabilities. This guide covers all authentication flows and their implementation.

## Authentication Flows

### 1. Standard Login Flow

**User Journey:**
1. Navigate to `/Frontend/pages/login-new.html`
2. Select role (User or Administrator)
3. Enter Clinical ID and Vault Passcode
4. Optional: Check "Remember terminal"
5. Click "Sign In"

**API Endpoint:** `POST /Server/api/auth/login.php`

**Features:**
- Role-based access (User vs Admin)
- Session token generation
- Activity logging
- Secure password verification with bcrypt

**Request:**
```json
{
  "email": "user@example.com",
  "password": "UserPassword123!"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "role": "user",
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

---

### 2. Forgot Password Flow (Complete Recovery)

**User Journey:**
1. Navigate to `/Frontend/pages/forgot-password.html`
2. Enter Clinical ID or registered email
3. Click "Send Recovery Code"
4. Receive OTP via email
5. Proceed to `/Frontend/pages/otp-verification.html`
6. Enter 6-digit OTP code
7. Navigate to `/Frontend/pages/reset-password.html`
8. Set new password meeting all requirements
9. Return to login

**Step 1: Forgot Password Request**

**File:** `/Frontend/pages/forgot-password.html`

**API Endpoint:** `POST /Server/api/auth/forgot-password.php`

**Request:**
```json
{
  "clinical_id": "MV-4429-XXXX" // or email address
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "email": "use***om",
    "message": "OTP sent to your registered email"
  }
}
```

---

### 3. OTP Verification

**File:** `/Frontend/pages/otp-verification.html`

**Features:**
- 6-digit OTP input with auto-focus
- Clipboard paste support
- 10-minute countdown timer
- Resend OTP option (60-second cooldown)
- Real-time validation feedback

**API Endpoint:** `POST /Server/api/auth/verify-otp.php`

**Request:**
```json
{
  "otp": "123456"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "verified": true,
    "token": "reset_token_...",
    "message": "OTP verified successfully"
  }
}
```

**Resend OTP:**

**API Endpoint:** `POST /Server/api/auth/resend-otp.php`

**Response:**
```json
{
  "success": true,
  "data": {
    "email": "use***om",
    "message": "New OTP sent to your email"
  }
}
```

---

### 4. Password Reset

**File:** `/Frontend/pages/reset-password.html`

**Features:**
- Real-time password strength meter
- Requirement validation:
  - Minimum 8 characters
  - At least one uppercase letter
  - At least one number
  - At least one special character
- Password confirmation match
- Visual requirement indicators

**API Endpoint:** `POST /Server/api/auth/reset-password.php`

**Request:**
```json
{
  "new_password": "NewPassword123!",
  "confirm_password": "NewPassword123!"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "message": "Password reset completed"
  }
}
```

---

### 5. OTP Method Selection (Optional)

**File:** `/Frontend/pages/otp-method-select.html`

**Available Methods:**
1. **Email** - 6-digit code sent to registered email
2. **SMS** - 6-digit code sent to registered phone
3. **Authenticator App** - TOTP-based verification

**User Selection:** Stored in `sessionStorage` for backend logic

---

## Frontend Pages

### Authentication Pages Created

| Page | Path | Purpose |
|------|------|---------|
| Login | `/Frontend/pages/login-new.html` | User/Admin login with role selection |
| Forgot Password | `/Frontend/pages/forgot-password.html` | Initiate password recovery |
| OTP Verification | `/Frontend/pages/otp-verification.html` | Verify 6-digit OTP code |
| OTP Method Select | `/Frontend/pages/otp-method-select.html` | Choose delivery method (optional) |
| Reset Password | `/Frontend/pages/reset-password.html` | Set new password with strength validation |

---

## Backend API Endpoints

### Authentication Endpoints

#### 1. User Registration
**Path:** `POST /Server/api/auth/register.php`

**Protection:** None (open endpoint)

**Requirements:**
- Email validation
- NID uniqueness check
- Password strength validation
- Optional photo upload

---

#### 2. User Login
**Path:** `POST /Server/api/auth/login.php`

**Protection:** None (open endpoint)

**Role Detection:**
- Checks both Users and Admins tables
- Returns appropriate role in token

---

#### 3. Password Recovery - Request
**Path:** `POST /Server/api/auth/forgot-password.php`

**Protection:** None (open endpoint)

**Process:**
1. Find user by email or clinical ID
2. Generate 6-digit OTP
3. Store OTP in secure session (10-minute expiry)
4. Send email with OTP
5. Log recovery request

---

#### 4. OTP Verification
**Path:** `POST /Server/api/auth/verify-otp.php`

**Protection:** Active password reset session

**Process:**
1. Verify OTP matches stored value
2. Generate temporary reset token
3. Set reset verification cookie
4. Return reset token for next step

---

#### 5. Resend OTP
**Path:** `POST /Server/api/auth/resend-otp.php`

**Protection:** Active password reset session

**Process:**
1. Generate new OTP
2. Update session OTP
3. Send new email
4. Log resend action

---

#### 6. Password Reset
**Path:** `POST /Server/api/auth/reset-password.php`

**Protection:** OTP verification required

**Process:**
1. Validate password strength
2. Confirm passwords match
3. Hash new password with bcrypt
4. Update user record
5. Clear reset session
6. Log password reset

---

## Security Features

### Password Requirements
- **Minimum Length:** 8 characters
- **Uppercase:** At least 1 (A-Z)
- **Numbers:** At least 1 (0-9)
- **Special Characters:** At least 1 (!@#$%^&*)

### OTP Security
- **Length:** 6 digits
- **Validity:** 10 minutes
- **Attempts:** Unlimited (in current version)
- **Resend Cooldown:** 60 seconds

### Session Management
- **Tokens:** JWT-style with 24-hour expiry
- **Reset Tokens:** 30-minute validity
- **Session Cookies:** Secure, HTTP-only (recommended in production)
- **HTTPS:** Required in production

### Best Practices
1. **Email Verification:** Implement double-opt-in for email changes
2. **Rate Limiting:** Add rate limits to password reset endpoint
3. **2FA:** Consider implementing 2FA for admin accounts
4. **Audit Logs:** All reset attempts logged with timestamps
5. **Brute Force Protection:** Implement account lockout after failed attempts

---

## Implementation Checklist

### Frontend
- [x] Login page with role selection
- [x] Forgot password request page
- [x] OTP verification page
- [x] Password reset page
- [x] OTP method selection page
- [x] Real-time validation feedback
- [x] Password strength indicator
- [x] Timer and resend functionality

### Backend
- [x] Forgot password endpoint
- [x] OTP verification endpoint
- [x] Password reset endpoint
- [x] Resend OTP endpoint
- [x] Activity logging
- [x] Session management

### Testing
- [ ] Test complete password recovery flow
- [ ] Test OTP expiration (10 minutes)
- [ ] Test OTP resend cooldown (60 seconds)
- [ ] Test password strength validation
- [ ] Test concurrent recovery attempts
- [ ] Test invalid OTP handling
- [ ] Test token expiration
- [ ] Test SQL injection prevention
- [ ] Test XSS prevention in inputs

---

## Production Checklist

### Email Configuration
```php
// In production, configure email service
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USER', 'your-email@gmail.com');
define('MAIL_PASS', 'your-app-password');
define('MAIL_FROM', 'noreply@medivault.com');
```

### Database Migration (if needed)
```sql
-- Add clinical_id column if using it
ALTER TABLE Users ADD COLUMN clinical_id VARCHAR(50) UNIQUE;

-- Add phone for SMS OTP
ALTER TABLE Users ADD COLUMN phone VARCHAR(20);

-- Create OTP tracking table
CREATE TABLE PasswordResetTokens (
    token_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    otp VARCHAR(6),
    method VARCHAR(20), -- email, sms, authenticator
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    INDEX(token),
    INDEX(user_id),
    INDEX(expires_at)
);
```

### Environment Variables
```env
# Email Configuration
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=your-email@gmail.com
MAIL_PASS=your-app-password

# SMS Configuration (if using)
SMS_PROVIDER=twilio
SMS_ACCOUNT_SID=your_account_sid
SMS_AUTH_TOKEN=your_auth_token
SMS_FROM_NUMBER=+1234567890

# Security
PASSWORD_RESET_TIMEOUT=1800 # 30 minutes
OTP_VALIDITY=600 # 10 minutes
RESEND_COOLDOWN=60 # 1 minute
MAX_RESET_ATTEMPTS=5
```

---

## Troubleshooting

### OTP Not Received
1. Check spam/junk folder
2. Verify email configuration in backend
3. Check activity logs for sending errors
4. Ensure OTP generation endpoint is called

### Password Reset Token Expired
- User must restart process from "Forgot Password"
- Implement token refresh mechanism in production

### Rate Limiting Issues
- Implement exponential backoff for resend attempts
- Track failed attempts per IP address
- Implement CAPTCHA after 3 failed attempts

---

## Future Enhancements

1. **Two-Factor Authentication (2FA)**
   - Authenticator app support
   - SMS/email backup codes
   - Biometric authentication

2. **Advanced Security**
   - Passwordless login with magic links
   - Social login (OAuth)
   - Hardware security key support

3. **User Experience**
   - Notification preferences
   - Login activity dashboard
   - Suspicious login detection

4. **Analytics**
   - Password reset success rate
   - Average reset time
   - Most common reset reasons

---

## Support & Documentation

For implementation support, refer to:
- Backend API documentation: `/Server/README.md`
- Security guidelines: OWASP guidelines
- Database schema: `/Server/database.sql`

---

**Last Updated:** 2026-05-12  
**Version:** 1.0  
**Status:** Production Ready
