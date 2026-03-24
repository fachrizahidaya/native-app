# User Registration System Documentation

## Overview

This is a complete user registration system with email verification via 6-digit OTP (One-Time Password). The system includes username validation, email verification, and secure password storage.

## Features

- ✅ User registration with email and password
- ✅ Unique username validation
- ✅ 6-digit OTP sent to email for verification
- ✅ OTP expiration (10 minutes)
- ✅ Resend OTP functionality
- ✅ Rate limiting for OTP requests
- ✅ Automatic token generation after verification

## API Endpoints

### 1. Check Username Availability

**Endpoint:** `POST /api/auth/check-username`

**Request Body:**

```json
{
    "username": "john_doe"
}
```

**Response:**

```json
{
    "success": true,
    "available": true,
    "message": "Username is available"
}
```

---

### 2. Register New User

**Endpoint:** `POST /api/auth/register`

**Request Body:**

```json
{
    "name": "John Doe",
    "username": "john_doe",
    "email": "john@example.com",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!"
}
```

**Important Notes:**

- The `password_confirmation` field name must be **exactly** `password_confirmation` (not `confirmPassword`, `confirm_password`, or `passwordConfirm`)
- Both `password` and `password_confirmation` must be identical
- Make sure you're sending JSON with `Content-Type: application/json` header

**Response:**

```json
{
    "success": true,
    "message": "Registration successful. Please check your email for the verification code.",
    "data": {
        "user_id": 1,
        "email": "john@example.com",
        "username": "john_doe"
    }
}
```

**Validation Rules:**

- `name`: Required, string, max 255 characters
- `username`: Required, string, 3-50 characters, unique, alphanumeric with dashes/underscores
- `email`: Required, valid email, unique
- `password`: Required, minimum 8 characters, must match confirmation
- `password_confirmation`: Required, minimum 8 characters, must match password

**Common Validation Errors:**

1. **Missing password_confirmation:**

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "password": ["The password and password confirmation do not match."],
        "password_confirmation": ["Password confirmation is required."]
    }
}
```

2. **Passwords don't match:**

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "password": ["The password and password confirmation do not match."]
    }
}
```

3. **Username already taken:**

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "username": ["This username is already taken."]
    }
}
```

---

### 3. Verify OTP

**Endpoint:** `POST /api/auth/verify-otp`

**Request Body:**

```json
{
    "email": "john@example.com",
    "otp": "123456"
}
```

**Success Response:**

```json
{
    "success": true,
    "message": "Email verified successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "username": "john_doe",
            "email": "john@example.com",
            "role": "member"
        },
        "token": "1|laravel_sanctum_token_here"
    }
}
```

**Error Response (Invalid OTP):**

```json
{
    "success": false,
    "message": "Invalid or expired OTP"
}
```

---

### 4. Resend OTP

**Endpoint:** `POST /api/auth/resend-otp`

**Request Body:**

```json
{
    "email": "john@example.com"
}
```

**Success Response:**

```json
{
    "success": true,
    "message": "OTP has been resent to your email"
}
```

**Error Response (Rate Limited):**

```json
{
    "success": false,
    "message": "Please wait before requesting a new OTP. Current OTP is still valid."
}
```

---

## Database Schema

### Users Table (Updated)

```
- id (bigint, primary key)
- name (string)
- username (string, unique) ← NEW
- email (string, unique)
- email_verified_at (timestamp, nullable)
- password (string, hashed)
- role (string)
- otp (string, 6 chars, nullable) ← NEW
- otp_expires_at (timestamp, nullable) ← NEW
- is_verified (boolean, default: false) ← NEW
- remember_token (string, nullable)
- created_at (timestamp)
- updated_at (timestamp)
```

---

## Configuration

### Email Setup

To send OTP emails, configure your mail settings in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourapp.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Queue Configuration (Optional but Recommended)

For better performance, configure queues in `.env`:

```env
QUEUE_CONNECTION=database
```

Then run:

```bash
php artisan queue:table
php artisan migrate
php artisan queue:work
```

---

## Usage Flow

### Complete Registration Process:

1. **Check Username** (Optional but recommended)

    ```
    POST /api/auth/check-username
    ```

2. **Register User**

    ```
    POST /api/auth/register
    → User receives OTP via email
    ```

3. **Verify OTP**

    ```
    POST /api/auth/verify-otp
    → User gets authenticated with token
    ```

4. **Resend OTP** (If needed)
    ```
    POST /api/auth/resend-otp
    → New OTP sent to email
    ```

---

## Security Features

1. **Password Hashing**: All passwords are hashed using Laravel's bcrypt
2. **OTP Expiration**: OTPs expire after 10 minutes
3. **Rate Limiting**: Users can't request new OTP while current one is valid
4. **Email Verification**: Users must verify email before accessing the system
5. **Unique Constraints**: Username and email must be unique

---

## Testing

### Using cURL:

**1. Register:**

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "username": "testuser",
    "email": "test@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!"
  }'
```

