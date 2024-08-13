# UM Happy Birthday 2
Extension to Ultimate Member for Birthday greeting emails and optional mobile SMS texts.

## UM Settings -> Email -> Happy Birthday
1. Enable/Disable - Enable this email notification
2. Subject
3. Email Content
4. Valid UM email placeholders. https://docs.ultimatemember.com/article/1340-placeholders-for-email-templates
5. Additional placeholders: {today}, {age}, {user_id}, {mobile_number}
6. For User Role dependant email content, use the "Email Parse Shortcode" plugin. https://github.com/MissVeronica/um-email-parse-shortcode

## UM Settings -> Extensions -> Happy Birthday
### WP Cronjob
1.  * Activate the Happy Birthday WP Cronjob - Click to activate the Plugin's WP Cronjob
2.  * Send Happy Birthday greetings during this hour or later - Select the hour during the day when the Happy Birthday plugin first will try to send greetings to the User. New sending test each hour if plugin or email/WP-SMS been inactive. New sending test also during next hour if additional Account Status or Roles are selected or "Resend" is applied from the WP All Users page and UM Action dropdown.
### User selections
3.  * User Account statuses to include - Select the Account statuses to receive the Happy Birthday greeting
4.  * Priority User Roles to include - Select the Priority User Roles to receive the Happy Birthday greeting
5.  * Current old Users default consent is "No" - Select to include current old users without having selected "Yes" in Account page as accepting birthday greetings. - These old Users are displayed as "Default" and accept or reject in the Status count.
### Backend Celebrant lists
6.  * Select to show the User Celebrant list - Click to get a list of each Celebrant User name for the Celebrant listing at this page and UM Dashboard modal if activated.
7.  * Select Name in celebrant list - Select the User name for the Celebrant listing at this page and UM Dashboard modal if activated.
8.  * Activate the UM Dashboard modal - Click to activate the UM Dashboard modal for Happy Birthday.
### Email greetings
9.  *  Activate sending emails - Click to enable the WP Cronjob sending Happy Birthday emails.
10.  *  Select email sending speed - Select which speed to send the greetings emails in number of emails sent per hour.
11.  *  WP Mail or SMTP - Click if you are using WP Mail and not a SMTP transport service for your emails.
12.  * Select delay in seconds for WP Mail - Select the delay in seconds between each greetings email being sent via WP Mail.
### WP SMS text greetings - Optional
13.  * Activate sending WP SMS - Click to enable the WP Cronjob sending Happy Birthday mobile SMS text greeting instead of an email if User registered with Mobile number
14.  * Activate sending flash WP SMS - Click to enable the WP Cronjob sending Happy Birthday flash SMS text greeting
15.  * WP SMS text greeting - Enter your Happy Birthday SMS text greeting and you can use UM email placeholders and {today}, {age}, {user_id}, {mobile_number}
### Daily Admin summary email
16.  * Activate Admin info email - Click to enable the Admin info email sent after each batch of greetings emails.
17.  * Select info in Admin email - Select the information fields about the celebrant to include in the Admin info email.
### Members Directory for display of Celebrants
18.  * Select Members Directory form - Select the Members Directory form for display of celebrants.
19.  * URL to Members Directory page - Enter the URL to the Members Directory page for display of celebrants.
### User Account page setting
20.  * Allow users to enable/disable greetings - Click to allow Users to enable/disable greetings at their Account Privacy page.
### Birthday Celebration icons
21.  * Birthday Celebration icon - Click to enable a Birthday Celebration icon at the User Profile page after the Celebrant's name.
22.  * Select Birthday Celebration icon - Default Birthday Celebration icon is "A Cake with three Candles". Current Birthday Celebration icon:
23.  * Birthday Celebration icon color - Enter the color for the Birthday Celebration icon either by the color name or HEX code. Default color is "white". W3Schools HTML Color Groups
24.  * Birthday Celebration icon size - Enter the size value in pixels for the Birthday Celebration icon, default value is 40.

## WP All Users
1. UM Action: Resend Happy Birthday greetings
2. For additional User columns use this plugin https://github.com/MissVeronica/um-additional-user-columns

## User Account Page - Privacy
1. Do you want to receive birthday greetings? - Enable/Disable birthday greetings via email or SMS text message inclusive a Celebration icon at the Profile page

