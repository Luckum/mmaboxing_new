<?php
/* Module */
define("NETCAT_MODULE_PAYMENT_NAME", "Приём платежей");
define("NETCAT_MODULE_PAYMENT_DESCRIPTION", "Модуль для приёма платежей");

/* Events description */
define("NETCAT_MODULE_PAYMENT_EVENT_ON_INIT", "Инициализация платежной системы");
define("NETCAT_MODULE_PAYMENT_EVENT_BEFORE_PAY_REQUEST", "Подготовка запроса на оплату"); // "Перед запросом на оплату"
define("NETCAT_MODULE_PAYMENT_EVENT_AFTER_PAY_REQUEST", "Запрос на оплату"); // "После запроса на оплату"
define("NETCAT_MODULE_PAYMENT_EVENT_ON_PAY_REQUEST_ERROR", "Ошибка в параметрах запроса на оплату");
define("NETCAT_MODULE_PAYMENT_EVENT_BEFORE_PAY_CALLBACK", "Подготовка к обработке callback-ответа платежной системы"); // "Перед обработкой callback ответа платежной системы"
define("NETCAT_MODULE_PAYMENT_EVENT_AFTER_PAY_CALLBACK", "Обработка callback-ответа платежной системы"); // "После обработки callback ответа платежной системы"
define("NETCAT_MODULE_PAYMENT_EVENT_ON_PAY_CALLBACK_ERROR", "Ошибка в параметрах при callback-ответе"); // "Ошибка в параметрах при callback ответе"
define("NETCAT_MODULE_PAYMENT_EVENT_ON_PAY_SUCCESS", "Платеж успешно проведен"); // "Платеж успешно проведен"
define("NETCAT_MODULE_PAYMENT_EVENT_ON_PAY_FAILURE", "Оплата не осуществлена"); // "Оплата не осуществлена"

/* Order description string */
define("NETCAT_MODULE_PAYMENT_PAYMENT_DESCRIPTION", "Оплата заказа №%s");

/* Error messages */
define("NETCAT_MODULE_PAYMENT_REQUEST_ERROR", "Ошибка в параметрах запроса");
define("NETCAT_MODULE_PAYMENT_ORDER_ID_IS_NOT_UNIQUE", "Номер заказа не уникальный");
define("NETCAT_MODULE_PAYMENT_ORDER_ID_IS_NULL", "Не установлен параметр 'OrderId'");
define("NETCAT_MODULE_PAYMENT_INCORRECT_PAYMENT_AMOUNT", "Сумма платежа не указана или задана некорректно");
define("NETCAT_MODULE_PAYMENT_INCORRECT_PAYMENT_CURRENCY", "Платёжная система не принимает платежи в валюте «%s»");
define("NETCAT_MODULE_PAYMENT_CANNOT_LOAD_INVOICE_ON_CALLBACK", "Платёжная система вернула неправильный идентификатор платежа");

/* admin.php */
define("NETCAT_MODULE_PAYMENT_SITES", "Сайт для настройки");
define("NETCAT_MODULE_PAYMENT_CHOICE_SITE", "Выбор сайта");
define("NETCAT_MODULE_PAYMENT_PAYMENT_SYSTEM", "Платежная система");
define("NETCAT_MODULE_PAYMENT_LIST_PAYMENT_SYSTEMS", "Список платежных систем");
define("NETCAT_MODULE_PAYMENT_SETTINGS_PAYMENT_SYSTEM", "Настройки");
define("NETCAT_MODULE_PAYMENT_ONOFF_PAYMENT_SYSTEM", "Вкл/выкл");
define("NETCAT_MODULE_PAYMENT_ADMIN_BUTTON_SAVE", "Сохранить");
define("NETCAT_MODULE_PAYMENT_ADMIN_BUTTON_ADD_PARAMETER", "Добавить параметр");
define("NETCAT_MODULE_PAYMENT_PAYMENT_SYSTEM_PARAMETERS", "Параметры платежной системы");
define("NETCAT_MODULE_PAYMENT_PARAMETER", "Параметр");
define("NETCAT_MODULE_PAYMENT_PARAMETER_VALUE", "Значение");
define("NETCAT_MODULE_PAYMENT_ADMIN_BUTTON_DELETE","Удалить");
define("NETCAT_MODULE_PAYMENT_ADMIN_BUTTON_CHANGE_SETTINGS","изменить параметры системы");
define("NETCAT_MODULE_PAYMENT_ADMIN_SETTINGS_SAVED", "Настройки сохранены");
define("NETCAT_MODULE_PAYMENT_ADMIN_CHANGE_PARAMETERS", "Изменить параметры системы");

