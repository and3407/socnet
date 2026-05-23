#!/bin/bash
set -e

echo "Testing message sending..."

# Register a user if not exists
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
echo "User ID: $USER_ID"

# Login
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

# Send message to dialog ID 4 (as per test)
echo "Sending message to dialog 4..."
RESPONSE=$(curl -s -X POST http://localhost:8383/dialog/4/send \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "text": "Привет, как дела!"
  }')

echo "Response: $RESPONSE"

# Check if success
if echo $RESPONSE | jq -e '.messageId' > /dev/null; then
    echo "Message sent successfully."
else
    echo "Failed to send message."
    exit 1
fi