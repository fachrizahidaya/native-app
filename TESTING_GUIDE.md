# Quick Testing Guide - Registration System

## Setup

### 1. Configure Email (Choose one method)

#### Option A: Using Mailtrap (Recommended for Development)

1. Sign up at https://mailtrap.io (free)
2. Get your credentials from the inbox
3. Update `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourapp.com
MAIL_FROM_NAME="${APP_NAME}"
```

#### Option B: Using Gmail (For Production Testing)

1. Enable 2-factor authentication on your Gmail
2. Generate an App Password
3. Update `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-16-char-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

#### Option C: Log Emails to File (Testing Only)

Update `.env`:

```env
MAIL_MAILER=log
```

OTPs will be logged to `storage/logs/laravel.log`

### 2. Clear Config Cache

```bash
php artisan config:clear
php artisan cache:clear
```

---

## Test Scenarios

### Scenario 1: Complete Registration Flow

**Step 1: Check Username**

```bash
curl -X POST http://localhost:8000/api/auth/check-username \
  -H "Content-Type: application/json" \
  -d '{"username": "johndoe"}'
```

**Step 2: Register**

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "username": "johndoe",
    "email": "john@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!"
  }'
```

**Step 3: Check Email**

- Check Mailtrap inbox OR
- Check `storage/logs/laravel.log` for OTP

**Step 4: Verify OTP**

```bash
curl -X POST http://localhost:8000/api/auth/verify-otp \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "otp": "123456"
  }'
```

Save the token from the response!

**Step 5: Test Login**

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "Password123!"
  }'
```

---

### Scenario 2: Duplicate Username

```bash
# First user
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "User One",
    "username": "sameusername",
    "email": "user1@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!"
  }'

# Try same username (should fail)
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "User Two",
    "username": "sameusername",
    "email": "user2@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!"
  }'
```

Expected error:

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "username": ["The username has already been taken."]
    }
}
```

---

### Scenario 3: Resend OTP

```bash
# 1. Register user
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "username": "testuser",
    "email": "test@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!"
  }'

# 2. Wait for OTP to expire or request new one
curl -X POST http://localhost:8000/api/auth/resend-otp \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com"}'
```

---

### Scenario 4: Expired OTP

1. Register user
2. Wait 10+ minutes
3. Try to verify with old OTP
4. Should get "Invalid or expired OTP" error
5. Request new OTP via resend endpoint

---

### Scenario 5: Login Without Verification

```bash
# 1. Register user
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Unverified User",
    "username": "unverified",
    "email": "unverified@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!"
  }'

# 2. Try to login without verifying (should fail)
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "unverified@example.com",
    "password": "Password123!"
  }'
```

Expected response:

```json
{
    "success": false,
    "message": "Please verify your email address first. Check your email for the OTP code.",
    "requires_verification": true,
    "email": "unverified@example.com"
}
```

---

## Postman Collection

### Import this JSON into Postman:

```json
{
    "info": {
        "name": "User Registration System",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    },
    "item": [
        {
            "name": "Check Username",
            "request": {
                "method": "POST",
                "header": [
                    { "key": "Content-Type", "value": "application/json" }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\n  \"username\": \"johndoe\"\n}"
                },
                "url": "{{base_url}}/api/auth/check-username"
            }
        },
        {
            "name": "Register",
            "request": {
                "method": "POST",
                "header": [
                    { "key": "Content-Type", "value": "application/json" }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\n  \"name\": \"John Doe\",\n  \"username\": \"johndoe\",\n  \"email\": \"john@example.com\",\n  \"password\": \"Password123!\",\n  \"password_confirmation\": \"Password123!\"\n}"
                },
                "url": "{{base_url}}/api/auth/register"
            }
        },
        {
            "name": "Verify OTP",
            "request": {
                "method": "POST",
                "header": [
                    { "key": "Content-Type", "value": "application/json" }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\n  \"email\": \"john@example.com\",\n  \"otp\": \"123456\"\n}"
                },
                "url": "{{base_url}}/api/auth/verify-otp"
            }
        },
        {
            "name": "Resend OTP",
            "request": {
                "method": "POST",
                "header": [
                    { "key": "Content-Type", "value": "application/json" }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\n  \"email\": \"john@example.com\"\n}"
                },
                "url": "{{base_url}}/api/auth/resend-otp"
            }
        },
        {
            "name": "Login",
            "request": {
                "method": "POST",
                "header": [
                    { "key": "Content-Type", "value": "application/json" }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\n  \"email\": \"john@example.com\",\n  \"password\": \"Password123!\"\n}"
                },
                "url": "{{base_url}}/api/auth/login"
            }
        }
    ],
    "variable": [
        {
            "key": "base_url",
            "value": "http://localhost:8000"
        }
    ]
}
```

---

## Debugging Tips

### 1. Check OTP in Logs

```bash
tail -f storage/logs/laravel.log
```

### 2. Check Database

```bash
php artisan tinker
```

```php
// Check recently registered users
User::latest()->first();

// Check OTP details
User::where('email', 'test@example.com')->first(['email', 'otp', 'otp_expires_at', 'is_verified']);

// Manually verify a user (for testing)
$user = User::where('email', 'test@example.com')->first();
$user->update(['is_verified' => true, 'email_verified_at' => now(), 'otp' => null]);
```

### 3. Test Email Configuration

```bash
php artisan tinker
```

```php
Mail::raw('Test email', function($msg) {
    $msg->to('test@example.com')->subject('Test');
});
```

### 4. Clear Everything and Start Fresh

```bash
php artisan migrate:fresh
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

## Common Issues

### Issue: Emails not sending

**Solution:**

1. Check `.env` mail configuration
2. Run `php artisan config:clear`
3. Check `storage/logs/laravel.log` for errors
4. Test with `MAIL_MAILER=log` first

### Issue: "Column not found: username"

**Solution:**

```bash
php artisan migrate:fresh
```

### Issue: "Invalid or expired OTP"

**Solution:**

1. Check the OTP in database/email
2. OTPs expire after 10 minutes
3. Use resend OTP endpoint

### Issue: OTP in database is null after registration

**Solution:**

1. Check if mail is configured properly
2. Check application logs for errors
3. Ensure OtpService is being called

---

## Production Checklist

- [ ] Configure real SMTP provider (SendGrid, Mailgun, SES)
- [ ] Set up queue workers for email sending
- [ ] Add rate limiting middleware
- [ ] Implement proper logging
- [ ] Add email templates with branding
- [ ] Configure CORS properly
- [ ] Set up monitoring for failed emails
- [ ] Add tests for registration flow
- [ ] Document API in Swagger/OpenAPI
- [ ] Set up email bounce handling

---

## Security Notes

1. **Never log OTP codes in production**
2. **Use HTTPS in production**
3. **Implement rate limiting on all auth endpoints**
4. **Monitor for suspicious registration patterns**
5. **Consider adding CAPTCHA for registration**
6. **Implement account lockout after failed attempts**
7. **Use environment variables for all sensitive data**
