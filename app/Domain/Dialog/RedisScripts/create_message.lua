-- Создание сообщения в диалоге
-- Ключи:
--   KEYS[1] - ключ для инкремента ID сообщения ('message:next_id')
--   KEYS[2] - ключ sorted set сообщений диалога ('dialog:messages:{dialogId}')
--   KEYS[3] - ключ хэша диалога ('dialog:{dialogId}')
-- Аргументы:
--   ARGV[1] - dialog_id (число)
--   ARGV[2] - author_user_id (число)
--   ARGV[3] - content (строка)
--   ARGV[4] - timestamp (число, миллисекунды)
-- Возвращает: ID созданного сообщения или nil при ошибке

local message_id_key = KEYS[1]
local messages_key = KEYS[2]
local dialog_key = KEYS[3]

local dialog_id = tonumber(ARGV[1])
local author_user_id = tonumber(ARGV[2])
local content = ARGV[3]
local timestamp = tonumber(ARGV[4])

-- Инкрементируем глобальный ID сообщения
local message_id = redis.call('INCR', message_id_key)
if not message_id then
    return nil
end

-- Создаем объект сообщения
local message = {
    id = message_id,
    dialog_id = dialog_id,
    author_user_id = author_user_id,
    content = content,
    created_at = timestamp
}
local message_json = cjson.encode(message)

-- Добавляем в sorted set с score = timestamp
redis.call('ZADD', messages_key, timestamp, message_json)

-- Обновляем время последнего сообщения в диалоге
redis.call('HSET', dialog_key, 'last_message_at', timestamp)

return message_id