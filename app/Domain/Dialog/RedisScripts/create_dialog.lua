-- Создание диалога между двумя пользователями
-- Ключи:
--   KEYS[1] - ключ для инкремента ID диалога ('dialog:next_id')
--   KEYS[2] - ключ хэша диалога ('dialog:{dialogId}')
--   KEYS[3] - ключ множества диалогов пользователя 1 ('user:dialogs:{userId1}')
--   KEYS[4] - ключ множества диалогов пользователя 2 ('user:dialogs:{userId2}')
--   KEYS[5] - ключ пары пользователей ('dialog:pair:{userId1}:{userId2}')
-- Аргументы:
--   ARGV[1] - creater_user_id (число)
--   ARGV[2] - recipient_user_id (число)
--   ARGV[3] - name (строка, может быть пустой)
--   ARGV[4] - timestamp (число, секунды)
-- Возвращает: ID созданного диалога или nil при ошибке

local dialog_id_key = KEYS[1]
local dialog_key = KEYS[2]
local user_dialogs_key1 = KEYS[3]
local user_dialogs_key2 = KEYS[4]
local pair_key = KEYS[5]

local creater_user_id = tonumber(ARGV[1])
local recipient_user_id = tonumber(ARGV[2])
local name = ARGV[3]
local timestamp = tonumber(ARGV[4])

-- Проверяем, существует ли уже диалог между этими пользователями
local existing_dialog_id = redis.call('GET', pair_key)
if existing_dialog_id then
    return tonumber(existing_dialog_id)
end

-- Инкрементируем глобальный ID диалога
local dialog_id = redis.call('INCR', dialog_id_key)
if not dialog_id then
    return nil
end

-- Создаем хэш диалога
redis.call('HMSET', dialog_key,
    'id', dialog_id,
    'name', name,
    'creater_user_id', creater_user_id,
    'created_at', timestamp
)

-- Добавляем диалог в множества пользователей
redis.call('SADD', user_dialogs_key1, dialog_id)
redis.call('SADD', user_dialogs_key2, dialog_id)

-- Сохраняем пару пользователей -> dialog_id
redis.call('SET', pair_key, dialog_id)

return dialog_id