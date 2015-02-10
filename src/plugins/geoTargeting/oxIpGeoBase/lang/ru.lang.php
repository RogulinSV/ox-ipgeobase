<?php

$translation = array();

$translation['geo_settings'] = 'Геотаргетинг';
$translation['import_data'] = 'Импорт данных';
$translation['cities_list'] = 'Список городов';

$translation['form_button_submit_name'] = 'Отправить';
$translation['form_error_wrong_input_params'] = 'Некорректные данные в форме';

$translation['form_import_header'] = 'Форма для ручного импорта геоданных';
$translation['form_import_description_name'] = 'Краткое описание';
$translation['form_import_description_hint'] = 'Форма для ручного импорта геоданных. Для запуска проццедуры импорта геоданных укажите путь до файла с данными, заполните поле почтовым адресом, на который впоследствии будет отправлен отчет о выполнении задачи, и нажмите на кнопку "Отправить". Если процедура импорта должна быть запущена немедленно, отметьте опцию "Начать процесс".';
$translation['form_import_field_upload_name'] = 'Загрузить файл с геоданными';
$translation['form_import_field_upload_hint'] = 'Вы должны указать путь до файла';
$translation['form_import_field_email_name'] = 'Почтовый адрес для уведомлений';
$translation['form_import_field_confirm_name'] = 'Начать процесс';
$translation['form_import_field_confirm_hint'] = 'Отметьте опцию, если процесс импорта необходимо начать сразу после загрузки файла';

$translation['form_import_error_requirements'] = 'Конфигурация сервера не удовлетворяет требованиям';
$translation['form_import_error_still_running'] = 'В настоящий момент не завершена обработка некоторых задач';
$translation['form_import_error_still_awaiting'] = 'К настоящему моменту присутствуют необработанные задачи';
$translation['form_import_error_still_not_completed'] = 'Некоторые задачи еще не обработаны до конца';
$translation['form_import_error_not_uploaded'] = 'Файл с архивом не загружен';
$translation['form_import_error_invalid_email'] = 'Почтовый адрес указан некорректно';
$translation['form_import_error_not_scheduled'] = 'Не удалось добавить задачу в планировщик';
$translation['form_import_message_success'] = 'Файл с архивом успешно загружен';

$translation['form_cities_header'] = 'Форма для поиска IP адреса';
$translation['form_cities_description_name'] = 'Краткое описание';
$translation['form_cities_description_hint'] = 'Форма предназначена для поиска (и проверки по стране/городу) IP адреса в базе данных. Для простого поиска IP адреса укажите искомый адрес в поле "IP адрес" и нажимет кнопку "Отправить". Если требуется проверить принадлежность IP адреса стране/городу, так же выберите страну/город в соответствующем списке "Города"';
$translation['form_cities_field_select_default'] = '--- Выбрать ---';
$translation['form_cities_field_select_name'] = 'Страна / город';
$translation['form_cities_field_ip_hint'] = 'Вы должны указать корректный IPv4 адрес';
$translation['form_cities_field_ip_name'] = 'IP адрес';
$translation['form_cities_field_bounds_name'] = 'IP границы';

$translation['form_error_wrong_ip_address'] = 'Некорректный IP адрес указан';
$translation['form_error_ip_not_found'] = 'IP %IP% не найден';
$translation['form_error_wrong_city'] = 'Некорректный город указан';
$translation['form_error_wrong_country'] = 'Некорректная страна указана';
$translation['form_cities_message_success'] = 'IP адрес найден';

$translation['table_scheduled_title'] = 'Списко запланированных задач';
$translation['table_scheduled_status_title'] = 'Статус задачи';
$translation['table_scheduled_task_created_title'] = 'Дата создания задачи';
$translation['table_scheduled_task_opened_title'] = 'Дата начала обработки';
$translation['table_scheduled_task_closed_title'] = 'Дата завершения обработки';
$translation['table_scheduled_author_title'] = 'Автор задачи';

$translation['cancel_task_hint'] = 'Отменить обработку запланированной задачи';
$translation['cancel_task_confirm'] = 'Вы действительно желаете отменить обработку запланированной задачи?';
$translation['action_cancel_task_onsuccess'] = 'Запланированная задача %ID% успешно отменена';
$translation['action_cancel_task_onfailure'] = 'Не удалось отменить запланированную задачу %ID%';

$translation['settings_error_invalid_url'] = 'Опция "%LABEL%" должна быть указана как корректный URL';
$translation['settings_error_invalid_archive_path'] = 'Опция "%LABEL%" должна быть указана как абсолютный путь до файла в архиве с ведущим слешем';
$translation['settings_error_invalid_number'] = 'Опция "%LABEL%" должна быть указана как целое положительное число';
$translation['settings_error_invalid_proxy'] = 'Не удалось проверить соединение через указанный прокси, проверьте значения опций "%PROXY%", "%AUTH_NAME%" и "%AUTH_PASS%"';

$translation['maintenance_notification_subject_onfailure'] = 'OpenX IPGeoBase: обслуживающий модуль завершил работу с ОШИБКАМИ';
$translation['maintenance_notification_content_onfailure'] = 'В процессе работы обслуживающего модуля были обнаружены ошибки:'.PHP_EOL.'%ERRORS%';
$translation['maintenance_notification_subject_onsuccess'] = 'OpenX IPGeoBase: обслуживающий модуль успешно завершил работу';
$translation['maintenance_notification_content_onsuccess'] = 'В процессе работы обслуживающего модуля ошибок не обнаружено, задача завершена успешно';

return $translation;