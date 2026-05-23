#!/bin/bash

# UUID пользователя 4 (отправитель)
FROM_UUID="c259e853-1ce4-482f-b466-12da84acca96"
# UUID пользователя 5 (получатель)
TO_UUID="417a9b58-7752-4f40-b092-3b05756ad5d5"

# Получаем токен для пользователя 4 (предполагаем, что пароль известен)
TOKEN=$(curl -s -X POST http://localhost:8383/login \
  -H "Content-Type: application/json" \
  -d "{\"uuid\": \"$FROM_UUID\", \"password\": \"P@asword123\"}" | jq -r '.token')

if [ -z "$TOKEN" ] || [ "$TOKEN" = "null" ]; then
  echo "Не удалось получить токен"
  exit 1
fi

echo "Токен: $TOKEN"

# Отправляем сообщение через основной контроллер (диалог ID = 1)
RESPONSE=$(curl -s -X POST http://localhost:8383/dialog/1/send \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d "{\"to_user_id\": \"$TO_UUID\", \"text\": \"Тестовое сообщение для счетчика\"}")

echo "Ответ отправки: $RESPONSE"

# Проверяем счетчик для получателя (пользователь 5) через endpoint основного приложения
sleep 2
COUNT_RESPONSE=$(curl -s -H "Authorization: Bearer $TOKEN" "http://localhost:8383/user/$TO_UUID/unread-count")
echo "Счетчик непрочитанных для пользователя $TO_UUID: $COUNT_RESPONSE"