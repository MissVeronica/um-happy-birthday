<?php

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

class UM_Happy_Birthday {

    public $wp_cron_event           = 'um_cron_birthday_greet_notification';
    public $slug                    = 'um_greet_todays_birthdays';
    public $transient_prefix        = 'birthday_greetings_directory_user_id_';
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
    public $icon_options            = array();

    public $sms_info                = '';
    public $sms_text_message        = '';
    public $display_name            = '';
    public $mobile_number           = '';
    public $today                   = '';
    public $email_send_status       = '';
    public $sms_send_status         = '';
    public $celebrants_today        = '';
    public $celebrants_summary      = '';    

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
        add_filter( 'um_admin_bulk_user_actions_hook',               array( $this, 'um_admin_bulk_user_actions_resend_happy_birthday' ), 10, 1 );
        add_action( 'um_admin_custom_hook_happy_birthday_greetings', array( $this, 'um_admin_custom_hook_happy_birthday_greetings_resend' ), 10, 1 );

        add_filter( 'um_account_tab_privacy_fields',                 array( $this, 'um_account_tab_privacy_fields_happy_birthday' ), 10, 2 );
        add_action( 'um_prepare_user_query_args',                    array( $this, 'um_happy_birthday_directories' ), 10, 2 );
        add_filter( 'um_pre_args_setup',                             array( $this, 'um_pre_args_setup_happy_birthday' ), 10, 1 );

        add_action( 'plugins_loaded',                                array( $this, 'um_happy_birthday_plugin_loaded' ), 0 );
        add_action( 'um_registration_set_extra_data',                array( $this, 'um_registration_set_happy_birthday_account_consent' ), 10, 3 );
        add_action( 'um_account_pre_update_profile',                 array( $this, 'um_account_pre_update_profile_happy_birthday_account_consent' ), 10, 2 );

        add_action( 'um_after_profile_name_inline',                  array( $this, 'um_after_profile_name_show_celebration_icon' ), 9, 1 );


        if ( UM()->options()->get( $this->slug . '_modal_list' ) == 1 ) {
		    add_action( 'load-toplevel_page_ultimatemember',         array( $this, 'load_metabox_happy_birthday' ) );
        }

