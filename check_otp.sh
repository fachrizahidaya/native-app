#!/bin/bash

# OTP Checker Script
# Quick script to check OTP codes from the database or logs

echo "======================================"
echo "OTP Code Checker"
echo "======================================"
echo ""

if [ "$1" == "db" ] || [ "$1" == "database" ]; then
    echo "📊 Checking database for recent OTPs..."
    echo ""
    psql -U arifburhanthoyib -d native_php -c "
        SELECT 
            id,
            email,
            username,
            otp,
            to_char(otp_expires_at, 'YYYY-MM-DD HH24:MI:SS') as expires_at,
            CASE 
                WHEN otp_expires_at > NOW() THEN '✓ Valid'
                WHEN otp_expires_at IS NULL THEN '- No OTP'
                ELSE '✗ Expired'
            END as status,
            CASE 
                WHEN is_verified THEN '✓ Yes'
                ELSE '✗ No'
            END as verified
        FROM users 
        WHERE otp IS NOT NULL OR is_verified = false
        ORDER BY id DESC 
        LIMIT 10;
    "
elif [ "$1" == "log" ] || [ "$1" == "logs" ]; then
    echo "📝 Checking logs for recent OTP emails..."
    echo ""
    grep -B 5 "Your 6-digit verification code is:" storage/logs/laravel.log | \
    grep -E "Hello|verification code is:|^\*\*[0-9]{6}\*\*$" | \
    tail -20
elif [ "$1" == "email" ] && [ -n "$2" ]; then
    echo "🔍 Checking OTP for email: $2"
    echo ""
    psql -U arifburhanthoyib -d native_php -c "
        SELECT 
            email,
            otp,
            to_char(otp_expires_at, 'YYYY-MM-DD HH24:MI:SS') as expires_at,
            CASE 
                WHEN otp_expires_at > NOW() THEN '✓ Still Valid'
                WHEN otp_expires_at IS NULL THEN 'No OTP'
                ELSE '✗ Expired'
            END as status
        FROM users 
        WHERE email = '$2';
    "
else
    echo "Usage:"
    echo "  $0 db          - Check database for all OTPs"
    echo "  $0 log         - Check email logs for recent OTPs"
    echo "  $0 email <email> - Check OTP for specific email"
    echo ""
    echo "Examples:"
    echo "  $0 db"
    echo "  $0 log"
    echo "  $0 email testmail@example.com"
    echo ""
    echo "Quick check - Last 5 users with OTPs:"
    psql -U arifburhanthoyib -d native_php -c "
        SELECT 
            email,
            otp,
            CASE WHEN otp_expires_at > NOW() THEN '✓' ELSE '✗' END as valid
        FROM users 
        WHERE otp IS NOT NULL
        ORDER BY id DESC 
        LIMIT 5;
    "
fi

echo ""
