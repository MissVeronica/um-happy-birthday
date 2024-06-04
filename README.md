# UM Happy Birthday 2
Extension to Ultimate Member for Birthday greeting emails and optional mobile SMS texts.

## UM Settings -> Email -> Happy Birthday
1. Enable/Disable - Enable this email notification
2. Subject
3. Email Content

## UM Settings -> Extensions -> Happy Birthday
### WP Cronjob
1.  * Activate the Happy Birthday WP Cronjob - Click to activate the Plugin's WP Cronjob
2.  * Send Happy Birthday greetings during this hour or later - Select the hour during the day when the Happy Birthday plugin first will try to send greetings to the User. New sending test each hour if plugin or email/WP-SMS been inactive. New sending test also during next hour if additional Account Status or Roles are selected or "Resend" is applied from the WP All Users page and UM Action dropdown.
### User selections
3.  * User Account statuses to include - Select the Account statuses to receive the Happy Birthday greeting
4.  * Priority User Roles to include - Select the Priority User Roles to receive the Happy Birthday greeting
### Backend  Celebrant lists
5.  * Select to show the User Celebrant list - Click to get a list of each Celebrant User name for the Celebrant listing at this page and UM Dashboard modal if activated.
6.  * Select Name in celebrant list - Select the User name for the Celebrant listing at this page and UM Dashboard modal if activated.
7.  * Activate the UM Dashboard modal - Click to activate the UM Dashboard modal for Happy Birthday.
### Email greetings
8.  *  Activate sending emails - Click to enable the WP Cronjob sending Happy Birthday emails.
9.  *  Select email sending speed - Select which speed to send the greetings emails in number of emails sent per hour.
10.  *  WP Mail or SMTP - Click if you are using WP Mail and not a SMTP transport service for your emails.
11.  * Select delay in seconds for WP Mail - Select the delay in seconds between each greetings email being sent via WP Mail.
### WP SMS text greetings - Optional
12.  * Activate sending WP SMS - Click to enable the WP Cronjob sending Happy Birthday mobile SMS text greeting instead of an email if User registered with Mobile number
13.  * Activate sending flash WP SMS - Click to enable the WP Cronjob sending Happy Birthday flash SMS text greeting
14.  * WP SMS text greeting - Enter your Happy Birthday SMS text greeting and you can use UM email placeholders and {today}, {age}, {user_id}, {mobile_number}
### Daily Admin summary email
15.  * Activate Admin info email - Click to enable the Admin info email sent after each batch of greetings emails.
16.  * Select info in Admin email - Select the information fields about the celebrant to include in the Admin info email.
### Members Directory for display of Celebrants
17.  * Select Members Directory form - Select the Members Directory form for display of celebrants.
18.  * URL to Members Directory page - Enter the URL to the Members Directory page for display of celebrants.
### User Accoun page setting
19.  * Allow users to enable/disable greetings - Click to allow Users to enable/disable greetings at their Account page.

## WP All Users
1. UM Action: Resend Happy Birthday greetings
2. For additional User columns use this plugin https://github.com/MissVeronica/um-additional-user-columns

## User Account page
1. Do you want to receive birthday greetings? - Enable/Disable birthday greetings via email or SMS text message

## UM Predefined fields
1. Happy Birthday last greeted
2. Happy Birthday last greeted status
3. Happy Birthday last greeted error

## Options WP SMS
1. Requires the "WP SMS – Messaging, SMS & MMS Notifications, 2FA & OTP for WordPress" plugin
2. https://wordpress.org/plugins/wp-sms/
3. In addition to this "WP SMS" plugin you need a local SMS gateway provider ( approx 300 supported by the ""WP SMS" plugin ), which will charge you per SMS sent.
4. Failure to send SMS text greeting will send email greeting instead.

## Members Directory
1. Create a separate Members Directory for displaying Celebrants today and in the delta interval +7/-7 days with the URL <code>.../um-happy-birthday/?delta=-1</code>
2. Add your Members Directory shortcode to the WP page with the slug <code>um-happy-birthday</code>
3. The plugin is creating links from the Celebrant summary modal in UM Dashboard and Plugin settings.

## Translations
1. Use the "Loco Translate" plugin.
2. https://wordpress.org/plugins/loco-translate/

## Updates
1. Github version update status is checked once each day.
2. Current Version 2.0.0 

## References
1. WP Cron:  https://developer.wordpress.org/plugins/cron/

## Installation
1. Install by downloading the plugin ZIP file and install as a new Plugin, which you upload in WordPress -> Plugins -> Add New -> Upload Plugin.
2. Activate the Plugin: Ultimate Member - Happy Birthday

