<?php
/**
 * Plugin Name:         Ultimate Member - Happy Birthday
 * Description:         Extension to Ultimate Member for Birthday greeting emails and optional mobile SMS texts.
 * Version:             2.3.0
 * Requires PHP:        7.4
 * Author:              Miss Veronica
 * License:             GPL v3 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:          https://github.com/MissVeronica
 * Plugin URI:          https://github.com/MissVeronica/um-happy-birthday
 * Update URI:          https://github.com/MissVeronica/um-happy-birthday
 * Text Domain:         happy-birthday
 * Domain Path:         /languages
 * UM version:          2.8.6
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

class UM_Happy_Birthday {

    public $wp_cron_event           = 'um_cron_birthday_greet_notification';
    public $slug                    = 'um_greet_todays_birthdays';
    public $last_greeted            = 'um_birthday_greeted_last';
    public $last_greeted_status     = 'um_birthday_greeted_last_status';
    public $last_greeted_error      = 'um_birthday_greeted_last_error';
    public $greetings_consent       = 'um_birthday_greetings_consent';

    public $send_to_email           = false;
    public $cronjob                 = false;
    public $email_send_failure      = false;
    public $sms_send_failure        = false;
    public $dashboard               = false;

    public $sms_send_counter        = 0;
    public $sms_failure_counter     = 0;
    public $email_send_counter      = 0;
    public $email_failure_counter   = 0;
    public $email_speed             = 0;
    public $lines_limit             = 4;

    public $sent_user_list          = array();
    public $happy_birthday_args     = array();
    public $content_admin_email     = array();
    public $description             = array();
    public $plugin_status           = array();
    public $prio_roles              = array();
    public $all_selected_user_roles = array();
    public $privacy_options         = array();

    public $sms_info                = '';
    public $sms_text_message        = '';
    public $display_name            = '';
    public $mobile_number           = '';
    public $today                   = '';
    public $email_send_status       = '';
    public $sms_send_status         = '';
    public $new_plugin_version      = '';
    public $celebrants_today        = '';
    public $celebrants_summary      = '';

    public $transient_life          = 5 * DAY_IN_SECONDS;
    public $px                      = '40';
    public $cake_color              = 'white';

    public $account_status   = array(
                                    'approved'                    => 'Approved',
                                    'awaiting_email_confirmation' => 'Email Confirmation',
                                    'awaiting_admin_review'       => 'Admin Review',
                                    'inactive'                    => 'Inactive',
                                    'rejected'                    => 'Rejected',
                                );

    public $html_allowed = array(
                                    'a'     => array(
                                                    'href'   => array(),
                                                    'target' => true,
                                                    'title'  => true,
                                                    'style'  => true,
                                                    ),
                                    'style' => array(),
                                    'br'    => array(),
                                    'hr'    => array(),
                                    'span'  => array(
                                                    'style' => true,
                                                    'title' => true,
                                                    ),
                                    'table' => array(),
                                    'td'    => array(
                                                    'style'   => true,
                                                    'colspan' => true,
                                                    ),
                                    'tr'    => array(
                                                    'style'   => true,
                                                    ),
                                );



    function __construct() {

        add_filter( 'um_email_notifications',                        array( $this, 'um_email_notifications' ), 10, 1 );
        add_action( 'um_extend_admin_menu',                          array( $this, 'copy_email_notifications_happy_birthday' ), 10 );
        add_filter( 'um_settings_structure',                         array( $this, 'um_admin_settings_email_section_happy_birthday' ), 10, 1 );

        add_filter( 'um_admin_bulk_user_actions_hook',               array( $this, 'um_admin_bulk_user_actions_resend_happy_birthday' ), 10, 1 );
        add_action( 'um_admin_custom_hook_happy_birthday_greetings', array( $this, 'um_admin_custom_hook_happy_birthday_greetings_resend' ), 10, 1 );
        add_filter( 'um_account_tab_privacy_fields',                 array( $this, 'um_account_tab_privacy_fields_happy_birthday' ), 10, 2 );

        add_action( 'um_prepare_user_query_args',                    array( $this, 'um_happy_birthday_directories' ), 10, 2 );
        add_filter( 'um_predefined_fields_hook',                     array( $this, 'um_predefined_fields_hook_happy_birthday' ), 10, 1 );
        add_filter( 'um_predefined_fields_hook',                     array( $this, 'custom_predefined_fields_happy_birthday' ), 10, 1 );

        add_filter( 'um_pre_args_setup',                             array( $this, 'um_pre_args_setup_happy_birthday' ), 10, 1 );
        add_action( 'plugins_loaded',                                array( $this, 'um_happy_birthday_plugin_loaded' ), 0 );
        add_action( 'um_registration_set_extra_data',                array( $this, 'um_registration_set_happy_birthday_account_consent' ), 10, 3 );

        add_action( 'um_account_pre_update_profile',                 array( $this, 'um_account_pre_update_profile_happy_birthday_account_consent' ), 10, 2 );
        add_action( 'um_after_profile_name_inline',                  array( $this, 'um_after_profile_name_show_cake_candles' ), 10, 1 );


        if ( UM()->options()->get( $this->slug . '_modal_list' ) == 1 ) {
		    add_action( 'load-toplevel_page_ultimatemember',         array( $this, 'load_metabox_happy_birthday' ) );
        }

        if ( UM()->options()->get( $this->slug . '_on' ) == 1 && UM()->options()->get( $this->slug . '_active' ) == 1 ) {

            add_action( $this->wp_cron_event,                        array( $this, 'um_cron_task_birthday_greet_notification' ));

            if ( ! wp_next_scheduled ( $this->wp_cron_event ) ) {
                wp_schedule_event( time(), 'hourly', $this->wp_cron_event );
            }
        }

        $this->privacy_options = array(
                                        'no'  => __( 'No',  'happy-birthday' ),
                                        'yes' => __( 'Yes', 'happy-birthday' ),
                                    );

        define( 'Happy_Birthday_Path', plugin_dir_path( __FILE__ ) );
        define( 'um_happy_birthday_textdomain', 'happy-birthday' );
    }

    public function um_happy_birthday_plugin_loaded() {

        $locale = ( get_locale() != '' ) ? get_locale() : 'en_US';
        load_textdomain( um_happy_birthday_textdomain, WP_LANG_DIR . '/plugins/' . um_happy_birthday_textdomain . '-' . $locale . '.mo' );
        load_plugin_textdomain( um_happy_birthday_textdomain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    public function load_metabox_happy_birthday() {

        $this->celebrants_summary = $this->prepare_status_listing();

        add_meta_box(   'um-metaboxes-sidebox-happy-birthday',
                        sprintf( __( 'Happy Birthday - %s', 'happy-birthday' ), $this->celebrants_today ),
                        array( $this, 'toplevel_page_happy_birthday' ),
                        'toplevel_page_ultimatemember', 'side', 'core'
                    );
    }

    public function toplevel_page_happy_birthday() {

        $this->dashboard = true;
        ?>
        <div>
        <?php
            echo $this->celebrants_summary;
        ?>
        </div>
        <?php
    }

    public function um_after_profile_name_show_cake_candles( $args ) {

        if ( UM()->options()->get( $this->slug . '_cake_candles' ) == 1 ) {

            if ( strpos( um_user( 'birth_date' ), date_i18n( '/m/d', current_time( 'timestamp' )) ) !== false ) {

                if ( $this->get_user_account_consent_setting() !== false ) {

                    $title = __( 'Happy Birthday today', 'happy-birthday' );

                    $color = UM()->options()->get( $this->slug . '_cake_color' );
                    if ( ! empty( $color )) {
                        $this->cake_color = sanitize_text_field( $color );
                    }

                    $px = UM()->options()->get( $this->slug . '_cake_size' );
                    if ( ! empty( $px )) {
                        $this->px = str_replace( 'px', '', strtolower( sanitize_text_field( $px )));
                    }
?>
                    <span class="um-field-label-icon" 
                          style="font-size: <?php echo esc_attr( $this->px ); ?>px; color: <?php echo esc_attr( $color ); ?>;" 
                          title="<?php echo esc_attr( $title ); ?>">
                        <i class="fas fa-cake-candles"></i>
                    </span>
<?php
                }
            }
        }
    }

    public function get_user_account_consent_setting() {

        $current_consent = um_user( $this->slug . '_privacy' );
        $consent = false;

        if ( empty( $current_consent )) {

            if ( UM()->options()->get( $this->slug . '_without_consent' ) == 1 ) {
                $consent = true;
            }

        } else {

            if ( is_array( $current_consent ) && $current_consent[0] == 'yes' ) {
                $consent = true;
            } 
        }

        return $consent;
    }

    public function cron_job_settings() {

        $this->plugin_status = array();

        $url_email  = get_site_url() . '/wp-admin/admin.php?page=um_options&tab=email&email=um_greet_todays_birthdays';

        if ( UM()->options()->get( $this->slug . '_on' ) == 1 ) {

            if ( UM()->options()->get( $this->slug . '_active' ) == 1 ) {

                $settings = '';
                if ( $this->dashboard ) {

                    $url_plugin = get_site_url() . '/wp-admin/admin.php?page=um_options&tab=extensions&section=happy-birthday';
                    $settings = sprintf( ' <a href="%s">%s</a>', $url_plugin, __( 'settings', 'happy-birthday' ));
                }

                $this->plugin_status[] = __( 'Plugin is active', 'happy-birthday' ) . $settings;
                $this->plugin_status[] = sprintf( '<a href="%s">%s</a> %s ', $url_email, __( 'Happy Birthday', 'happy-birthday' ), __( 'email template is active', 'happy-birthday' ));

                $this->wp_cron_job_status();

                if ( UM()->options()->get( $this->slug . '_email' ) == 1 ) {

                    if ( defined( 'WP_SMS_DIR' ) && UM()->options()->get( $this->slug . '_sms' ) == 1 ) {
                        $this->plugin_status[] = __( 'Plugin will only send Happy Birthday email greetings to users with mobile numbers if the SMS text sending fails', 'happy-birthday' );

                    } else {
                        $this->plugin_status[] = sprintf( __( 'Plugin will send max %d Happy Birthday email greetings per hour', 'happy-birthday' ),
                        intval( UM()->options()->get( $this->slug . '_email_speed' )));
                    }

                } else {

                    $this->plugin_status[] = __( 'Plugin will NOT send any Happy Birthday email greetings', 'happy-birthday' );
                }

                if ( UM()->options()->get( $this->slug . '_flash_sms' ) == 1 ) {
                    $wp_sms = '<a href="https://wordpress.org/plugins/wp-twilio-core/" title="Twilio WP plugin page" target="_blank">WP flash SMS</a>';

                } else {
                    $wp_sms = '<a href="https://wordpress.org/plugins/wp-twilio-core/" title="Twilio WP plugin page" target="_blank">WP SMS</a>';
                }

                if ( defined( 'WP_SMS_DIR' ) ) {

                    if ( UM()->options()->get( $this->slug . '_sms' ) == 1 ) {
                            $this->plugin_status[] = sprintf( __( 'Plugin will try to send Happy Birthday "%s" text greetings', 'happy-birthday' ), $wp_sms );

                    } else {
                        $this->plugin_status[] = sprintf( __( 'Plugin will NOT try to send any Happy Birthday "%s" text greetings', 'happy-birthday' ), $wp_sms );
                    }

                } else {
                    $this->plugin_status[] = sprintf( __( 'The "%s" plugin is not installed or the plugin is deactivated', 'happy-birthday' ), $wp_sms );
                }

            } else {
                $this->plugin_status[] = __( 'Plugin is paused and not sending any Happy Birthday greetings', 'happy-birthday' );
            }

        } else {

            $this->plugin_status[] = sprintf( '<a href="%s">%s</a> %s ', $url_email, __( 'Happy Birthday', 'happy-birthday' ), __( 'email template is not active and greetings are disabled', 'happy-birthday' ));
        }
    }

    public function wp_cron_job_status() {

        $cron_job = wp_next_scheduled( $this->wp_cron_event );

        if ( ! empty( $cron_job )) {

            $minutes = intval(( $cron_job - time() ) / 60 );

            if ( $minutes > 0 ) {
                $this->plugin_status[] = sprintf( __( 'The Plugin WP Cronjob will execute next in about %d minutes', 'happy-birthday' ), $minutes );

            } else {
                $seconds = intval( $cron_job - time() );
                if ( $seconds > 0 ) {
                    $this->plugin_status[] = sprintf( __( 'The Plugin WP Cronjob will execute next in about %d seconds', 'happy-birthday' ), $seconds );

                } else {
                    $this->plugin_status[] = __( 'The Plugin WP Cronjob is waiting in the WP job queue', 'happy-birthday' );
                }
            }

        } else {
            $this->plugin_status[] = __( 'No active Plugin WP Cronjob for Happy Birthday messages', 'happy-birthday' );
        }
    }

    public function um_happy_birthday_wp_mail( $args ) {

        $this->send_to_email = $args['to'];

        return  $args;
    }

    public function um_happy_birthday_sms_gateway_info( $message_info ) {

        $this->sms_send_status = sprintf( __( 'SMS Gateway status: %s', 'happy-birthday' ), $message_info );
        $this->sms_send_counter++;
        $this->sms_send_failure = false;
    }

    public function um_admin_bulk_user_actions_resend_happy_birthday( $actions ) {

        if ( UM()->options()->get( $this->slug . '_email' ) == 1 ) {
            $actions['happy_birthday_greetings']  = array( 'label' => __( 'Resend Happy Birthday greetings', 'happy-birthday' ));
        }

        if ( defined( 'WP_SMS_DIR' ) && UM()->options()->get( $this->slug . '_sms' ) == 1 ) {
            $actions['happy_birthday_greetings']  = array( 'label' => __( 'Resend Happy Birthday greetings', 'happy-birthday' ));
        }

        return $actions;
    }

    public function um_admin_custom_hook_happy_birthday_greetings_resend( $user_id ) {

        $cron = new DateTime( date_i18n( 'Y-m-d H:i:s', wp_next_scheduled( $this->wp_cron_event ) + 3600 ), new DateTimeZone( 'UTC' ));
        $cron->setTimezone( new DateTimeZone( wp_timezone_string() ));

        if ( $cron->format( 'Y/m/d' ) == date_i18n( 'Y/m/d', current_time( 'timestamp' )) ) {

            update_user_meta( $user_id, $this->last_greeted, 'resend' );
            UM()->user()->remove_cache( $user_id );
        }
    }

    public function get_happy_birthday_meta_query( $account_status ) {

        $meta_query = array(
                            'relation' => 'AND',
                                array(
                                    'relation' => 'OR',
                                        array(
                                            'key'     => 'birth_date',
                                            'value'   => substr( $this->today, 4, 6 ),
                                            'compare' => 'LIKE',
                                        ),

                                        array(
                                            'key'     => 'birth_date',
                                            'value'   => str_replace( '/', '-', substr( $this->today, 4, 6 ) ),
                                            'compare' => 'LIKE',
                                        ),
                                    ),

                                array(
                                    'key'     => 'account_status',
                                    'value'   => $account_status,
                                    'compare' => 'IN',
                                ),

                                array(
                                    'relation' => 'OR',
                                        array(
                                            'key'     => $this->slug . '_privacy',
                                            'value'   => '"yes"',
                                            'compare' => 'LIKE',
                                        ),

                                        array(
                                            'key'     => $this->slug . '_privacy',
                                            'compare' => 'NOT EXISTS',
                                        ),
                                ),

                                array(
                                    'relation' => 'OR',
                                        array(
                                                'key'     => $this->last_greeted,
                                                'value'   => substr( $this->today, 0, 10 ),
                                                'compare' => 'NOT LIKE',
                                            ),

                                        array(
                                                'key'     => $this->last_greeted,
                                                'compare' => 'NOT EXISTS',
                                            ),
                                    ),
                            );

        if ( ! $this->cronjob ) {
            unset( $meta_query[3] );
        }

        if ( UM()->options()->get( $this->slug . '_without_consent' ) != 1 ) {
            unset( $meta_query[2][1] );
        }

        return $meta_query;
    }

    public function get_account_status() {

        $account_status = false;
        $status = UM()->options()->get( $this->slug . '_account_status' );

        if ( is_array( $status ) && ! empty( $status )) {
            $account_status = array_map( 'sanitize_text_field', $status );
        }

        return $account_status;
    }

    public function get_all_celebrants() {

        $celebrants = array();
        $account_status = $this->get_account_status();

        if ( ! empty( $account_status )) {

            $args = array(
                            'fields'     => 'ids',
                            'number'     => -1,
                            'meta_query' => $this->get_happy_birthday_meta_query( $account_status ),
                        );

            $celebrants = get_users( $args );
            $celebrants = $this->prepare_email_sms_greetings( $celebrants, substr( $this->today, 0, 10 ) );
            $celebrants = $this->selection_priority_user_roles( $celebrants );
        }

        return $celebrants;
    }

    public function prepare_email_sms_greetings( $celebrant_list, $today ) {

        $celebrants = array();
        $valid_values = array( $today, 'resend' );

        $email_activated = ( UM()->options()->get( $this->slug . '_email' ) == 1 ) ? true : false;
        $sms_activated   = ( UM()->options()->get( $this->slug . '_sms' )   == 1 && defined( 'WP_SMS_DIR' ) ) ? true : false;

        foreach( $celebrant_list as $user_id ) {
            um_fetch_user( $user_id );

            $last_greeted  = substr( um_user( $this->last_greeted ), 0, 10 );
            $mobile_number = ( $this->short_status( um_user( $this->last_greeted_status )) == 'sms failure' ) ? false : um_user( 'mobile_number' );

            if ( ! $mobile_number && $last_greeted == 'resend' ) {
                $mobile_number = um_user( 'mobile_number' );
            }

            if ( $this->cronjob ) {

                if ( $last_greeted != $today ) {

                    if ( $email_activated ) {
                        $celebrants[$user_id]['email'] = true;
                        $celebrants[$user_id]['sms']   = false;
                    }

                    if ( $sms_activated ) {

                        if ( ! empty( $mobile_number )) {
                            $celebrants[$user_id]['sms']   = true;
                            $celebrants[$user_id]['email'] = false;

                        } else {

                            if ( $email_activated ) {
                                $celebrants[$user_id]['sms']   = false;
                                $celebrants[$user_id]['email'] = true;

                            } else {
                                unset( $celebrants[$user_id] );
                                UM()->user()->remove_cache( $user_id );
                            }
                        }
                    }

                } else {

                    unset( $celebrants[$user_id] );
                    UM()->user()->remove_cache( $user_id );
                }

            } else {

                $celebrants[$user_id] = true;
            }
        }

        return $celebrants;
    }

    public function get_all_selected_user_roles() {

        if ( empty( $this->all_selected_user_roles ) ) {
            $this->all_selected_user_roles = UM()->options()->get( $this->slug . '_user_roles' );

            if ( is_array( $this->all_selected_user_roles )) {
                $this->all_selected_user_roles = array_map( 'sanitize_text_field', $this->all_selected_user_roles );
            }
        }
    }

    public function selection_priority_user_roles( $celebrants ) {

        $user_selection = array();
        if ( ! empty( $celebrants ) ) {

            $this->get_all_selected_user_roles();
            if ( ! empty( $this->all_selected_user_roles )) {

                foreach( $celebrants as $user_id => $type ) {

                    UM()->user()->remove_cache( $user_id );
                    um_fetch_user( $user_id );

                    $prio_role = UM()->roles()->get_priority_user_role( $user_id );
                    if ( in_array( $prio_role, $this->all_selected_user_roles )) {

                        $user_selection[$user_id] = $type;
                        $this->prio_roles[$user_id] = UM()->roles()->get_role_name( $prio_role );

                    } else {
                        UM()->user()->remove_cache( $user_id );
                    }
                }
                ksort( $user_selection );
            }
        }

        return $user_selection;
    }

    public function send_happy_birthday_info_admin() {

        if ( UM()->options()->get( $this->slug . '_admin' ) == 1 ) {

            if ( count( $this->sent_user_list ) > 0 ) {

                $status = array();

                if ( UM()->options()->get( $this->slug . '_email' ) == 1 ) {
                    $status[] = sprintf( __( 'Emails OK: %d',      'happy-birthday' ), intval( $this->email_send_counter ));
                    $status[] = sprintf( __( 'Email failures: %d', 'happy-birthday' ), intval( $this->email_failure_counter ));
                }

                if ( UM()->options()->get( $this->slug . '_sms' ) == 1 && defined( 'WP_SMS_DIR' ) ) {
                    $status[] = sprintf( __( 'SMS text OK: %d',       'happy-birthday' ), intval( $this->sms_send_counter ));
                    $status[] = sprintf( __( 'SMS text failures: %d', 'happy-birthday' ), intval( $this->sms_failure_counter ));
                }

                if ( ! empty( $status )) {

                    $status = '<br />' . implode( '<br />', $status ) . '<br />';

                    $url = $happy_birthday_form = sanitize_text_field( UM()->options()->get( $this->slug . '_um_form_url' ));

                    $link = substr( $this->today, 0, 10 );
                    if ( ! empty( $url )) {
                        $link = sprintf( '<a href="%s?date=%s" target="Happy_Birthday">%s</a>', $url, substr( $this->today, 0, 10 ), substr( $this->today, 0, 10 ));
                    }

                    $subject = wp_kses( sprintf( __( 'Birthday greetings today %s', 'happy-birthday' ), substr( $this->today, 0, 10 ) ), $this->html_allowed );

                    $body  = sprintf( __( 'Birthday greetings today %s', 'happy-birthday' ), $link ) . '<br />';
                    $body .= sprintf( __( 'Number of greetings sent in this batch: %s', 'happy-birthday' ), $status ) . '<br />';
                    $body .= implode( '<br />', $this->sent_user_list );
                    $body  = str_replace( array( '<br /><br /><br /><br />', '<br /><br /><br />' ), '<br /><br />', $body );

                    $body = wp_kses( $body, $this->html_allowed );

                    $headers = array();
                    $headers[] = 'Content-Type: text/html; charset=UTF-8';
                    $headers[] = sprintf( 'From: %s <%s>', esc_html( get_bloginfo( 'name' )), um_admin_email());

                    wp_mail( um_admin_email(), $subject, $body, $headers );
                }
            }
        }
    }

    public function prepare_admin_user_info( $user_id ) {

        if ( UM()->options()->get( $this->slug . '_admin' ) == 1 ) {

            $admin_info = array_map( 'sanitize_text_field', UM()->options()->get( $this->slug . '_admin_info' ));
            $this->content_admin_email = array();

            if ( ! empty( $admin_info )) {

                foreach( $admin_info as $key ) {
                    switch( $key ) {
                        case 'age'             :    $s = sprintf( __( 'Age',                'happy-birthday' ) . ' %d', $this->get_user_age() ); break;
                        case 'birth_date'      :    $s = sprintf( __( 'Birth date',         'happy-birthday' ) . ' %s', um_user( 'birth_date' )); break;
                        case 'user_login'      :    $s = sprintf( __( 'Username',           'happy-birthday' ) . ' %s', um_user( 'user_login' )); break;
                        case 'display_name'    :    $s = sprintf( __( 'Display name',       'happy-birthday' ) . ' %s', um_user( 'display_name' )); break;
                        case 'account_status'  :    $s = sprintf( __( 'Accoun status',      'happy-birthday' ) . ' %s', um_user( 'account_status' )); break;
                        case 'user_registered' :    $s = sprintf( __( 'Registration date',  'happy-birthday' ) . ' %s', substr( um_user( 'user_registered' ), 0, 10 )); break;
                        case '_um_last_login'  :    $s = sprintf( __( 'Last login',         'happy-birthday' ) . ' %s', substr( um_user( '_um_last_login' ), 0, 10 )); break;
                        case 'prio_role'       :    $s = sprintf( __( 'User priority role', 'happy-birthday' ) . ' %s', $this->prio_roles[$user_id] ); break;
                        case 'user_id'         :    $s = sprintf( __( 'User ID',            'happy-birthday' ) . ' %s', $user_id ); break;
                        case 'profile_page'    :    $s = sprintf( __( 'Profile page',       'happy-birthday' ) . ' %s', '<a href="' . um_user_profile_url( $user_id ) . '">' . um_user( 'user_login' ) . '</a>' ); break;
                        case 'send_status'     :    $s = '##send_status##'; break;
                        default                :    $s = '';
                    }
                    $this->content_admin_email[] = $s;
                }
            }
        }
    }

    public function get_user_age() {

        return intval( substr( $this->today, 0, 4 )) - intval( substr( um_user( 'birth_date'), 0, 4 ));
    }

    public function prepare_sms_message( $bool ) {

        if ( $bool ) {

            $this->sms_text_message = sanitize_text_field( UM()->options()->get( $this->slug . '_sms_text' ));

            if ( empty( $this->sms_text_message )) {
                $this->sms_text_message = __( 'Happy Birthday {display_name}! Hope you have a fantastic day! Regards {site_name}', 'happy-birthday' );
            }

            $this->sms_text_message = um_convert_tags( $this->sms_text_message, $this->happy_birthday_args );
        }
    }

    public function prepare_placeholders( $user_id ) {

        $this->mobile_number = um_user( 'mobile_number' );
        $this->display_name  = um_user( 'display_name' );

        $this->happy_birthday_args = array(
                                            'tags'          => array(
                                                                '{today}',
                                                                '{age}',
                                                                '{user_id}',
                                                                '{mobile_number}'
                                                            ),

                                            'tags_replace'  => array(
                                                                substr( $this->today, 0, 10 ),
                                                                $this->get_user_age(),
                                                                $user_id,
                                                                $this->mobile_number,
                                                            ),
                                        );
    }

    public function send_happy_birthday_sms( $bool ) {

        if ( $bool ) {

            if ( um_user( $this->last_greeted ) == 'resend' ) {
                $this->content_admin_email = array_merge( array( __( 'SMS resend by Admin', 'happy-birthday' )), $this->content_admin_email );
            }

            $flash_sms = ( UM()->options()->get( $this->slug . '_flash_sms' ) == 1 ) ? true : null;

            $sms_status = wp_sms_send( $this->mobile_number, $this->sms_text_message, $flash_sms );

            if ( is_wp_error( $sms_status )) {

                $this->sms_failure_counter++;
                $this->sms_send_failure = true;
                $this->sms_send_status = sprintf( __( 'SMS Gateway error: %s', 'happy-birthday' ), $sms_status->get_error_message() );
            }

            if ( empty( $this->sms_send_status )) {

                if ( is_array( $sms_status )) {
                    $sms_status = implode( ', ', $sms_status );
                }

                $this->sms_send_status = sprintf( __( 'SMS Gateway return message: %s', 'happy-birthday' ), $sms_status );
                $this->sms_send_counter++;
                $this->sms_send_failure = false;
            }
        }
    }

    public function send_happy_birthday_email( $bool ) {

        if ( $bool && ! empty( $this->email_speed )) {

            $save_email = um_user( 'user_email' );

            if ( um_user( $this->last_greeted ) == 'resend' ) {
                $this->content_admin_email = array_merge( array( __( 'Email resent by Admin', 'happy-birthday' )), $this->content_admin_email );
            }

            switch( $this->short_status( um_user( $this->last_greeted_status ) )) {
                case 'email failure':   $this->content_admin_email = array_merge( array( '', __( 'Email failure = email resend', 'happy-birthday' )), $this->content_admin_email ); break;
                case 'sms failure':     $this->content_admin_email = array_merge( array( '', __( 'SMS failure = email resend', 'happy-birthday' )), $this->content_admin_email ); break;
                default: break;
            }

            UM()->mail()->send( $save_email, $this->slug, $this->happy_birthday_args );

            if ( $save_email == $this->send_to_email ) {
                $this->email_send_status = sprintf( __( 'Email WP status %s to %s', 'happy-birthday' ), __( 'Sent OK', 'happy-birthday' ), $save_email );
                $this->email_send_counter++;

            } else {
                $this->email_send_status = sprintf( __( 'Email WP status %s to &s', 'happy-birthday' ), __( 'Not sent', 'happy-birthday' ), $save_email );
                $this->email_send_failure = true;
                $this->email_failure_counter++;
            }
        }
    }

    public function update_user_meta_status( $user_id, $type ) {

        $current_time = date_i18n( 'Y/m/d H:i:s', current_time( 'timestamp' ));

        if ( $type['email'] && ! empty( $this->email_speed ) ) {

            if ( ! $this->email_send_failure ) {
                update_user_meta( $user_id, $this->last_greeted, $current_time );
                update_user_meta( $user_id, $this->last_greeted_status,'email ok' );
                update_user_meta( $user_id, $this->last_greeted_error, '' );

            } else {
                update_user_meta( $user_id, $this->last_greeted_status,'email failure ' . $this->email_send_status );
                update_user_meta( $user_id, $this->last_greeted_error, $this->today );
                update_user_meta( $user_id, $this->last_greeted, '' );
                $this->email_send_failure = false;
            }

            $this->get_celebrant_admin_summary( $type );

            $this->email_speed--;
            if ( ! empty( $this->email_speed ) ) {
                $this->wp_mail_sleep();
            }
        }

        if ( $type['sms'] ) {

            if ( ! $this->sms_send_failure ) {
                update_user_meta( $user_id, $this->last_greeted, $current_time );
                update_user_meta( $user_id, $this->last_greeted_status, 'sms ok' );

            } else {
                update_user_meta( $user_id, $this->last_greeted_status, 'sms failure ' . $this->sms_send_status );
                update_user_meta( $user_id, $this->last_greeted_error, $this->today );
                update_user_meta( $user_id, $this->last_greeted, '' );
                $this->sms_send_failure = false;
            }

            $this->get_celebrant_admin_summary( $type );
        }

        UM()->user()->remove_cache( $user_id );
    }

    public function set_email_speed( $celebrants ) {

        $this->email_speed = intval( UM()->options()->get( $this->slug . '_email_speed' ));

        if ( empty( $this->email_speed ) || count( $celebrants ) < $this->email_speed ) {
            $this->email_speed = count( $celebrants );
        }
    }

    public function wp_mail_sleep() {

        if ( UM()->options()->get( $this->slug . '_wp_mail' ) == 1 ) {

            $sleep_seconds = intval( UM()->options()->get( $this->slug . '_email_delay' ));

            if ( empty( $sleep_seconds )) {
                $sleep_seconds = 1;
            }

            sleep( $sleep_seconds );
        }
    }

    public function second_attempt_possible() {

        if ( ! empty( $this->sms_failure_counter ) && ! empty( $this->email_speed )) {

            if ( UM()->options()->get( $this->slug . '_email' ) == 1 ) {
                return true;
            }
        }
        return false;
    }

    public function get_celebrant_admin_summary( $type ) {

        if ( count( $this->content_admin_email ) > 0 ) {

            $status = ( $type['email'] ) ? $this->email_send_status : $this->sms_send_status;

            if ( count( $this->content_admin_email ) > $this->lines_limit ) {

                $this->content_admin_email[] = '';
                $this->content_admin_email[] = '';

                $summary = implode( '<br />', $this->content_admin_email );
                $summary = str_replace( '##send_status##', $status, $summary );

            } else {
                $summary = implode( ', ', $this->content_admin_email );
                $summary = str_replace( '##send_status##', $status, $summary );

                $headers = array(
                    __( 'Age',                'happy-birthday' ),
                    __( 'Birth date',         'happy-birthday' ),
                    __( 'Username',           'happy-birthday' ),
                    __( 'Display name',       'happy-birthday' ),
                    __( 'Accoun status',      'happy-birthday' ),
                    __( 'Registration date',  'happy-birthday' ),
                    __( 'Last login',         'happy-birthday' ),
                    __( 'User priority role', 'happy-birthday' ),
                    __( 'User ID',            'happy-birthday' ),
                    __( 'Profile page',       'happy-birthday' ),
                    __( 'Email WP status',    'happy-birthday' ),
                );

                $summary = str_replace( $headers, '', $summary );
            }

            $this->sent_user_list[] = $summary;
        }
    }

    public function happy_birthday_greetings_impossible( $celebrants ) {

        if ( ! empty( $celebrants ) ) {

            foreach( $celebrants as $user_id => $type ) {
                um_fetch_user( $user_id );

                update_user_meta( $user_id, $this->last_greeted_status,'email failure UM deactivated setting' );
                update_user_meta( $user_id, $this->last_greeted_error, $this->today );
                update_user_meta( $user_id, $this->last_greeted, '' );

                $this->sent_user_list[] = um_user( 'display_name' );

                UM()->user()->remove_cache( $user_id );
            }
        }
    }

    public function send_happy_birthday_greetings( $celebrants ) {

        if ( ! empty( $celebrants ) ) {
            foreach( $celebrants as $user_id => $type ) {
                um_fetch_user( $user_id );

                $this->prepare_placeholders( $user_id );
                $this->prepare_sms_message( $type['sms'] );
                $this->prepare_admin_user_info( $user_id );
                $this->send_happy_birthday_email( $type['email'] );
                $this->send_happy_birthday_sms( $type['sms'] );
                $this->update_user_meta_status( $user_id, $type );
            }
        }
    }

    public function um_cron_task_birthday_greet_notification() {

        if ( UM()->options()->get( $this->slug . '_on' ) == 1 && UM()->options()->get( $this->slug . '_active' ) == 1 ) {

            $happy_birthday_hour = sanitize_text_field( UM()->options()->get( $this->slug . '_hour' ));
            $current_hour = date_i18n( 'H', current_time( 'timestamp' ));

            if ( empty( $happy_birthday_hour ) || $current_hour >= $happy_birthday_hour ) {

                $this->cronjob = true;
                $this->today = date_i18n( 'Y/m/d H:i:s', current_time( 'timestamp' ));

                $celebrants = $this->get_all_celebrants( substr( $this->today, 4, 6 ) );

                if ( ! empty( $celebrants ) ) {

                    $this->set_email_speed( $celebrants );

                    add_filter( 'wp_mail',     array( $this, 'um_happy_birthday_wp_mail' ), 999, 1 );
                    add_action( 'wp_sms_send', array( $this, 'um_happy_birthday_sms_gateway_info' ), 10, 1 );

                    $this->send_happy_birthday_greetings( $celebrants );

                    if ( $this->second_attempt_possible() ) {

                        $this->sent_user_list[] = '';
                        $this->sent_user_list[] = __( 'Second attempt this hour to send remaining Happy Birthday greetings', 'happy-birthday' );
                        $this->sent_user_list[] = '';

                        $celebrants = $this->get_all_celebrants();

                        $this->send_happy_birthday_greetings( $celebrants );

                    } else {

                        $celebrants = $this->get_all_celebrants();
                        if ( ! empty( $celebrants )) {

                            $this->sent_user_list[] = '';
                            $this->sent_user_list[] = __( 'Impossible to send Happy Birthday greetings', 'happy-birthday' );
                            $this->sent_user_list[] = '';

                            $this->happy_birthday_greetings_impossible( $celebrants );
                        }
                    }

                    remove_filter( 'wp_mail',     array( $this, 'um_happy_birthday_wp_mail' ), 999, 1 );
                    remove_action( 'wp_sms_send', array( $this, 'um_happy_birthday_sms_gateway_info' ), 10, 1 );

                    $this->send_happy_birthday_info_admin();
                }
            }
        }
    }

    public function validate_sending_media() {

        if ( UM()->options()->get( $this->slug . '_sms' ) == 1 && defined( 'WP_SMS_DIR' ) ) {

            if ( ! empty( um_user( 'mobile_number' ) )) {
                return 'SMS';

            } else {

                if ( UM()->options()->get( $this->slug . '_email' ) == 1 ) {
                    return 'email';

                } else {
                    return 'none';
                }
            }
        }

        if ( UM()->options()->get( $this->slug . '_email' ) == 1 ) {
            return 'email';
        }

        return 'none';
    }

    public function user_profile_link( $user_id, $celebrant_name ) {

        switch( $this->validate_sending_media()) {

            case 'SMS':     $send = um_user( 'mobile_number' );
                            $title = __( 'Greetings to mobile number %s', 'happy-birthday' );
                            break;
            case 'none':    $title = __( 'SMS not possible and email greetings are disabled: No greetings will be sent', 'happy-birthday' );
                            $send = '';
                            break;
            case 'email':   $send = um_user( 'user_email' );
                            $title = __( 'Greetings to email address %s', 'happy-birthday' );
                            break;
            default:        break;
        }

        $title = sprintf( $title, $send );

        $greeted = sprintf( '<a href="%s" target="Happy_Birthday" title="%s">%s</a>, %d', um_user_profile_url( $user_id ), $title, um_user( $celebrant_name ), $this->get_user_age());

        if ( ! $this->cronjob ) {
            UM()->user()->remove_cache( $user_id );
        }

        return $greeted;
    }

    public function short_status( $status ) {

        $words = explode( ' ', $status );
        if ( count( $words ) > 2 ) {
            $status = $words[0] . ' ' . $words[1];
        }

        return $status;
    }

    public function celebration_user_status_list( $celebrants, $delta ) {

        if ( UM()->options()->get( $this->slug . '_celebrant_list' ) == 1 ) {

            $celebrant_name = sanitize_text_field( UM()->options()->get( $this->slug . '_celebrant_name' ));
            if ( empty( $celebrant_name )) {
                $celebrant_name = 'user_login';
            }

            if ( $delta <= 0 ) {

                $greeted = '<table>';
                $celebrants = array_keys( $celebrants );

                foreach( $celebrants as $user_id ) {
                    um_fetch_user( $user_id );

                    $td   = '<td style="padding-bottom:0px;padding-top:0px;">';
                    $td2  = '<td colspan="2" style="padding-bottom:0px;padding-top:0px;">';
                    $span = '<span style="padding-bottom:0px;padding-top:0px;" title="%s">%s</span>';

                    $greeted .= '<tr>';

                    if ( um_user( $this->last_greeted ) == 'resend' ) {
                        $greeted .= $td2;
                        $greeted .= __( 'Resend by Admin', 'happy-birthday' );
                        $greeted .= '</td>';

                    } else {

                        if ( substr( um_user( $this->last_greeted ), 0, 10 ) == substr( $this->today, 0, 10 ) ) {
                            $greeted .= $td;
                            $greeted .= sprintf( $span, __( 'Time when birthday greeting was sent', 'happy-birthday' ), substr( um_user( $this->last_greeted ), 10 ));
                            $greeted .= '</td>';

                            $greeted .= $td;
                            $greeted .= sprintf( $span, um_user( $this->last_greeted_status ), $this->short_status( um_user( $this->last_greeted_status ) ) );
                            $greeted .= '</td>';

                        } else {

                            if ( substr( um_user( $this->last_greeted_error ), 0, 10 ) == substr( $this->today, 0, 10 ) ) {

                                $greeted .= $td;
                                $greeted .= sprintf( $span, __( 'Time for birthday greeting attempt', 'happy-birthday' ), substr( um_user( $this->last_greeted_error ), 10 ));
                                $greeted .= '</td>';

                                $status = $this->short_status( um_user( $this->last_greeted_status ));
                                $greeted .= $td;
                                $greeted .= sprintf( $span, str_replace( $status, '', um_user( $this->last_greeted_status )), $status );
                                $greeted .= '</td>';

                            } else {

                                $media = $this->validate_sending_media();
                                if ( $media != 'none' ) {

                                    if ( strpos( um_user( $this->last_greeted_status ), 'failure' )) {

                                        $status = $this->short_status( um_user( $this->last_greeted_status ));
                                        $status = str_replace( $status, '', um_user( $this->last_greeted_status ));

                                        $greeted .= $td2;
                                        $greeted .= sprintf( $span, $status, sprintf( __( '%s greetings failed', 'happy-birthday' ), $media ));
                                        $greeted .= '</td>';

                                    } else {
                                        $greeted .=$td2;
                                        $greeted .= sprintf( __( 'Pending %s greetings', 'happy-birthday' ), $media );
                                        $greeted .= '</td>';
                                    }

                                } else {
                                    $greeted .= $td2;
                                    $greeted .= __( 'Greetings not possible', 'happy-birthday' );
                                    $greeted .= '</td>';
                                }
                            }
                        }
                    }

                    $greeted .= $td;
                    $greeted .= $this->user_profile_link( $user_id, $celebrant_name );
                    $greeted .= '</td>';

                    $greeted .= '</tr>';
                }

                $greeted .= '</table>';

                $this->description[] = $greeted;

            } else {

                $greeted = array();
                $greeted[] = '';

                foreach( $celebrants as $user_id => $type ) {
                    um_fetch_user( $user_id );
                    $greeted[] = $this->user_profile_link( $user_id, $celebrant_name );
                }

                $greeted[] = '';
                $this->description[] = implode( '<br />', $greeted );
            }

        } else {

            $this->description[] = '<br />';
        }
    }

    public function current_status_celebrants( $delta ) {

        $this->today = date_i18n( 'Y/m/d l', current_time( 'timestamp' ) + ( $delta * DAY_IN_SECONDS ) );
        $celebrants = $this->get_all_celebrants();
        $url = $happy_birthday_form = sanitize_text_field( UM()->options()->get( $this->slug . '_um_form_url' ));

        $link = '';
        if ( ! empty( $url )) {
            $link = sprintf( '<a href="%s?delta=%s" title="%s" target="Happy_Birthday">%s</a>', $url, $delta, __( 'Members Directory page', 'happy-birthday' ), __( 'Show', 'happy-birthday' ));
        }

        switch( count( $celebrants ) ) {
            case 0:     $hdr = sprintf( __( 'No celebrants %s',    'happy-birthday' ), $this->today ); break;
            case 1:     $hdr = sprintf( __( 'One celebrant %s %s', 'happy-birthday' ), $this->today, $link ); break;
            default:    $hdr = sprintf( __( '%d celebrants %s %s', 'happy-birthday' ), count( $celebrants ), $this->today, $link );
        }

        if ( $delta == 0 ) {

            $hdr = str_replace( substr( $this->today, 0, 10 ), __( 'today', 'happy-birthday' ), $hdr );
            $this->celebrants_today = str_replace( $link, '', $hdr );
            $hdr = '<span style="color: green;">' . $hdr . '</span>';
        }

        $this->description[] = $hdr;

        $this->celebration_user_status_list( $celebrants, $delta );
    }

    public function prepare_status_listing() {

        $this->cron_job_settings();

        $default = 'reject';
        if ( UM()->options()->get( $this->slug . '_without_consent' ) == 1 ) {
            $default = 'accept';
        }

        $this->plugin_status[] = '<hr>' . sprintf( __( 'Status: %d Users accepts and %d Users rejects birthday greetings. Default %s: %d', 'happy-birthday' ),
                                                        $this->transient_manager_counters( 'birthday_greetings_yes' ),
                                                        $this->transient_manager_counters( 'birthday_greetings_no' ),
                                                        $default,
                                                        $this->transient_manager_counters( 'birthday_greetings_default' ));

        $this->plugin_status[] = '<hr>' . __( 'With current plugin settings of User Roles and Account statuses:', 'happy-birthday' );

        $this->description = array();

        $i = -1;
        while( $i <= 6 ) {
            $this->current_status_celebrants( $i );
            $i++;
        }

        $desc = implode( '<br />', $this->plugin_status );
        $desc .= '<br />';
        $desc .= implode( '', $this->description );

        $desc = wp_kses( $desc, $this->html_allowed );

        return $desc;
    }

    public function um_admin_settings_email_section_happy_birthday( $settings ) {

        if ( isset( $_POST['um-settings-action'] ) &&  $_POST['um-settings-action'] == 'save' ) {

            delete_transient( 'birthday_greetings_yes' );
            delete_transient( 'birthday_greetings_no' );
            delete_transient( 'birthday_greetings_default' );
        }

        $um_directory_forms = get_posts( array( 'numberposts' => -1,
                                                'post_type'   => 'um_directory',
                                                'post_status' => 'publish'
                                            )
                                        );

        $members_directories = array();
        foreach( $um_directory_forms as $um_directory_form ) {
            $members_directories[$um_directory_form->ID] = $um_directory_form->post_title;
        }

        $prefix = '&nbsp; * &nbsp;';
        $wp_cron_job = sprintf( '<a href="https://developer.wordpress.org/plugins/cron/" title="%s" target="_blank">WP Cronjob</a>', __( 'What is WP-Cron?', 'happy-birthday' ));

        $description = array();
        $description[] = __( 'Select the hour during the day when the Happy Birthday plugin first will try to send greetings to the User.', 'happy-birthday' );
        $description[] = __( 'New sending attempt each hour if plugin or email/WP-SMS been inactive.', 'happy-birthday' );
        $description[] = __( 'New sending attempt also during next hour if additional Account Status or Roles are selected', 'happy-birthday' );
        $description[] = __( 'A "Resend" must be applied from the WP All Users page and UM Action dropdown at least 2 hours before midnight.', 'happy-birthday' );
 
        $settings['extensions']['sections']['happy-birthday'] = array(
                                                                        'title'	      => __( 'Happy Birthday', 'happy-birthday' ),
                                                                        'description' => $this->get_possible_plugin_update( 'um-happy-birthday' ),
                                                                    );

        $section_fields = array();

        $section_fields[] = array(
                    'id'              => $this->slug . '_header',
                    'type'            => 'header',
                    'label'           => __( 'WP Cronjob', 'happy-birthday' ),
                );

        $section_fields[] = array(
                    'id'              => $this->slug . '_active',
                    'type'            => 'checkbox',
                    'label'           => $prefix . sprintf( __( 'Activate the Happy Birthday %s', 'happy-birthday' ), $wp_cron_job  ),
                    'checkbox_label'  => __( "Click to activate the Plugin's WP Cronjob", 'happy-birthday' ),
                    'description'     => $this->prepare_status_listing(),
                );

        $section_fields[] = array(
                    'id'              => $this->slug . '_hour',
                    'type'            => 'select',
                    'size'            => 'short',
                    'options'         => array(
                                                '00' => '00 - 01',
                                                '01' => '01 - 02',
                                                '02' => '02 - 03',
                                                '03' => '03 - 04',
                                                '04' => '04 - 05',
                                                '05' => '05 - 06',
                                                '06' => '06 - 07',
                                                '07' => '07 - 08',
                                                '08' => '08 - 09',
                                                '09' => '09 - 10',
                                                '10' => '10 - 11',
                                                '11' => '11 - 12',
                                                '12' => '12 - 13',
                                                '13' => '13 - 14',
                                                '14' => '14 - 15',
                                                '15' => '15 - 16',
                                                '16' => '16 - 17',
                                                '17' => '17 - 18',
                                                '18' => '18 - 19',
                                                '19' => '19 - 20',
                                                '20' => '20 - 21',
                                                '21' => '21 - 22',
                                                '22' => '22 - 23',
                                                '23' => '23 - 24',
                                            ),
                    'label'           => $prefix . __( 'Send Happy Birthday greetings during this hour or later', 'happy-birthday' ),
                    'description'     => implode( '<br />', $description ),
                );

        $section_fields[] = array(
                    'id'              => $this->slug . '_header',
                    'type'            => 'header',
                    'label'           => __( 'User selections', 'happy-birthday' ),
                );

        $section_fields[] = array(
                    'id'              => $this->slug . '_account_status',
                    'type'            => 'select',
                    'multi'           => true,
                    'size'            => 'short',
                    'options'         => $this->account_status,
                    'label'           => $prefix . __( 'User Account statuses to include', 'happy-birthday' ),
                    'description'     => __( 'Select the Account statuses to receive the Happy Birthday greeting', 'happy-birthday' ),
                );

        $section_fields[] = array(
                    'id'              => $this->slug . '_user_roles',
                    'type'            => 'select',
                    'multi'           => true,
                    'size'            => 'short',
                    'options'         => UM()->roles()->get_roles(),
                    'label'           => $prefix . __( 'Priority User Roles to include', 'happy-birthday' ),
                    'description'     => __( 'Select the Priority User Roles to receive the Happy Birthday greeting', 'happy-birthday' ),
                );

        $section_fields[] = array(
                    'id'              => $this->slug . '_without_consent',
                    'type'            => 'checkbox',
                    'label'           => $prefix . __( 'Current old Users default consent is "No"', 'happy-birthday' ),
                    'checkbox_label'  => __( 'Select to include current old users without having selected "Yes" in Account page as accepting birthday greetings.', 'happy-birthday' ),
                    'description'     => __( 'These old Users are displayed as "Default" and accept or reject in the Status count.', 'happy-birthday' ),
                );

        $section_fields[] = array(
                    'id'              => $this->slug . '_header',
                    'type'            => 'header',
                    'label'           => __( 'Backend Celebrant lists', 'happy-birthday' ),
                );

        $section_fields[] = array(
                    'id'              => $this->slug . '_celebrant_list',
                    'type'            => 'checkbox',
                    'label'           => $prefix . __( 'Select to show the User Celebrant list', 'happy-birthday' ),
                    'checkbox_label'  => __( 'Click to get a list of each Celebrant User name for the Celebrant listing at this page and UM Dashboard modal if activated.', 'happy-birthday' ),
                );

        $section_fields[] = array(
                    'id'              => $this->slug . '_celebrant_name',
                    'type'            => 'select',
                    'label'           => $prefix . __( 'Select Name in the Celebrant list', 'happy-birthday' ),
                    'options'         => array(   'user_login'   => __( 'User Login', 'happy-birthday' ),
                                                  'display_name' => __( 'Display Name', 'happy-birthday' ),
                                            ),
                    'default'         => 'display_name',
                    'description'     => __( 'Select the User name for the Celebrant listing at this page and UM Dashboard modal if activated.', 'happy-birthday' ),
                    'conditional'     => array( $this->slug . '_celebrant_list', '=', 1 ),
                );

        $section_fields[] = array(
                    'id'              => $this->slug . '_modal_list',
                    'type'            => 'checkbox',
                    'label'           => $prefix . __( 'Activate the UM Dashboard modal', 'happy-birthday' ),
                    'checkbox_label'  => __( 'Click to activate the UM Dashboard modal for Happy Birthday.', 'happy-birthday' ),
                );

        $section_fields[] = array(
                    'id'              => $this->slug . '_header',
                    'type'            => 'header',
                    'label'           => __( 'Email greetings', 'happy-birthday' ),
                );

        $section_fields[] = array(
                    'id'              => $this->slug . '_email',
                    'type'            => 'checkbox',
                    'label'           => $prefix . __( 'Activate sending emails', 'happy-birthday' ),
                    'checkbox_label'  => __( 'Click to enable the WP Cronjob sending Happy Birthday emails.', 'happy-birthday' ),
                );

        $section_fields[] = array(
                    'id'              => $this->slug . '_email_speed',
                    'type'            => 'select',
                    'size'            => 'short',
                    'label'           => $prefix . __( 'Select email sending speed', 'happy-birthday' ),
                    'options'         => array(
                                                 1   => '1',
                                                 5   => '5',
                                                10   => '10',
                                                15   => '15',
                                                20   => '20',
                                                25   => '25',
                                                50   => '50',
                                                75   => '75',
                                                100  => '100',
                                                125  => '125',
                                                150  => '150',
                                                175  => '175',
                                                200  => '200',
                                                225  => '225',
                                                250  => '250',
                                            ),
                    'description'     => __( 'Select which speed to send the greetings emails in number of emails sent per hour.', 'happy-birthday' ),
                    'conditional'     => array( $this->slug . '_email', '=', 1 ),
                );

        $section_fields[] = array(
                    'id'              => $this->slug . '_wp_mail',
                    'type'            => 'checkbox',
                    'label'           => $prefix . __( 'WP Mail or SMTP', 'happy-birthday' ),
                    'checkbox_label'  => __( 'Click if you are using WP Mail and not a SMTP transport service for your emails.', 'happy-birthday' ),
                    'conditional'     => array( $this->slug . '_email', '=', 1 ),
                );

        $section_fields[] = array(
                    'id'              => $this->slug . '_email_delay',
                    'type'            => 'select',
                    'size'            => 'short',
                    'label'           => $prefix . __( 'Select delay in seconds for WP Mail', 'happy-birthday' ),
                    'options'         => array(
                                                 1   => '1',
                                                 5   => '5',
                                                10   => '10',
                                                15   => '15',
                                                20   => '20',
                                                25   => '25',
                                                30   => '30',
                                                60   => '60',
                                                90   => '90',
                                                120  => '120',
                                            ),
                    'description'     => __( 'Select the delay in seconds between each greetings email being sent via WP Mail.', 'happy-birthday' ),
                    'conditional'     => array( $this->slug . '_wp_mail', '=', 1 ),
                );

        if ( defined( 'WP_SMS_DIR' )) {

            $section_fields[] = array(
                        'id'             => $this->slug . '_header',
                        'type'           => 'header',
                        'label'          => __( 'WP SMS text greetings - Optional', 'happy-birthday' ),
                    );

            $section_fields[] = array(
                        'id'             => $this->slug . '_sms',
                        'type'           => 'checkbox',
                        'label'          => $prefix . __( 'Activate sending WP SMS', 'happy-birthday' ),
                        'checkbox_label' => __( 'Click to enable the WP Cronjob sending Happy Birthday mobile SMS text greeting instead of an email if User registered with Mobile number', 'happy-birthday' ),
                    );

            $section_fields[] = array(
                        'id'             => $this->slug . '_flash_sms',
                        'type'           => 'checkbox',
                        'label'          => $prefix . __( 'Activate sending flash WP SMS', 'happy-birthday' ),
                        'checkbox_label' => __( 'Click to enable the WP Cronjob sending Happy Birthday flash SMS text greeting', 'happy-birthday' ),
                        'conditional'    => array( $this->slug . '_sms', '=', 1 ),
                    );

            $section_fields[] = array(
                        'id'             => $this->slug . '_sms_text',
                        'type'           => 'textarea',
                        'label'          => $prefix . __( 'WP SMS text greeting', 'happy-birthday' ),
                        'description'    => __( 'Enter your Happy Birthday SMS text greeting and you can use UM email placeholders and {today}, {age}, {user_id}, {mobile_number}', 'happy-birthday' ),
                    );
        }

        $section_fields[] = array(
                        'id'             => $this->slug . '_header',
                        'type'           => 'header',
                        'label'          => __( 'Daily Admin summary email', 'happy-birthday' ),
                    );

        $section_fields[] = array(
                        'id'             => $this->slug . '_admin',
                        'type'           => 'checkbox',
                        'label'          => $prefix . __( 'Activate Admin info email', 'happy-birthday' ),
                        'checkbox_label' => __( 'Click to enable the Admin info email sent after each batch of greetings emails.', 'happy-birthday' ),
                    );

        $section_fields[] = array(
                        'id'             => $this->slug . '_admin_info',
                        'type'           => 'select',
                        'multi'          => true,
                        'label'          => $prefix . __( 'Select info in Admin email', 'happy-birthday' ),
                        'options'        => array(
                                                    'age'             =>    __( 'Celebrant age',        'happy-birthday' ),
                                                    'birth_date'      =>    __( 'Birth date',           'happy-birthday' ),
                                                    'user_login'      =>    __( 'Username',             'happy-birthday' ),
                                                    'display_name'    =>    __( 'Display name',         'happy-birthday' ),
                                                    'account_status'  =>    __( 'Accoun status',        'happy-birthday' ),
                                                    'user_registered' =>    __( 'Registration date',    'happy-birthday' ),
                                                    '_um_last_login'  =>    __( 'Last login date',      'happy-birthday' ),
                                                    'prio_role'       =>    __( 'User priority role',   'happy-birthday' ),
                                                    'user_id'         =>    __( 'User ID',              'happy-birthday' ),
                                                    'profile_page'    =>    __( 'Profile page link',    'happy-birthday' ),
                                                    'send_status'     =>    __( 'Sent greeting status', 'happy-birthday' )
                                                ),
                        'description'    => __( 'Select the information fields about the celebrant to include in the Admin info email.', 'happy-birthday' ),
                        'conditional'    => array( $this->slug . '_admin', '=', 1 ),
                    );

        $section_fields[] = array(
                        'id'             => $this->slug . '_header',
                        'type'           => 'header',
                        'label'          => __( 'Members Directory for display of Celebrants', 'happy-birthday' ),
                    );

        $section_fields[] = array(
                        'id'             => $this->slug . '_um_form',
                        'type'           => 'select',
                        'size'           => 'medium',
                        'label'          => $prefix . __( 'Select Members Directory form', 'happy-birthday' ),
                        'options'        => $members_directories,
                        'description'    => __( 'Select the Members Directory form for display of celebrants.', 'happy-birthday' ),
                    );

        $section_fields[] = array(
                        'id'             => $this->slug . '_um_form_url',
                        'type'           => 'text',
                        'size'           => 'medium',
                        'label'          => $prefix . __( 'URL to Members Directory page', 'happy-birthday' ),
                        'description'    => __( 'Enter the URL to the Members Directory page for display of celebrants.', 'happy-birthday' ),
                    );

        $section_fields[] = array(
                        'id'             => $this->slug . '_header',
                        'type'           => 'header',
                        'label'          => __( 'User Accoun page setting', 'happy-birthday' ),
                    );

        $section_fields[] = array(
                        'id'             => $this->slug . '_account',
                        'type'           => 'checkbox',
                        'label'          => $prefix . __( 'Allow users to enable/disable greetings', 'happy-birthday' ),
                        'checkbox_label' => __( 'Click to allow Users to enable/disable greetings at their Account page.', 'happy-birthday' ),
                    );

        $section_fields[] = array(
                        'id'             => $this->slug . '_header',
                        'type'           => 'header',
                        'label'          => __( 'Birthday Cake with Candles', 'happy-birthday' ),
                    );

        $section_fields[] = array(
                        'id'             => $this->slug . '_cake_candles',
                        'type'           => 'checkbox',
                        'label'          => $prefix . __( 'Cake with Candles', 'happy-birthday' ),
                        'checkbox_label' => __( 'Click to enable a Birthday Cake with Candles at the Profile page after name for todays celebrants.', 'happy-birthday' ),
                    );

        $section_fields[] = array(
                        'id'             => $this->slug . '_cake_color',
                        'type'           => 'text',
                        'size'           => 'small',
                        'label'          => $prefix . __( 'Cake with Candles color', 'happy-birthday' ),
                        'description'    => __( 'Enter the color for the Cake with Candles either by the color name or HEX code.', 'happy-birthday' ) . '<br />' .
                                            sprintf( __( 'Default color is "%s".', 'happy-birthday' ), $this->cake_color ) .
                                            ' <a href="https://www.w3schools.com/colors/colors_groups.asp" target="_blank">W3Schools HTML Color Groups</a>',
                        'conditional'    => array( $this->slug . '_cake_candles', '=', 1 ),
                    );

        $section_fields[] = array(
                        'id'             => $this->slug . '_cake_size',
                        'type'           => 'text',
                        'size'           => 'small',
                        'label'          => $prefix . __( 'Cake with Candles size', 'happy-birthday' ),
                        'description'    => sprintf( __( 'Enter the size value in pixels for the Cake with Candles, default value is %s.', 'happy-birthday' ), $this->px ),
                        'conditional'    => array( $this->slug . '_cake_candles', '=', 1 ),
                    );

        $settings['extensions']['sections']['happy-birthday']['fields'] = $section_fields;

        return $settings;
    }

    public function um_account_tab_privacy_fields_happy_birthday( $args, $shortcode_args ) {

        global $current_user;

        $this->get_all_selected_user_roles();

        if ( ! empty( $this->all_selected_user_roles )) {

            $prio_role = UM()->roles()->get_priority_user_role( $current_user->ID );
            if ( in_array( $prio_role, $this->all_selected_user_roles )) {

                $args .= ',' . $this->slug . '_privacy';
            }
        }

        return $args;
    }

    public function um_predefined_fields_hook_happy_birthday( $predefined_fields ) {

        if ( UM()->options()->get( $this->slug . '_account' ) == 1 ) {

            $default = 'no';
            if ( UM()->options()->get( $this->slug . '_without_consent' ) == 1 ) {
                $default = 'yes';
            }

            $predefined_fields[$this->slug . '_privacy'] = array(
                                                    'title'         => __( 'Receive birthday greetings?', 'happy-birthday' ),
                                                    'metakey'       => $this->slug . '_privacy',
                                                    'type'          => 'radio',
                                                    'label'         => __( 'Do you want to receive birthday greetings?', 'happy-birthday' ),
                                                    'help'          => __( 'Enable/Disable birthday greetings via email or SMS text message inclusive cake with candles at the Profile page', 'happy-birthday' ),
                                                    'required'      => 0,
                                                    'public'        => 1,
                                                    'editable'      => true,
                                                    'default'       => $default,
                                                    'options'       => $this->privacy_options,
                                                    'account_only'  => true,
                                                );
        }

        return $predefined_fields;
    }

    public function um_registration_set_happy_birthday_account_consent( $user_id, $args, $form_data ) {

        if ( isset( $form_data['mode'] ) && $form_data['mode'] == 'register' ) {

            if ( isset( $args[$this->greetings_consent] )) {

                update_user_meta( $user_id, $this->slug . '_privacy', array( 'yes' ) );
                update_user_meta( $user_id, $this->greetings_consent, date_i18n( 'Y/m/d', current_time( 'timestamp' )) );
                $this->transient_manager_counters( 'birthday_greetings_yes', 1 );

            } else {

                update_user_meta( $user_id, $this->slug . '_privacy', array( 'no' ) );
                $this->transient_manager_counters( 'birthday_greetings_no', 1 );
            }
        }
    }

    public function um_account_pre_update_profile_happy_birthday_account_consent( $changes, $user_id ) {

        if ( isset( $changes[$this->slug . '_privacy'] ) ) {

            if ( is_array( $changes[$this->slug . '_privacy'] ) && in_array( $changes[$this->slug . '_privacy'][0], array( 'no', 'yes' ))) {

                $current_consent = um_user( $this->slug . '_privacy' );

                if ( empty( $current_consent )) {
                    $this->transient_manager_counters( 'birthday_greetings_default', -1 );

                } else {

                    if ( is_array( $current_consent ) && in_array( $current_consent[0], array( 'no', 'yes' ))) {
                        $this->transient_manager_counters( 'birthday_greetings_' . $current_consent[0], -1 );
                    }
                }

                $this->transient_manager_counters( 'birthday_greetings_' . $changes[$this->slug . '_privacy'][0], 1 );
            }
        }
    }

    public function um_email_notifications( $notifications ) {

        $url = get_site_url() . '/wp-admin/admin.php?page=um_options&tab=extensions&section=happy-birthday';
        $url = sprintf( ' <a href="%s">%s</a>', $url, __( 'Plugin settings', 'happy-birthday' ));

        $notifications[$this->slug] = array(
                                            'key'            => $this->slug,
                                            'title'          => __( 'Happy Birthday', 'happy-birthday' ),
                                            'subject'        => __( 'Happy Birthday from {site_name}', 'happy-birthday' ),
                                            'body'           => 'Hi {first_name},<br /><br />We wish you a happy birthday today!<br /><br />The {site_name} Team',
                                            'description'    => __( 'Whether to send the user an email when someone is today\'s birthday.','happy-birthday' ) . $url,
                                            'recipient'   	 => 'user',
                                        );

        if ( UM()->options()->get( $this->slug . '_on' ) === '' ) {

            $email_on = empty( $notifications[$this->slug]['default_active'] ) ? 0 : 1;
			UM()->options()->update( $this->slug . '_on', $email_on );
        }

        if ( UM()->options()->get( $this->slug . '_sub' ) === '' ) {

			UM()->options()->update( $this->slug . '_sub', $notifications[$this->slug]['subject'] );
        }

        return $notifications;
    }

    public function copy_email_notifications_happy_birthday() {

        $located = UM()->mail()->locate_template( $this->slug );

        if ( ! is_file( $located ) || filesize( $located ) == 0 ) {
            $located = wp_normalize_path( STYLESHEETPATH . '/ultimate-member/email/' . $this->slug . '.php' );
        }

        clearstatcache();
        if ( ! file_exists( $located ) || filesize( $located ) == 0 ) {

            wp_mkdir_p( dirname( $located ) );

            $email_source = file_get_contents( Happy_Birthday_Path . $this->slug . '.php' );
            file_put_contents( $located, $email_source );

            if ( ! file_exists( $located ) ) {
                file_put_contents( um_path . 'templates/email/' . $this->slug . '.php', $email_source );
            }
        }
    }

    public function get_possible_plugin_update( $plugin ) {

        $transient = get_transient( $plugin );
        if ( is_array( $transient ) && isset( $transient['status'] )) {
            $update = $transient['status'];
        }

        $plugin_data = get_plugin_data( __FILE__ );

        if ( empty( $transient ) || $this->new_version_test_required( $transient, $plugin_data )) {

            if ( extension_loaded( 'curl' )) {

                $github_user = 'MissVeronica';
                $url = "https://api.github.com/repos/{$github_user}/{$plugin}/contents/README.md";

                $curl = curl_init();
                curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
                curl_setopt( $curl, CURLOPT_BINARYTRANSFER, 1 );
                curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, 1 );
                curl_setopt( $curl, CURLOPT_URL, $url );
                curl_setopt( $curl, CURLOPT_USERAGENT, $github_user );

                $content = json_decode( curl_exec( $curl ), true );
                $error = curl_error( $curl );
                curl_close( $curl );

                if ( ! $error ) {

                    switch( $this->validate_new_plugin_version( $plugin_data, $content ) ) {

                        case 0:     $update = __( 'Plugin version update verification failed', 'happy-birthday' );
                                    break;
                        case 1:     $update = '<a href="' . $plugin_data['UpdateURI'] . '" target="_blank">';
                                    $update = sprintf( __( 'Update to %s plugin version %s%s is available for download.', 'happy-birthday' ), $update, esc_attr( $this->new_plugin_version ), '</a>' );
                                    break;
                        case 2:     $update = sprintf( __( 'Plugin is updated to latest version %s.', 'happy-birthday' ), esc_attr( $plugin_data['Version'] ));
                                    break;
                        default:    break;
                    }

                    $this->today = date_i18n( 'Y/m/d H:i:s', current_time( 'timestamp' ));
                    $update .= '<br />' . sprintf( __( 'Github plugin version status is checked each 24 hours last at %s.', 'happy-birthday' ), esc_attr( $this->today ));

                    set_transient( $plugin, array( 'status' => $update, 'last_version' => $plugin_data['Version'] ), 24 * HOUR_IN_SECONDS );

                } else {
                    $update = sprintf( __( 'GitHub remote connection cURL error: %s', 'happy-birthday' ), $error );
                }

            } else {
                $update = __( 'cURL extension not loaded by PHP', 'happy-birthday' );
            }
        }

        return wp_kses( $update, $this->html_allowed );
    }

    public function new_version_test_required( $transient, $plugin_data ) {

        $bool = false;
        if ( isset( $transient['last_version'] ) && $plugin_data['Version'] != $transient['last_version'] ) {
            $bool = true;
        }

        return $bool;
    }

    public function validate_new_plugin_version( $plugin_data, $content ) {

        $validation = 0;

        if ( is_array( $content ) && isset( $content['content'] )) {

            $readme  = base64_decode( $content['content'] );
            $version = strrpos( $readme, 'Version' );

            if ( $version !== false ) {
                $version = substr( $readme, $version, 16 );
                $version = explode( ' ', $version );

                if ( isset( $plugin_data['Version'] ) && ! empty( $plugin_data['Version'] )) {
                    if ( isset( $version[1] ) && ! empty( $version[1] )) {

                        if ( sanitize_text_field( $plugin_data['Version'] ) != sanitize_text_field( $version[1] )) {

                            if ( isset( $plugin_data['UpdateURI'] ) && ! empty( $plugin_data['UpdateURI'] )) {
                                $this->new_plugin_version = $version[1];
                                $validation = 1;
                            }

                        } else {
                            $validation = 2;
                        }
                    }
                }
            }
        }

        return $validation;
    }

    public function um_happy_birthday_directories( $query_args, $directory_data ) {

        global $current_user;

        $happy_birthday_form = sanitize_text_field( UM()->options()->get( $this->slug . '_um_form' ));

        if ( isset( $directory_data['form_id'] ) && $directory_data['form_id'] == $happy_birthday_form ) {

            $account_status = $this->get_account_status();

            if ( ! empty( $account_status )) {

                $delta = get_transient( 'Happy_Birthday_directory_user_id_' . $current_user->ID );
                $this->today = date_i18n( 'Y/m/d', current_time( 'timestamp' ) + intval( $delta ) * DAY_IN_SECONDS );

                $query_args['meta_query'] = $this->get_happy_birthday_meta_query( $account_status );
            }
        }

        return $query_args;
    }

    public function um_pre_args_setup_happy_birthday( $args ) {

        global $current_user;

        $happy_birthday_form = sanitize_text_field( UM()->options()->get( $this->slug . '_um_form' ));

        if ( isset( $args['form_id'] ) && $args['form_id'] == $happy_birthday_form ) {

            $delta = 0;
            if ( isset( $_GET['delta'] ) && is_numeric( $_GET['delta'] )) {

                $delta = intval( $_GET['delta'] );
            }

            if ( isset( $_GET['date'] )) {
                $date  = sanitize_text_field( $_GET['date'] );

                $today = new DateTime( date_i18n( 'Y/m/d', current_time( 'timestamp' )) );
                $diff  = $today->diff( new DateTime( $date ) );
                $delta = -intval( $diff->d );
            }

            if ( absint( $delta ) > 7 ) {
                $delta = 0;
            } 

            set_transient( 'Happy_Birthday_directory_user_id_' . $current_user->ID, $delta, DAY_IN_SECONDS );
        }

        return $args;
    }

    public function transient_manager_counters( $transient, $delta = false ) {

        $value = get_transient( $transient );

        if ( $value === false ) {

            $args = array(  'fields'     => array( 'ID' ),
                            'meta_query' => array( 'relation' => 'AND' )
                        );

            $account_status = $this->get_account_status();

            $args['meta_query'][] = array(
                                        'key'     => 'account_status',
                                        'value'   => $account_status,
                                        'compare' => 'IN',
                                    );

            switch( $transient ) {

                case 'birthday_greetings_no':       $args['meta_query'][] = array(
                                                                                    'key'     => $this->slug . '_privacy',
                                                                                    'value'   => '"no"',
                                                                                    'compare' => 'LIKE'
                                                                                );
                                                    break;

                case 'birthday_greetings_yes':      $args['meta_query'][] = array(
                                                                                    'key'     => $this->slug . '_privacy',
                                                                                    'value'   => '"yes"',
                                                                                    'compare' => 'LIKE'
                                                                                );
                                                    break;

                case 'birthday_greetings_default':  $args['meta_query'][] = array(  
                                                                                    'key'     => $this->slug . '_privacy',
                                                                                    'compare' => 'NOT EXISTS'
                                                                                );
                                                    break;

                default:    return;
                            break;
            }

            $users = get_users( $args );
            $count = count( $users );

            set_transient( $transient, $count, $this->transient_life );

            return $count;
        }

        if ( $delta !== false ) {

            $value = $value + $delta;
            set_transient( $transient, $value );
        }

        return $value;
    }

    public function custom_predefined_fields_happy_birthday( $predefined_fields ) {

        $predefined_fields[$this->last_greeted] = array(
                                                'title'    => __( 'Happy Birthday last greeted', 'happy-birthday' ),
                                                'metakey'  => $this->last_greeted,
                                                'type'     => 'text',
                                                'label'    => __( 'Happy Birthday last greeted', 'happy-birthday' ),
                                                'required' => 0,
                                                'public'   => 1,
                                                'editable' => false,
                                            );

        $predefined_fields[$this->last_greeted_status] = array(
                                                'title'    => __( 'Happy Birthday last greeted status', 'happy-birthday' ),
                                                'metakey'  => $this->last_greeted_status,
                                                'type'     => 'text',
                                                'label'    => __( 'Happy Birthday last greeted status', 'happy-birthday' ),
                                                'required' => 0,
                                                'public'   => 1,
                                                'editable' => false,
                                            );

        $predefined_fields[$this->last_greeted_error] = array(
                                                'title'    => __( 'Happy Birthday last greeted error', 'happy-birthday' ),
                                                'metakey'  => $this->last_greeted_error,
                                                'type'     => 'text',
                                                'label'    => __( 'Happy Birthday last greeted error', 'happy-birthday' ),
                                                'required' => 0,
                                                'public'   => 1,
                                                'editable' => false,
                                            );

        $predefined_fields[$this->greetings_consent] = array(
                                                'title'    => __( 'Happy Birthday greetings consent', 'happy-birthday' ),
                                                'metakey'  => $this->greetings_consent,
                                                'type'     => 'checkbox',
                                                'label'    => __( 'Happy Birthday greetings consent', 'happy-birthday' ),
                                                'required' => 0,
                                                'public'   => 1,
                                                'editable' => false,
                                                'options'  => array( 'yes' => __( 'Accept Birthday greetings', 'happy-birthday' )),
                                            );

        return $predefined_fields;
    }


}

new UM_Happy_Birthday();