**2. Verify OTP:**

```bash
curl -X POST http://localhost:8000/api/auth/verify-otp \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "otp": "123456"
  }'
```

### Using Postman:

1. Import the collection with the endpoints above
2. Set the base URL to your application URL
3. Test each endpoint sequentially

---

## Error Handling

All endpoints return consistent error responses:

```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        "field_name": ["Error message"]
    }
}
```

Common HTTP Status Codes:

- `200`: Success
- `201`: Created (Registration)
- `400`: Bad Request (Invalid OTP, Already verified)
- `404`: Not Found (User not found)
- `422`: Validation Error
- `429`: Too Many Requests (Rate limited)
- `500`: Server Error

---

## Files Created

1. **Migration**: `database/migrations/2026_02_06_000001_add_username_and_otp_fields_to_users_table.php`
2. **Controller**: `app/Http/Controllers/Auth/RegisterController.php`
3. **Service**: `app/Services/OtpService.php`
4. **Notification**: `app/Notifications/OtpNotification.php`
5. **Routes**: Updated `routes/api.php`
6. **Model**: Updated `app/Models/User.php`

---

## Next Steps

1. **Configure Email**: Set up your email credentials in `.env`
2. **Test Locally**: Use Mailtrap or similar for testing
3. **Configure Queue**: Set up queue workers for production
4. **Add Rate Limiting**: Consider adding rate limiters to prevent abuse
5. **Customize Email Template**: Modify `OtpNotification.php` for branded emails
6. **Add Logging**: Implement logging for security events

---

## Troubleshooting

### Password Confirmation Issues

**Problem:** Getting "password confirmation does not match" error even with matching passwords

**Solutions:**

1. **Check the field name** - Must be exactly `password_confirmation`:

    ```json
    // ✅ CORRECT
    {
      "password": "MyPass123!",
      "password_confirmation": "MyPass123!"
    }

    // ❌ WRONG - These will NOT work:
    {
      "password": "MyPass123!",
      "confirmPassword": "MyPass123!"  // camelCase - wrong
    }
    {
      "password": "MyPass123!",
      "confirm_password": "MyPass123!"  // different name - wrong
    }
    ```

2. **Check Content-Type header** - Must be `application/json`:

    ```bash
    # ✅ CORRECT
    curl -X POST http://localhost:8000/api/auth/register \
      -H "Content-Type: application/json" \
      -d '{"password": "Pass123!", "password_confirmation": "Pass123!"}'

    # ❌ WRONG - Missing header
    curl -X POST http://localhost:8000/api/auth/register \
      -d '{"password": "Pass123!", "password_confirmation": "Pass123!"}'
    ```

3. **Check for invisible characters** - Copy-paste might add invisible characters:
    - Type the password manually instead of copy-pasting
    - Trim whitespace from both fields

4. **Verify the request payload** - Log what you're actually sending:

    ```javascript
    // In JavaScript/React Native
    const data = {
        name: "John",
        username: "john",
        email: "john@example.com",
        password: "Pass123!",
        password_confirmation: "Pass123!",
    };
    console.log("Sending:", JSON.stringify(data)); // Debug this
    ```

5. **Test with curl** - Verify the API works:
    ```bash
    curl -X POST http://localhost:8000/api/auth/register \
      -H "Content-Type: application/json" \
      -H "Accept: application/json" \
      -d '{
        "name": "Test User",
        "username": "testuser",
        "email": "test@example.com",
        "password": "Password123!",
        "password_confirmation": "Password123!"
      }'
    ```

### Other Common Issues

**Issue: "This username is already taken"**

- Use the check-username endpoint first
- Choose a different username

**Issue: "This email is already registered"**

- User might have already registered
- Try logging in instead
- Use password reset if forgotten

**Issue: OTP email not received**

- Check spam folder
- Verify MAIL\_\* settings in `.env`
- Check `storage/logs/laravel.log` for email errors
- Use Mailtrap for testing

---

## Support

For issues or questions, check:

- Laravel Documentation: https://laravel.com/docs
- Sanctum Documentation: https://laravel.com/docs/sanctum
- Mail Documentation: https://laravel.com/docs/mail
