# UM Happy Birthday
Extension to Ultimate Member for Birthday greeting emails

## UM Settings - Emails
1. "Happy Birthday" notification email
2. Template is saved to active theme's folder: <code>ultimate-member/email/um_greet_todays_birthdays.php</code>

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
None

## Installation
1. Install by downloading the plugin ZIP file and install as a new Plugin, which you upload in WordPress -> Plugins -> Add New -> Upload Plugin.
2. Activate the Plugin: Ultimate Member - Happy Birthday
