<?php

$translation = array();

$translation['geo_settings'] = 'Geo Settings';
$translation['import_data'] = 'Import data';
$translation['cities_list'] = 'Cities list';

$translation['form_button_submit_name'] = 'Submit';
$translation['form_error_wrong_input_params'] = 'Incorrect form data';

$translation['form_import_header'] = 'Form for manual import geo data';
$translation['form_import_description_name'] = 'Short description';
$translation['form_import_description_hint'] = 'Form for manual import geo data. To start importing the data, you must specify the path to the files, specify the email address to which the report will be sent upon completion of the procedure, and click "Submit" button. If the import process must be restarted immediately, check the option "Start process".';
$translation['form_import_field_upload_name'] = 'Upload IpGeoBase Archive';
$translation['form_import_field_upload_hint'] = 'You must select a file';
$translation['form_import_field_email_name'] = 'Email for notification';
$translation['form_import_field_confirm_name'] = 'Start process';
$translation['form_import_field_confirm_hint'] = 'check this option if process must start immediately after download the file';

$translation['form_import_error_requirements'] = 'Server configuration does not meet the requirements';
$translation['form_import_error_still_running'] = 'Some scheduled tasks still running';
$translation['form_import_error_still_awaiting'] = 'Some scheduled tasks awaiting to run';
$translation['form_import_error_still_not_completed'] = 'Some scheduled tasks not completed';
$translation['form_import_error_not_uploaded'] = 'Archive file not uploaded';
$translation['form_import_error_invalid_email'] = 'Email is not valid';
$translation['form_import_error_not_scheduled'] = 'Unable to add task in scheduler';
$translation['form_import_message_success'] = 'Archive file successfully uploaded';

$translation['form_cities_header'] = 'Form for searching IPv4 address';
$translation['form_cities_description_name'] = 'Short description';
$translation['form_cities_description_hint'] = 'Form is used to search for (and checking on the country / city) IP addresses in the database. For a simple search, specify the IP address of the desired address in the "IP address" and click "Send" button. If you want to check whether the IP address of the country / city, just choose your country / city in the corresponding list of "Cities"';
$translation['form_cities_field_select_default'] = '--- Choose ---';
$translation['form_cities_field_select_name'] = 'Country / city';
$translation['form_cities_field_ip_hint'] = 'You must specify correct IPv4 address';
$translation['form_cities_field_ip_name'] = 'IP address';
$translation['form_cities_field_bounds_name'] = 'IP bounds';

$translation['form_error_wrong_ip_address'] = 'Wrong IP address';
$translation['form_error_ip_not_found'] = 'IP %IP% not found';
$translation['form_error_wrong_city'] = 'Wrong city';
$translation['form_error_wrong_country'] = 'Wrong country';
$translation['form_cities_message_success'] = 'IP matched';

$translation['table_scheduled_title'] = 'List of scheduled tasks';
$translation['table_scheduled_status_title'] = 'Task Status';
$translation['table_scheduled_task_created_title'] = 'Task Creation Date';
$translation['table_scheduled_task_opened_title'] = 'Opening Date of the Task';
$translation['table_scheduled_task_closed_title'] = 'Closing Date of the Task';
$translation['table_scheduled_author_title'] = 'Author of the Task';

$translation['cancel_task_hint'] = 'Cancel scheduled task';
$translation['cancel_task_confirm'] = 'Do you really want to cancel scheduled task?';
$translation['action_cancel_task_onsuccess'] = 'Scheduled task %ID% successfully canceled';
$translation['action_cancel_task_onfailure'] = 'Unable to cancel scheduled task %ID%';

$translation['settings_error_invalid_url'] = 'The option "%LABEL%" should be specified as a valid URL address';
$translation['settings_error_invalid_archive_path'] = 'The option "%LABEL%" should be specified as a path to the file in the archive with a leading slash';
$translation['settings_error_invalid_number'] = 'The option "%LABEL%" should be specified as a positive integer';
$translation['settings_error_invalid_proxy'] = 'Unable to verify the connection through a proxy, check the options "%PROXY%", "%AUTH_NAME%" and "%AUTH_PASS%"';

$translation['maintenance_notification_subject_onfailure'] = 'OpenX IPGeoBase maintenance has been finished with ERRORS';
$translation['maintenance_notification_content_onfailure'] = 'Errors was detected while processing maintenance tasks:'.PHP_EOL.'%ERRORS%';
$translation['maintenance_notification_subject_onsuccess'] = 'OpenX IPGeoBase maintenance has been successfully finished';
$translation['maintenance_notification_content_onsuccess'] = 'Maintenance tasks successfully processed without any error';

return $translation;