        if ( UM()->options()->get( $this->slug . '_on' ) == 1 && UM()->options()->get( $this->slug . '_active' ) == 1 ) {

            add_action( $this->wp_cron_event,                        array( $this, 'um_cron_task_birthday_greet_notification' ));

            if ( ! wp_next_scheduled ( $this->wp_cron_event ) ) {
                wp_schedule_event( time(), 'hourly', $this->wp_cron_event );
            }
        }
    }

    public function load_metabox_happy_birthday() {

        $this->celebrants_summary = $this->prepare_status_listing( true );

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

    public function um_after_profile_name_show_celebration_icon( $args ) {

        if ( UM()->options()->get( $this->slug . '_cake_candles' ) == 1 ) {

            if ( strpos( um_user( 'birth_date' ), date_i18n( '/m/d', current_time( 'timestamp' )) ) !== false ) {

                if ( $this->get_user_account_consent_setting() !== false ) {

                    $this->show_current_happy_birthday_icon();
                }
            }
        }
    }

    public function show_current_happy_birthday_icon() {

        $title = __( 'Happy Birthday today', 'happy-birthday' );

        $color = trim( UM()->options()->get( $this->slug . '_cake_color' ));
        if ( ! empty( $color )) {
            $this->cake_color = sanitize_text_field( $color );
        }

        $px = trim( UM()->options()->get( $this->slug . '_cake_size' ));
        if ( ! empty( $px )) {
            $this->px = absint( str_replace( 'px', '', strtolower( sanitize_text_field( $px ))));
        }

        $class_icon = 'fas fa-cake-candles';
        $icon = UM()->options()->get( $this->slug . '_celebration_icon' );

        if ( ! empty( $icon ) && isset( $this->icon_options[$icon] )) {
            $class_icon = $icon;
        }
?>
        <span class="um-field-label-icon" 
              style="font-size: <?php echo esc_attr( $this->px ); ?>px; color: <?php echo esc_attr( $color ); ?>;"
              title="<?php echo esc_attr( $title ); ?>">
            <i class="<?php echo esc_attr( $class_icon ); ?>"></i>
        </span>
<?php
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
                    $settings = sprintf( ' <a href="%s">%s</a>', esc_url( $url_plugin ), __( 'settings', 'happy-birthday' ));
                }

                $this->plugin_status[] = __( 'Plugin is active', 'happy-birthday' ) . $settings;
                $this->plugin_status[] = sprintf( '<a href="%s">%s</a> %s ', esc_url( $url_email ), __( 'Happy Birthday', 'happy-birthday' ), __( 'email template is active', 'happy-birthday' ));

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

            $this->plugin_status[] = sprintf( '<a href="%s">%s</a> %s ', esc_url( $url_email ), __( 'Happy Birthday', 'happy-birthday' ), __( 'email template is not active and greetings are disabled', 'happy-birthday' ));
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

                    $url = sanitize_text_field( UM()->options()->get( $this->slug . '_um_form_url' ));

                    $link = substr( $this->today, 0, 10 );
                    if ( ! empty( $url )) {
                        $link = sprintf( '<a href="%s?date=%s" target="Happy_Birthday">%s</a>', esc_url( $url ), substr( $this->today, 0, 10 ), substr( $this->today, 0, 10 ));
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
                        case 'age'             :    $s = sprintf( __( 'Age',                'happy-birthday' ) . ' %d', $this->get_user_age() );      break;
                        case 'birth_date'      :    $s = sprintf( __( 'Birth date',         'happy-birthday' ) . ' %s', um_user( 'birth_date' ));     break;
                        case 'user_login'      :    $s = sprintf( __( 'Username',           'happy-birthday' ) . ' %s', um_user( 'user_login' ));     break;
                        case 'display_name'    :    $s = sprintf( __( 'Display name',       'happy-birthday' ) . ' %s', um_user( 'display_name' ));   break;
                        case 'account_status'  :    $s = sprintf( __( 'Accoun status',      'happy-birthday' ) . ' %s', um_user( 'account_status' )); break;
                        case 'user_registered' :    $s = sprintf( __( 'Registration date',  'happy-birthday' ) . ' %s', substr( um_user( 'user_registered' ), 0, 10 )); break;
                        case '_um_last_login'  :    $s = sprintf( __( 'Last login',         'happy-birthday' ) . ' %s', substr( um_user( '_um_last_login' ), 0, 10 ));  break;
                        case 'prio_role'       :    $s = sprintf( __( 'User priority role', 'happy-birthday' ) . ' %s', $this->prio_roles[$user_id] ); break;
                        case 'user_id'         :    $s = sprintf( __( 'User ID',            'happy-birthday' ) . ' %s', $user_id ); break;
                        case 'profile_page'    :    $s = sprintf( __( 'Profile page',       'happy-birthday' ) . ' %s', '<a href="' . esc_url( um_user_profile_url( $user_id )) . '">' . um_user( 'user_login' ) . '</a>' ); break;
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
                case 'sms failure':     $this->content_admin_email = array_merge( array( '', __( 'SMS failure = email resend',   'happy-birthday' )), $this->content_admin_email ); break;
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

        if ( wp_doing_cron() && UM()->options()->get( $this->slug . '_on' ) == 1 && UM()->options()->get( $this->slug . '_active' ) == 1 ) {

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

        $greeted = sprintf( '<a href="%s" target="Happy_Birthday" title="%s">%s</a>, %d', esc_url( um_user_profile_url( $user_id )), $title, um_user( $celebrant_name ), $this->get_user_age());

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

        $url = sanitize_text_field( UM()->options()->get( $this->slug . '_um_form_url' ));

        $link = '';
        if ( ! empty( $url )) {
            $link = sprintf( '<a href="%s?delta=%s" title="%s" target="Happy_Birthday">%s</a>', esc_url( $url ), $delta, __( 'Members Directory page', 'happy-birthday' ), __( 'Show', 'happy-birthday' ));
        }

        switch( count( $celebrants ) ) {
            case 0:     $hdr = sprintf( __( 'No celebrants %s',    'happy-birthday' ), $this->today ); break;
            case 1:     $hdr = sprintf( __( 'One celebrant %s %s', 'happy-birthday' ), $this->today, $link ); break;
            default:    $hdr = sprintf( __( '%d celebrants %s %s', 'happy-birthday' ), count( $celebrants ), $this->today, $link );
        }

        if ( $delta == 0 ) {

            $hdr = str_replace( substr( $this->today, 0, 10 ), __( 'today', 'happy-birthday' ), $hdr );
            $this->celebrants_today = str_replace( $link, '', $hdr );
            $hdr = '<span style="color: green; font-weight: bold;">' . $hdr . '</span>';
        }

        $this->description[] = $hdr;

        $this->celebration_user_status_list( $celebrants, $delta );
    }

    public function prepare_status_listing( $modal = false ) {

        $this->cron_job_settings();

        $default = 'reject';
        if ( UM()->options()->get( $this->slug . '_without_consent' ) == 1 ) {
            $default = 'accept';
        }

        $this->plugin_status[] = '<hr>' . sprintf( __( 'Status: %d Users accepts and %d Users rejects birthday greetings. Default %s: %d', 'happy-birthday' ),
                                            UM()->classes['um_happy_birthday_transients']->transient_manager_counters( 'yes' ),
                                            UM()->classes['um_happy_birthday_transients']->transient_manager_counters( 'no' ),
                                            $default,
                                            UM()->classes['um_happy_birthday_transients']->transient_manager_counters( 'default' ));

        if ( $modal ) {

            $url = strtolower( $this->get_plugin_settings_url());
            $this->plugin_status[] = '<hr>' . sprintf( __( 'With current %s of User Roles and Account statuses:', 'happy-birthday' ), $url );

        } else {
            $this->plugin_status[] = '<hr>' . __( 'With current plugin settings of User Roles and Account statuses:', 'happy-birthday' );
        }

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

    public function um_registration_set_happy_birthday_account_consent( $user_id, $args, $form_data ) {

        if ( isset( $form_data['mode'] ) && $form_data['mode'] == 'register' ) {

            if ( isset( $args[$this->greetings_consent] )) {

                update_user_meta( $user_id, $this->slug . '_privacy', array( 'yes' ) );
                update_user_meta( $user_id, $this->greetings_consent, date_i18n( 'Y/m/d', current_time( 'timestamp' )) );
                UM()->classes['um_happy_birthday_transients']->transient_manager_counters( 'yes', 1 );

            } else {

                update_user_meta( $user_id, $this->slug . '_privacy', array( 'no' ) );
                UM()->classes['um_happy_birthday_transients']->transient_manager_counters( 'no', 1 );
            }
        }
    }

    public function um_account_pre_update_profile_happy_birthday_account_consent( $changes, $user_id ) {

        if ( isset( $changes[$this->slug . '_privacy'] ) ) {

            if ( is_array( $changes[$this->slug . '_privacy'] ) && in_array( $changes[$this->slug . '_privacy'][0], array( 'no', 'yes' ))) {

                $current_consent = um_user( $this->slug . '_privacy' );

                if ( empty( $current_consent )) {
                    UM()->classes['um_happy_birthday_transients']->transient_manager_counters( 'default', -1 );

                } else {

                    if ( is_array( $current_consent ) && in_array( $current_consent[0], array( 'no', 'yes' ))) {
                        UM()->classes['um_happy_birthday_transients']->transient_manager_counters( $current_consent[0], -1 );
                    }
                }

                UM()->classes['um_happy_birthday_transients']->transient_manager_counters( $changes[$this->slug . '_privacy'][0], 1 );
            }
        }
    }

    public function get_plugin_settings_url() {

        $url = get_site_url() . '/wp-admin/admin.php?page=um_options&tab=extensions&section=happy-birthday';
        $url = sprintf( ' <a href="%s">%s</a>', esc_url( $url ), __( 'Plugin settings', 'happy-birthday' ));

        return $url;
    }

    public function um_email_notifications( $notifications ) {

        $url = $this->get_plugin_settings_url();

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

    public function um_happy_birthday_directories( $query_args, $directory_data ) {

        global $current_user;

        $happy_birthday_form = sanitize_text_field( UM()->options()->get( $this->slug . '_um_form' ));

        if ( isset( $directory_data['form_id'] ) && $directory_data['form_id'] == $happy_birthday_form ) {

            $account_status = $this->get_account_status();

            if ( ! empty( $account_status )) {

                $delta = get_transient( $this->transient_prefix . $current_user->ID );
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

            set_transient( $this->transient_prefix . $current_user->ID, $delta, DAY_IN_SECONDS );
        }

        return $args;
    }

}