/* Payment common messages */
define("NETCAT_MODULE_PAYMENT_ERROR_PRIVATE_SECURITY_IS_NOT_VALID", "Некорректная подпись формы");
define("NETCAT_MODULE_PAYMENT_ERROR_INVOICE_NOT_FOUND", "Платеж не найден в NetCat");
define("NETCAT_MODULE_PAYMENT_ERROR_INVALID_SUM", "Неверная сумма платежа");
define("NETCAT_MODULE_PAYMENT_ERROR_ALREADY_PAID", "Счет уже оплачен");

/* Payment form texts */
define("NETCAT_MODULE_PAYMENT_FORM_PAY", "Оплатить");

/* Assist */
define("NETCAT_MODULE_PAYMENT_ASSIST_ERROR_CHECKVALUE_IS_NOT_VALID", "Неправильный параметр 'CheckValue'");
define("NETCAT_MODULE_PAYMENT_ASSIST_ERROR_ASSIST_SHOP_ID", "Параметр 'AssistShopId' должен быть числом");
define("NETCAT_MODULE_PAYMENT_ASSIST_ERROR_ASSIST_SECRET_WORD_IS_NULL", "Параметр 'AssistSecretWord' должен быть установлен");

/* Mail */
define("NETCAT_MODULE_PAYMENT_MAIL_ERROR_SIGNATURE_IS_NOT_VALID", "Неправильный параметр 'Signature'");
define("NETCAT_MODULE_PAYMENT_MAIL_ERROR_SHOP_ID", "Параметр 'MailShopID' должен быть числом");
define("NETCAT_MODULE_PAYMENT_MAIL_ERROR_SECRET_KEY_IS_NULL", "Параметр 'MailSecretKey' должен быть установлен");
define("NETCAT_MODULE_PAYMENT_MAIL_ERROR_HASH_IS_NULL", "Параметр 'MailHash' должен быть установлен");

/* Paymaster */
define("NETCAT_MODULE_PAYMENT_PAYMASTER_ERROR_MERCHANTID_IS_NOT_VALID", "Неправильный параметр 'LMI_MERCHANT_ID'");
define("NETCAT_MODULE_PAYMENT_PAYMASTER_ERROR_LMI_PAYMENT_DESC_IS_LONG", "Длина параметра 'LMI_PAYMENT_DESC' должна быть меньше 255");
define("NETCAT_MODULE_PAYMENT_PAYMASTER_ERROR_PRIVATE_SECURITY_IS_NOT_VALID", "Неправильный параметр 'LMI_HASH'");

/* Payonline */
define("NETCAT_MODULE_PAYMENT_PAYONLINE_ERROR_MERCHANT_ID", "Параметр 'MerchantId' должен быть числом");
define("NETCAT_MODULE_PAYMENT_PAYONLINE_ERROR_PRIVATE_SECURITY_KEY_IS_NULL", "Параметр 'PrivateSecurityKey' должен быть установлен");
define("NETCAT_MODULE_PAYMENT_PAYONLINE_ERROR_PRIVATE_SECURITY_IS_NOT_VALID", "Неправильный параметр 'SecurityKey'");

/* Paypal */
define("NETCAT_MODULE_PAYMENT_PAYPAL_ERROR_SOME_PARAMETRS_ARE_NOT_VALID", "Некорректные параметры");
define("NETCAT_MODULE_PAYMENT_PAYPAL_ERROR_PAYPAL_MAIL_IS_NOT_VALID", "Некорректный параметр 'Paypal-email'");

