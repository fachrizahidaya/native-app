#!/bin/bash

# Registration System Test Script
# This script tests the registration endpoints to verify everything is working

BASE_URL="http://localhost:8000"
API_URL="$BASE_URL/api/auth"

echo "=================================="
echo "Registration System Test"
echo "=================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Generate random test data
TIMESTAMP=$(date +%s)
TEST_USERNAME="testuser_$TIMESTAMP"
TEST_EMAIL="test_$TIMESTAMP@example.com"
TEST_PASSWORD="Password123!"

echo -e "${YELLOW}Test 1: Check if server is running...${NC}"
if curl -s -o /dev/null -w "%{http_code}" "$BASE_URL" | grep -q "200\|302\|404"; then
    echo -e "${GREEN}✓ Server is running${NC}"
else
    echo -e "${RED}✗ Server is not running on $BASE_URL${NC}"
    echo "Please start the server with: php artisan serve"
    exit 1
fi
echo ""

echo -e "${YELLOW}Test 2: Check username availability...${NC}"
RESPONSE=$(curl -s -X POST "$API_URL/check-username" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"username\": \"$TEST_USERNAME\"}")

if echo "$RESPONSE" | grep -q '"available":true'; then
    echo -e "${GREEN}✓ Username check endpoint working${NC}"
    echo "Response: $RESPONSE"
else
    echo -e "${RED}✗ Username check failed${NC}"
    echo "Response: $RESPONSE"
fi
echo ""

echo -e "${YELLOW}Test 3: Register with MISSING password_confirmation...${NC}"
RESPONSE=$(curl -s -X POST "$API_URL/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{
    \"name\": \"Test User\",
    \"username\": \"${TEST_USERNAME}_missing\",
    \"email\": \"missing_$TEST_EMAIL\",
    \"password\": \"$TEST_PASSWORD\"
  }")

if echo "$RESPONSE" | grep -q 'Password confirmation is required'; then
    echo -e "${GREEN}✓ Correctly rejects missing password_confirmation${NC}"
    echo "Response: $RESPONSE"
else
    echo -e "${RED}✗ Should have rejected missing password_confirmation${NC}"
    echo "Response: $RESPONSE"
fi
echo ""

echo -e "${YELLOW}Test 4: Register with MISMATCHED passwords...${NC}"
RESPONSE=$(curl -s -X POST "$API_URL/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{
    \"name\": \"Test User\",
    \"username\": \"${TEST_USERNAME}_mismatch\",
    \"email\": \"mismatch_$TEST_EMAIL\",
    \"password\": \"$TEST_PASSWORD\",
    \"password_confirmation\": \"DifferentPassword123!\"
  }")

if echo "$RESPONSE" | grep -q 'do not match'; then
    echo -e "${GREEN}✓ Correctly rejects mismatched passwords${NC}"
    echo "Response: $RESPONSE"
else
    echo -e "${RED}✗ Should have rejected mismatched passwords${NC}"
    echo "Response: $RESPONSE"
fi
echo ""

echo -e "${YELLOW}Test 5: Register with MATCHING passwords...${NC}"
RESPONSE=$(curl -s -X POST "$API_URL/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{
    \"name\": \"Test User\",
    \"username\": \"$TEST_USERNAME\",
    \"email\": \"$TEST_EMAIL\",
    \"password\": \"$TEST_PASSWORD\",
    \"password_confirmation\": \"$TEST_PASSWORD\"
  }")

if echo "$RESPONSE" | grep -q '"success":true'; then
    echo -e "${GREEN}✓ Registration successful with matching passwords!${NC}"
    echo "Response: $RESPONSE"
    
    # Extract user_id for cleanup
    USER_ID=$(echo "$RESPONSE" | grep -o '"user_id":[0-9]*' | grep -o '[0-9]*')
    echo ""
    echo -e "${GREEN}User registered successfully (ID: $USER_ID)${NC}"
    echo "Username: $TEST_USERNAME"
    echo "Email: $TEST_EMAIL"
else
    echo -e "${RED}✗ Registration failed${NC}"
    echo "Response: $RESPONSE"
fi
echo ""

echo -e "${YELLOW}Test 6: Try to register with same username (should fail)...${NC}"
RESPONSE=$(curl -s -X POST "$API_URL/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{
    \"name\": \"Another User\",
    \"username\": \"$TEST_USERNAME\",
    \"email\": \"another_$TEST_EMAIL\",
    \"password\": \"$TEST_PASSWORD\",
    \"password_confirmation\": \"$TEST_PASSWORD\"
  }")

if echo "$RESPONSE" | grep -q 'already taken'; then
    echo -e "${GREEN}✓ Correctly rejects duplicate username${NC}"
    echo "Response: $RESPONSE"
else
    echo -e "${RED}✗ Should have rejected duplicate username${NC}"
    echo "Response: $RESPONSE"
fi
echo ""

echo "=================================="
echo -e "${GREEN}All tests completed!${NC}"
echo "=================================="
echo ""
echo "Next steps:"
echo "1. Check your email/logs for the OTP code"
echo "2. Test OTP verification endpoint"
echo "3. Configure email settings in .env"
echo ""
