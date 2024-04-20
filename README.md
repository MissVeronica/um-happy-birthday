# UM Happy Birthday
Extension to Ultimate Member for Birthday greeting emails

## UM Settings - Emails
1. "Happy Birthday" notification email
2. Template is saved to active theme's folder: <code>ultimate-member/email/um_greet_todays_birthdays.php</code>
3. If you don't have custom email templates already you must create active theme's folder: <code>ultimate-member/email</code>
4. Happy Birthday - Send email during this hour - Select the hour when the Happy Birthday email will be sent to the User.

## Email Placeholders
1. {display_name}
2. {first_name}
3. {last_name}
4. {title}
5. {today} in Y/m/d format
6. {usermeta:here_any_usermeta_key}

## UM meta_key
1. <code>um_birthday_greeted_last</code> in Y/m/d format when last email "Happy Birthday" was sent to the User.

## WP cron job
1. Name: <code>um_cron_birthday_greet_notification</code>
2. Schedule: Hourly
3. Management plugin: Advanced Cron Manager https://wordpress.org/plugins/advanced-cron-manager/

## Updates
1. Version 1.1.0 Code improvements
2. Version 1.2.0 Removed email conflict and Updated for UM 2.8.3
3. Version 1.3.0/1.3.1 Support for UM 2.8.5
4. Version 1.4.0 Addition of Hour when the email will be sent

## Installation
1. Install by downloading the plugin ZIP file and install as a new Plugin, which you upload in WordPress -> Plugins -> Add New -> Upload Plugin.
2. Activate the Plugin: Ultimate Member - Happy Birthday