/* Platidoma */
define("NETCAT_MODULE_PAYMENT_PLATIDOMA_ERROR_PGSHOPID_IS_NOT_VALID", "Параметр 'pd_shop_id' должен быть установлен");
define("NETCAT_MODULE_PAYMENT_PLATIDOMA_ERROR_PGLOGIN_IS_NOT_VALID", "Параметр 'pd_login' должен быть установлен");
define("NETCAT_MODULE_PAYMENT_PLATIDOMA_ERROR_PGGATEPASSWORD_IS_NOT_VALID", "Параметр 'pd_gate_password' должен быть установлен");
define("NETCAT_MODULE_PAYMENT_PLATIDOMA_ERROR_PRIVATE_SECURITY_KEY_IS_NULL", "Параметр 'PrivateSecurityKey' должен быть установлен");
define("NETCAT_MODULE_PAYMENT_PLATIDOMA_ERROR_PRIVATE_KEY_IS_NOT_VALID", "Некорректный параметр 'pd_rnd'");
define("NETCAT_MODULE_PAYMENT_PLATIDOMA_ERROR_PRIVATE_SECURITY_IS_NOT_VALID", "Некорректный параметр 'pd_sign'");
define("NETCAT_MODULE_PAYMENT_PLATIDOMA_ERROR_ORDER_ID_IS_LONG", "Длина параметра 'OrderId' должна быть меньше 50");

/* QIWI */
define("NETCAT_MODULE_PAYMENT_QIWI_ERROR_AMOUNT_TOO_LARGE", "Максимальная сумма платежа — 15 000 рублей");
define("NETCAT_MODULE_PAYMENT_QIWI_ERROR_QIWI_FORM", "Параметр 'QiwiForm' должен быть числом");

/* Robokassa */
define("NETCAT_MODULE_PAYMENT_ROBOKASSA_ERROR_MRCHLOGIN_IS_NOT_VALID", "Некорректный параметр 'MrchLogin'");
define("NETCAT_MODULE_PAYMENT_ROBOKASSA_ERROR_INVID_IS_NOT_VALID", "Параметр 'InvId' должен быть числом");
define("NETCAT_MODULE_PAYMENT_ROBOKASSA_ERROR_INVDESC_ID_IS_LONG", "Длина параметра 'InvDesc' должна быть меньше 100");
define("NETCAT_MODULE_PAYMENT_ROBOKASSA_ERROR_PRIVATE_SECURITY_IS_NOT_VALID", "Некорректный параметр 'SignatureValue'");

/* Webmoney */
define("NETCAT_MODULE_PAYMENT_WEBMONEY_ERROR_PURSE_IS_NOT_VALID", "Некорректный параметр 'LMI_PAYEE_PURSE'");
define("NETCAT_MODULE_PAYMENT_WEBMONEY_ERROR_PRIVATE_SECURITY_IS_NOT_VALID", "Некорректный параметр 'LMI_HASH'");
define("NETCAT_MODULE_PAYMENT_WEBMONEY_ERROR_ORDER_ID_IS_LONG", "Длина параметра 'OrderId' должна быть меньше 50");

/* Yandex_Email */
define("NETCAT_MODULE_PAYMENT_YANDEX_EMAIL_ERROR_RECEIVER", "Параметр 'Receiver' должен быть установлен");

/* Yandex CPP */
define("NETCAT_MODULE_PAYMENT_YANDEX_CPP_ERROR_SHOPID_IS_NOT_VALID", "Некорректный параметр 'shopId'");
define("NETCAT_MODULE_PAYMENT_YANDEX_CPP_ERROR_SCID_IS_NOT_VALID", "Некорректный параметр 'scid'");
define("NETCAT_MODULE_PAYMENT_YANDEX_CPP_ERROR_SHOP_PASSWORD_IS_NOT_VALID", "Некорректный параметр 'shopPassword'");
define("NETCAT_MODULE_PAYMENT_YANDEX_CPP_ERROR_ORDER_ID_IS_NOT_VALID", "Некорректный параметр 'orderNumber'");
define("NETCAT_MODULE_PAYMENT_YANDEX_CPP_ERROR_AMOUNT", "Параметр 'Amount' должен быть числом");
define("NETCAT_MODULE_PAYMENT_YANDEX_CPP_ERROR_PRIVATE_SECURITY_IS_NOT_VALID", "Некорректный параметр 'md5'");

/* Platron */
define("NETCAT_MODULE_PAYMENT_PLATRON_ERROR_MERCHANT_ID_IS_NOT_VALID", "Некорректный параметр 'merchant_id'");
define("NETCAT_MODULE_PAYMENT_PLATRON_ERROR_SECRET_KEY_IS_NOT_VALID", "Некорректный параметр 'secret_key'");
define("NETCAT_MODULE_PAYMENT_PLATRON_ERROR_CURRENCY_IS_NOT_VALID", "Некорректная валюта");
define("NETCAT_MODULE_PAYMENT_PLATRON_ERROR_SIGN_IS_NOT_VALID", "Некорректная подпись");