## User Registration Page
1. "Happy Birthday greetings consent" field from UM Predefined fields

## UM Predefined Form Builder fields
1. Happy Birthday last greeted
2. Happy Birthday last greeted status
3. Happy Birthday last greeted error
4. Happy Birthday greetings consent

## Options WP SMS
1. Requires the "WP SMS – Messaging, SMS & MMS Notifications, 2FA & OTP for WordPress" plugin
2. https://wordpress.org/plugins/wp-sms/
3. In addition to this "WP SMS" plugin you need a local SMS gateway provider ( approx 300 supported by the "WP SMS" plugin ), which will charge you per SMS sent.
4. Failure to send SMS text greeting will send email greeting instead.

## Members Directory
1. Create a separate Members Directory for displaying Celebrants today and in the delta interval +7/-7 days with the URL <code>.../um-happy-birthday/?delta=-1</code>
2. Add your Members Directory shortcode to the WP page with the slug <code>um-happy-birthday</code>
3. The plugin is creating links from the Celebrant summary modal in UM Dashboard and Plugin settings.
4. Supports the <code>[birthdays_today]</code> shortcode.

## WP Appearance -> Menus
1. Add the Members Directory links to the menu choices like <code>.../um-happy-birthday/</code> for today's birthdays

## Shortcode
1. <code>[birthdays_today]</code> or <code>[birthdays_today]enter link text here[/birthdays_today]</code>  for use in User Profile pages
2. Creates a link to the Birtdays Directory page for current day with the default text "Happy Birthdays today"
3. Restriction: Link is only displayed at current User's own Profile page i.e. not displayed when visiting other Profiles.
4. The shortcode can be used independent of WP CronJob sending email or SMS greetings or not.

## Translations & Text changes
1. Use the "Loco Translate" plugin.
2. https://wordpress.org/plugins/loco-translate/
3. For a few changes of text use the "Say What?" plugin with text domain happy-birthday
4. https://wordpress.org/plugins/say-what/

## Birthday Celebration Icon Filter
Code snippet:
<code>
add_filter( 'happy_birthday_icons', 'happy_birthday_icons_star', 10, 1 );
function happy_birthday_icons_star( $array ) {
    $array['fas fa-face-surprise'] = 'Face surprise solid';
    $array['far fa-face-surprise'] = 'Face surprise regular';
    return $array;
}
</code>
Install by adding the code snippet to your active theme's functions.php file or use the "Code Snippets" Plugin

## Updates
1. Version 2.0.0 Github update status is checked once each day.
2. Version 2.1.0 Code improvements
### Version 2.2.0  
1. UM predefined Form Builder field checkbox: Happy Birthday greetings consent, meta_key: um_birthday_greetings_consent, value: current date if accepted otherwise empty
2. User Registration consent updates the Account page Birthday greetings Privacy setting which User can edit later
3. Consent Status counts for the site added to the Modal and plugin Settings.
4. Old Users without a Registration consent can either be set to "Yes" or "No" for the Privacy default value in Account page.
5. Account page privacy settings only displayed for Users selected by their User role.
### Version 2.3.0
1. Birthday Cake with Candles at the celebrants Profile pages after Profile user name
2. Display of Birthday Cake with Candles disabled for Accounts denying Birthday greetings emails.
### Version 2.4.0
1. New Shortcode for User display of today's birthdays.
### Version 2.4.1     
1. Code improvement version update text
### Version 2.5.0     
1. Update of the "Birthday Celebration icon" feature with a selection of 40+ additional free WP icons from "Font Awesome".
2. Filter hook "happy_birthday_icons" for custom addition of "Font Awesome" free WP icons.
3. Code improvements  

## References
1. WP Cron:  https://developer.wordpress.org/plugins/cron/
2. Font Awesome: https://docs.fontawesome.com/web/add-icons/how-to
3. "Code Snippets" Plugin: https://wordpress.org/plugins/code-snippets/

## Installation & Updates
1. Install or Update by downloading the plugin ZIP file at the green Code button
2. Install as a new WP Plugin Upload in WordPress -> Plugins -> Add New -> Upload Plugin.
3. Activate the Plugin: Ultimate Member - Happy Birthday
