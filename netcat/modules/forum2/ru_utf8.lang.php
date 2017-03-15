<?php

/* $Id $ */
define("NETCAT_MODULE_SETUP_FORUM2", "Форум");

define("NETCAT_MODULE_FORUM2_DESCRIPTION", "Форум, обновлённая версия");

define("NETCAT_MODULE_FORUM2_FORUM_TOPIC_TYPE", "Типы топиков форума");
define("NETCAT_MODULE_FORUM2_FORUM_TOPIC_TYPE_SIMPLE", "обычный");
define("NETCAT_MODULE_FORUM2_FORUM_TOPIC_TYPE_IMPORTANT", "важный");
define("NETCAT_MODULE_FORUM2_FORUM_TOPIC_TYPE_ADVERTISEMENT", "объявление");
define("NETCAT_MODULE_FORUM2_FORUM_TOPIC_TYPE_CLOSED", "закрытый");

define("NETCAT_MODULE_FORUM2_FORUM_REPLY", "Ответ на топик");

// components constants
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_LEGEND", "Добавление нового форума");
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_FIELD_CHECKED", "включить форум");
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_FIELD_KEYWORD", "Ключевое слово форума");
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_FIELD_NAME", "Название форума");
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_FIELD_DESCRIPTION", "Описание форума");
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_FIELD_GROUP", "Категория форума");
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_FIELD_TOPIC_KEYWORD", "Ключевое слово компонета \"Топики\"");
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_FIELD_TOPIC_NAME", "Название компонета \"Топики\"");
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_FIELD_REPLY_KEYWORD", "Ключевое слово компонета \"Ответы\"");
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_FIELD_REPLY_NAME", "Название компонета \"Ответы\"");
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_FIELD_TOPIC_RSS_KEYWORD", "Ключевое слово компонета \"Топики RSS\"");
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_FIELD_TOPIC_RSS_NAME", "Название компонета \"Топики RSS\"");
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_FIELD_REPLY_RSS_KEYWORD", "Ключевое слово компонета \"Ответы RSS\"");
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_FIELD_REPLY_RSS_NAME", "Название компонета \"Ответы RSS\"");
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_DEFAULT_TOPIC_KEYWORD", "topic");
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_DEFAULT_TOPIC_NAME", "Топики");
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_DEFAULT_REPLY_KEYWORD", "reply");
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_DEFAULT_REPLY_NAME", "Ответы");
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_DEFAULT_PARENT_RSS_KEYWORD", "rss");
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_DEFAULT_PARENT_RSS_NAME", "RSS");
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_DEFAULT_TOPIC_RSS_KEYWORD", "rss");
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_DEFAULT_TOPIC_RSS_NAME", "RSS топики");
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_DEFAULT_REPLY_RSS_KEYWORD", "replyrss");
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_DEFAULT_REPLY_RSS_NAME", "RSS ответы");

define("NETCAT_MODULE_FORUM2_USER_GUEST", "Гость");

define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_REQFIELDS", "Заполните поле <b>\"".NETCAT_MODULE_FORUM2_COMPONENT_FORUM_FIELD_KEYWORD."\"</b> и <b>\"".NETCAT_MODULE_FORUM2_COMPONENT_FORUM_FIELD_NAME."\"</b>");

define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_ERROR_CREATE", "Ошибка создания форума!");
define("NETCAT_MODULE_FORUM2_COMPONENT_FORUM_ERROR_EXIST", "Форум с ключевым словом <b>\"%s\"</b> уже существует!");

define("NETCAT_MODULE_FORUM2_COMPONENT_INSIDE_ADMIN_RSS", "RSS лента недоступна в режиме редактирования, <a href='%s' target='_blank'>просмотреть RSS</a>");

define("NETCAT_MODULE_FORUM2_COMPONENT_REPLIES_EDIT_IN_PLACE", "Ответы на топики удобнее редактировать, открыв нужный топик.");
define("NETCAT_MODULE_FORUM2_COMPONENT_REPLIES_SOURCE", "Исходное сообщение");

define("NETCAT_MODULE_FORUM2_COMPONENT_MESSAGE_PREVIEW", "Предпросмотр");

define("NETCAT_MODULE_FORUM2_COMPONENT_TOPIC_UPDATED", "Топик изменён.");
define("NETCAT_MODULE_FORUM2_COMPONENT_REPLY_UPDATED", "Ответ на топик изменён.");

define("NETCAT_MODULE_FORUM2_COMPONENT_TOPIC_CLOSED", "Топик закрыт.");

define("NETCAT_MODULE_FORUM2_COMPONENT_TOPIC_ADDED", "Новый топик добавлен.");
define("NETCAT_MODULE_FORUM2_COMPONENT_TOPIC_ADDED_MODERATION", "Новый топик будет добавлен после проверки модератором форума.");
define("NETCAT_MODULE_FORUM2_COMPONENT_RETURN_TO_THE_FORUM", "Вернуться к форуму");

