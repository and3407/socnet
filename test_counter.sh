#!/bin/bash
set -e

echo "Testing counter-service..."

# Wait for service to be ready
echo "Waiting for counter-service on port 9100..."
until curl -s http://localhost:9100/health > /dev/null; do
    sleep 1
done

echo "Counter-service is up."

# Test getting unread count for a user (requires authentication)
# First, register a user
echo "Registering test user..."
REGISTER_RESPONSE=$(curl -s -X POST http://localhost:8383/user/register \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "test",
    "second_name": "user",
    "birthdate": "1990-01-01",
    "biography": "test",
    "city": "Samara",
    "password": "P@asword123"
  }')

USER_ID=$(echo $REGISTER_RESPONSE | jq -r '.user_id')
if [ "$USER_ID" == "null" ] || [ -z "$USER_ID" ]; then
    echo "Failed to register user"
    exit 1
fi
echo "User registered with ID: $USER_ID"

# Login to get token
echo "Logging in..."
LOGIN_RESPONSE=$(curl -s -X POST http://localhost:8383/login \
  -H "Content-Type: application/json" \
  -d "{
    \"uuid\": \"$USER_ID\",
    \"password\": \"P@asword123\"
  }")

TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.token')
if [ "$TOKEN" == "null" ] || [ -z "$TOKEN" ]; then
    echo "Failed to login"
    exit 1
fi
echo "Token obtained."

# Test counter endpoint
echo "Testing GET /api/v1/counters/user/$USER_ID..."
COUNTER_RESPONSE=$(curl -s -X GET http://localhost:9100/api/v1/counters/user/$USER_ID \
  -H "Authorization: Bearer $TOKEN")

echo "Response: $COUNTER_RESPONSE"

# Check if response contains total_unread
if echo $COUNTER_RESPONSE | jq -e '.total_unread' > /dev/null; then
    echo "Counter service works correctly."
else
    echo "Counter service may have issues."
    exit 1
fi

echo "All tests passed."