define("NETCAT_MODULE_FORUM2_COMPONENT_REPLY_ADDED", "Ответ на топик добавлен.");
define("NETCAT_MODULE_FORUM2_COMPONENT_REPLY_ADDED_MODERATION", "Ответ на топик будет добавлен после проверки модератором форума.");
define("NETCAT_MODULE_FORUM2_COMPONENT_RETURN_TO_THE_TOPIC", "Вернуться в топик");

// module admin
define("NETCAT_MODULE_FORUM2_ADMIN_TEMPLATE_CONVERTER_TAB", "Конвертер из старых версий");
define("NETCAT_MODULE_FORUM2_ADMIN_TEMPLATE_SETTINGS_TAB", "Настройки форума");

define("NETCAT_MODULE_FORUM2_ADMIN_SETTINGS_FORUM_LIST", "Список форумов");
define("NETCAT_MODULE_FORUM2_ADMIN_SETTINGS_GROUPS_SETTINGS", "Управление категориями");
define("NETCAT_MODULE_FORUM2_ADMIN_SETTINGS_GROUP_NEW", "-- новая категория --");
define("NETCAT_MODULE_FORUM2_ADMIN_SETTINGS_GROUP_FORUM", "Форум категории");
define("NETCAT_MODULE_FORUM2_ADMIN_SETTINGS_GROUP_NAME", "Название категории");
define("NETCAT_MODULE_FORUM2_ADMIN_SETTINGS_GROUP_PRIORITY", "Приоритет");
define("NETCAT_MODULE_FORUM2_ADMIN_SETTINGS_GROUP_DESCRIPTION", "Описание категории");
define("NETCAT_MODULE_FORUM2_ADMIN_SETTINGS_GROUP_DELETE", "удалить категорию");
define("NETCAT_MODULE_FORUM2_ADMIN_SETTINGS_SAVE", "Сохранить");
define("NETCAT_MODULE_FORUM2_ADMIN_SETTINGS_NO_FORUMS_FOUND", "форумы отсутствуют");

define("NETCAT_MODULE_FORUM2_ADMIN_SAVE_OK", "Настройки успешно сохранены");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERTER_RETURN_BUTTON", "Вернуться");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERTER_SAVE_BUTTON_3", "Выбрать сайт");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERTER_SAVE_BUTTON_4", "Конвертировать");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERTER_SELECT_CATALOGUE", "Выбор сайта");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERTER_SELECT_SUBDIVISION", "Выбор раздела");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERTER_SUBDIVISION_NEW_KEYWORD", "Ключевое слово нового раздела форумов");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERTER_SUBDIVISION_NEW_NAME", "Название нового раздела форумов");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERTER_DIALOG", "Диалог выбора");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERT_OK", "Сообщения форума переконвертированы.");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERT_ERROR", "Во время конвертирования возникла ошибка.");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERT_DATA_ERROR", "Неверные входные данные, попробуйте ещё раз.");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERT_NO_CATALOGUE_ERROR", "Не найдено ни одного сайта.");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERT_NO_SUBDIVISION", "Не найдено ни одного раздела на сайте для конвертации.");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERT_NO_DATA", "Объекты для конвертирования отсутствуют.");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERT_NO_PARENT_DATA", "Форум не найден.");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERT_CAN_NOT_CREATE_PARENT", "Ошибка создания корневого раздела форумов.");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERT_NO_GROUPS_DATA", "Отсутствуют категории форума.");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERT_NO_FORUMS_DATA", "Форумы отсутствуют.");

define("NETCAT_MODULE_FORUM2_ADMIN_CONVERT_PERMISSION_SETTINGS", "Настройка прав");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERT_PERMISSION_SET_FORUM", "конвертировать права на форумы (разделы)");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERT_PERMISSION_SET_GROUPS", "конвертировать права групп пользователей");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERT_PERMISSION_GROUP_USERS", "Группа пользователей");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERT_PERMISSION_GROUP_MODERATORS", "Группа модераторов");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERT_PERMISSION_VIEW", "просмотр");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERT_PERMISSION_COMMENT", "комментирование");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERT_PERMISSION_ADD", "добавление");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERT_PERMISSION_EDIT", "изменение");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERT_PERMISSION_CHECK", "включение");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERT_PERMISSION_DELETE", "удаление");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERT_PERMISSION_SUBSCRIBE", "подписка");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERT_PERMISSION_MODERATE", "модерирование");
define("NETCAT_MODULE_FORUM2_ADMIN_CONVERT_PERMISSION_ADMINISTER", "администрирование");

define("NETCAT_MODULE_FORUM2_CLASS_UNRECOGNIZED_OBJECT_CALLING", "Неподдерживаемый вызов объекта");
define("NETCAT_MODULE_FORUM2_CLASS_UNCORRECT_DATA_FORMAT", "Неверный формат данных");
?>