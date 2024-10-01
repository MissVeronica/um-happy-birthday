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
    public $account_status_user     = array();

    public $sms_info                = '';
    public $sms_text_message        = '';
    public $display_name            = '';
    public $mobile_number           = '';
    public $today                   = '';
    public $email_send_status       = '';
    public $sms_send_status         = '';
    public $celebrants_today        = '';
    public $celebrants_summary      = '';

    public $account_status   = array(
                                    'approved'                    => 'Approved',
                                    'awaiting_email_confirmation' => 'Email Confirmation',
                                    'awaiting_admin_review'       => 'Admin Review',
                                    'inactive'                    => 'Inactive',
                                    'rejected'                    => 'Rejected',
                                );

    function __construct() {

        add_filter( 'um_email_notifications',                        array( $this, 'um_email_notifications_happy_birthday' ), 98, 1 );
        //add_filter( 'um_admin_views_users',                          array( $this, 'um_admin_views_users_happy_birthday' ), 10, 1 );  // UM2.8.7

        add_action(	'um_extend_admin_menu',                          array( $this, 'um_extend_admin_menu_happy_birthday' ), 10 );
        add_action( 'pre_get_users',                                 array( $this, 'pre_get_users_sort_columns_custom' ));

        add_filter( 'pre_user_query',                                array( $this, 'filter_users_happy_birthday' ), 99 );
        add_filter( 'manage_users_sortable_columns',                 array( $this, 'register_sortable_columns_custom' ), 10, 1 );

        add_filter( 'manage_users_columns',                          array( $this, 'manage_users_columns_happy_birthday' ) );
        add_filter( 'manage_users_custom_column',                    array( $this, 'manage_users_custom_column_happy_birthday' ), 10, 3 );

        add_filter( 'bulk_actions-users',                            array( $this, 'um_admin_bulk_user_actions_resend_happy_birthday' ), 10, 1 );  // UM2.8.7 function changes
        add_action( 'handle_bulk_actions-users',                     array( $this, 'um_admin_custom_hook_happy_birthday_greetings_resend' ), 10, 3 ); // UM2.8.7

        add_filter( 'um_prepare_user_query_args',                    array( $this, 'um_happy_birthday_directories' ), 10, 2 );
        add_filter( 'um_pre_args_setup',                             array( $this, 'um_pre_args_setup_happy_birthday' ), 10, 1 );

        add_action( 'um_registration_set_extra_data',                array( $this, 'um_registration_set_happy_birthday_account_consent' ), 10, 3 );
        add_action( 'um_account_pre_update_profile',                 array( $this, 'um_account_pre_update_profile_happy_birthday_account_consent' ), 10, 2 );

        add_action( 'um_admin_do_action__happy_birthday_cron',       array( $this, 'happy_birthday_reset_cron_job' ) );
        add_filter( 'um_adm_action_custom_update_notice',            array( $this, 'happy_birthday_admin_notice' ), 10, 2 );



        if ( UM()->options()->get( $this->slug . '_modal_list' ) == 1 ) {
		    add_action( 'load-toplevel_page_ultimatemember',         array( $this, 'load_metabox_happy_birthday' ) );
        }

        if ( UM()->options()->get( $this->slug . '_on' ) == 1 && UM()->options()->get( $this->slug . '_active' ) == 1 ) {

            add_action( $this->wp_cron_event, array( $this, 'um_cron_task_birthday_greet_notification' ));
            $this->schedule_greetings_cron_job();
        }

        $this->today = date_i18n( 'Y/m/d H:i:s', current_time( 'timestamp' ));
    }

    public function happy_birthday_deactivation() {

        wp_clear_scheduled_hook( $this->wp_cron_event );
    }

    public function schedule_greetings_cron_job() {

        if ( ! wp_next_scheduled ( $this->wp_cron_event ) ) {

            $current_minute = intval( date_i18n( 'i', current_time( 'timestamp' )));
            wp_schedule_event( time() + ( 61-$current_minute )*60 - 30, 'hourly', $this->wp_cron_event );
        }
    }

    public function restart_greetings_cron_job() {

        $status = wp_clear_scheduled_hook( $this->wp_cron_event );
        $this->schedule_greetings_cron_job();

        return $status;
    }

    public function happy_birthday_reset_cron_job() {

        $status = $this->restart_greetings_cron_job();

        $url = add_query_arg(
                                array(
                                    'page'     => 'ultimatemember',
                                    'update'   => 'happy_birthday_reset',
                                    'result'   =>  $status,
                                    '_wpnonce' =>  wp_create_nonce( 'happy_birthday_reset' ),
                                ),
                                admin_url( 'admin.php' )
                            );

        wp_safe_redirect( $url );
        exit;
    }

    public function happy_birthday_admin_notice( $message, $update ) {

        if ( $update == 'happy_birthday_reset' && isset( $_REQUEST['result'] )) {

            $cron_job = wp_next_scheduled( $this->wp_cron_event );

            if ( $cron_job > 0 ) {

                $message[0]['content'] = sprintf( esc_html__( 'Happy Birthday restarted %s WP Cronjob to be scheduled next time at %s', 'happy-birthday' ),
                                                               sanitize_text_field( $_REQUEST['result'] ), esc_attr( $this->get_local_time( $cron_job ) ));

            } else {

                $message[0]['content'] = esc_html__( 'Restart of Happy Birthday WP Cronjob failed', 'happy-birthday' );
            }
        }

        if ( $update == 'happy_birthday_greetings' && isset( $_REQUEST['result'] ) ) {

            switch( sanitize_text_field( $_REQUEST['result'] ) ) {

                case 'A':   $result = esc_html__( 'Invalid', 'happy-birthday' );
                            break;

                case 'B':   $result = esc_html__( 'Too late for resending any more Birthday greetings today.', 'happy-birthday' );
                            break;

                case 'C':   $result = esc_html__( 'No users selected for resending Birthday greetings.', 'happy-birthday' );
                            break;

                default:    $result = sprintf( esc_html__( '%d users selected for resending Birthday greetings. Users with pending greetings are not included in this Users list.', 'happy-birthday' ), intval( $_REQUEST['result'] ) );
                            break;
            }

            $message[0]['content'] = esc_html( $result );
        }

        return $message;
    }

    public function get_local_time( $cron_job ) {

        $utc_timestamp_converted = date( 'Y-m-d H:i:s', $cron_job );
        $local_timestamp = get_date_from_gmt( $utc_timestamp_converted, 'H:i:s' );

        return $local_timestamp;
    }

    public function load_metabox_happy_birthday() {

        $this->celebrants_summary = $this->prepare_status_listing( true );

        add_meta_box(   'um-metaboxes-sidebox-happy-birthday',
                        sprintf( esc_html__( 'Happy Birthday - %s', 'happy-birthday' ), $this->celebrants_today ),
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

    public function cron_job_settings() {

        $this->plugin_status = array();

        $url_email  = get_site_url() . '/wp-admin/admin.php?page=um_options&tab=email&email=um_greet_todays_birthdays';

        if ( UM()->options()->get( $this->slug . '_on' ) == 1 ) {

            if ( UM()->options()->get( $this->slug . '_active' ) == 1 ) {

                $plugin_data = get_plugin_data( Plugin_File_HB );

                $this->plugin_status[] = sprintf( esc_html__( 'Plugin version %s is active', 'happy-birthday' ), $plugin_data['Version'] );
                $this->plugin_status[] = sprintf( '<a href="%s">%s</a> %s ', esc_url( $url_email ), esc_html__( 'Happy Birthday', 'happy-birthday' ), esc_html__( 'email template is active', 'happy-birthday' ));

                $this->wp_cron_job_status();

                if ( UM()->options()->get( $this->slug . '_email' ) == 1 ) {

                    if ( defined( 'WP_SMS_DIR' ) && UM()->options()->get( $this->slug . '_sms' ) == 1 ) {
                        $this->plugin_status[] = esc_html__( 'Plugin will only send Happy Birthday email greetings to users with mobile numbers if the SMS text sending fails', 'happy-birthday' );

                    } else {
                        $this->plugin_status[] = sprintf( esc_html__( 'Plugin will send max %d Happy Birthday email greetings per hour', 'happy-birthday' ),
                        intval( UM()->options()->get( $this->slug . '_email_speed' )));
                    }

                } else {

                    $this->plugin_status[] = esc_html__( 'Plugin will NOT send any Happy Birthday email greetings', 'happy-birthday' );
                }

                if ( UM()->options()->get( $this->slug . '_flash_sms' ) == 1 ) {
                    $wp_sms = '<a href="https://wordpress.org/plugins/wp-twilio-core/" title="Twilio WP plugin page" target="_blank">WP flash SMS</a>';

                } else {
                    $wp_sms = '<a href="https://wordpress.org/plugins/wp-twilio-core/" title="Twilio WP plugin page" target="_blank">WP SMS</a>';
                }

                if ( defined( 'WP_SMS_DIR' ) ) {

                    if ( UM()->options()->get( $this->slug . '_sms' ) == 1 ) {
                            $this->plugin_status[] = sprintf( esc_html__( 'Plugin will try to send Happy Birthday "%s" text greetings', 'happy-birthday' ), $wp_sms );

                    } else {
                        $this->plugin_status[] = sprintf( esc_html__( 'Plugin will NOT try to send any Happy Birthday "%s" text greetings', 'happy-birthday' ), $wp_sms );
                    }

                } else {

                    if ( file_exists( WP_CONTENT_DIR . '/plugins/wp-sms' )) {
                        $this->plugin_status[] = sprintf( esc_html__( 'The "%s" plugin is deactivated', 'happy-birthday' ), $wp_sms );

                    } else {
                        $this->plugin_status[] = sprintf( esc_html__( 'The "%s" plugin is not installed', 'happy-birthday' ), $wp_sms );
                    }
                }

            } else {
                $this->plugin_status[] = esc_html__( 'Plugin is paused and not sending any Happy Birthday greetings', 'happy-birthday' );
            }

        } else {

            $this->plugin_status[] = sprintf( '<a href="%s">%s</a> %s ', esc_url( $url_email ), esc_html__( 'Happy Birthday', 'happy-birthday' ), esc_html__( 'email template is not active and greetings are disabled', 'happy-birthday' ));
        }
    }

    public function wp_cron_job_status() {

        $cron_job = wp_next_scheduled( $this->wp_cron_event );

        if ( ! empty( $cron_job )) {

            $minutes = intval(( $cron_job - time() ) / 60 );

            if ( $minutes > 0 ) {
                $this->plugin_status[] = sprintf( esc_html__( 'The Plugin WP Cronjob will execute next in about %d minutes at %s', 'happy-birthday' ), $minutes, esc_attr( $this->get_local_time( $cron_job ) ) );

            } else {
                $seconds = intval( $cron_job - time() );
                if ( $seconds > 0 ) {
                    $this->plugin_status[] = sprintf( esc_html__( 'The Plugin WP Cronjob will execute next in about %d seconds', 'happy-birthday' ), $seconds );

                } else {
                    $seconds = absint( $seconds );
                    if ( $seconds < 3600 ) {
                        $this->plugin_status[] = sprintf( esc_html__( 'The Plugin WP Cronjob has been waiting %d minutes in the WP job queue', 'happy-birthday' ), intval( $seconds/60 ));

                    } else {

                        $this->restart_greetings_cron_job();
                        $this->plugin_status[] = sprintf( esc_html__( 'Restarted the Plugin WP Cronjob after waiting in the WP Cronjob queue for %d minutes', 'happy-birthday' ), intval( $seconds/60 ));
                    }
                }
            }

        } else {
            $this->plugin_status[] = esc_html__( 'No active Plugin WP Cronjob for Happy Birthday messages', 'happy-birthday' );
        }
    }

    public function um_happy_birthday_wp_mail( $args ) {

        $this->send_to_email = $args['to'];

        return  $args;
    }

    public function um_happy_birthday_sms_gateway_info( $message_info ) {

        $this->sms_send_status = sprintf( esc_html__( 'SMS Gateway status: %s', 'happy-birthday' ), $message_info );
        $this->sms_send_counter++;
        $this->sms_send_failure = false;
    }

    public function um_admin_bulk_user_actions_resend_happy_birthday( $actions ) {

        if ( isset( $_REQUEST['happy_birthday'] ) && sanitize_key( $_REQUEST['happy_birthday'] ) === 'happy_birthday_greetings' ) {

            $rolename = UM()->roles()->get_priority_user_role( get_current_user_id() );
            $role     = get_role( $rolename );

            if ( null === $role ) {
                return $actions;
            }

            if ( ! current_user_can( 'edit_users' ) && ! $role->has_cap( 'edit_users' ) ) {
                return $actions;
            }

            $sub_actions = array();

            if ( UM()->options()->get( $this->slug . '_email' ) == 1 ) {
                $sub_actions['happy_birthday_greetings'] = esc_html__( 'Resend Happy Birthday greetings', 'happy-birthday' );
            }

            if ( defined( 'WP_SMS_DIR' ) && UM()->options()->get( $this->slug . '_sms' ) == 1 ) {
                $sub_actions['happy_birthday_greetings'] = esc_html__( 'Resend Happy Birthday greetings', 'happy-birthday' );
            }

            $actions[ esc_html__( 'UM Happy Birthday', 'happy-birthday' ) ] = $sub_actions;
        }

        return $actions;
    }

    public function um_extend_admin_menu_happy_birthday() {

        $url = esc_url( admin_url( 'users.php' ) . '?happy_birthday=happy_birthday_greetings' );

        add_submenu_page( 'ultimatemember', esc_html__( 'Happy Birthday', 'happy-birthday' ),
                                            esc_html__( 'Happy Birthday', 'happy-birthday' ), // . sprintf( ' (%d)', intval( $count )),
                                                               'manage_options', $url , '' );
    }

    public function um_admin_views_users_happy_birthday( $views ) {

        if ( isset( $_REQUEST['happy_birthday'] ) && sanitize_key( $_REQUEST['happy_birthday'] ) === 'happy_birthday_greetings' ) {

            $current = 'class="current"';
            $views['all'] = str_replace( 'class="current"', '', $views['all'] );

        } else {
            $current = '';
        }
        $celebrant_count = 0;
        $views['happy_birthday'] = '<a ' . $current . 'href="' . esc_url( admin_url( 'users.php' ) . '?happy-birthday=happy_birthday_greetings' ) . '">' .
                                        esc_html__( 'Happy Birthday', 'happy-birthday' ) . ' <span class="count">(' . $celebrant_count . ')</span></a>';

        return $views;
    }

    public function manage_users_columns_happy_birthday( $columns ) {

        if ( isset( $_REQUEST['happy_birthday'] ) && sanitize_key( $_REQUEST['happy_birthday'] ) === 'happy_birthday_greetings' ) {

            $columns['happy_birthday'] = esc_html__( 'Celebrants today', 'happy_birthday' );
        }

        return $columns;
    }

    public function register_sortable_columns_custom( $columns ) {

        $columns['happy_birthday'] = 'happy_birthday';

        return $columns;
    }

    public function pre_get_users_sort_columns_custom( $query ) {

        if ( $query->get( 'orderby' ) == 'happy_birthday' ) {

            $query->set( 'orderby',  'meta_value' );
            $query->set( 'meta_key', 'birth_date' );
        }
    }

    public function manage_users_custom_column_happy_birthday( $value, $column_name, $user_id ) {

        if ( $column_name == 'happy_birthday' ) {

            um_fetch_user( $user_id );
            $value = um_user( 'birth_date' );

            um_reset_user();
        }

        return $value;
    }

    public function filter_users_happy_birthday( $filter_query ) {

        global $wpdb;
        global $pagenow;

        if ( is_admin() && $pagenow == 'users.php' && ! empty( $_REQUEST['happy_birthday'] ) ) {

            if ( sanitize_key( $_REQUEST['happy_birthday'] ) === 'happy_birthday_greetings' ) {

                $today = date_i18n( 'Y/m/d', current_time( 'timestamp' ));

                $filter_query->query_where = str_replace( 'WHERE 1=1', "WHERE 1=1 AND {$wpdb->users}.ID IN (
                                                                        SELECT {$wpdb->usermeta}.user_id FROM $wpdb->usermeta
                                                                        WHERE {$wpdb->usermeta}.meta_key = 'um_birthday_greeted_last'
                                                                        AND {$wpdb->usermeta}.meta_value LIKE '%%{$today}%')",
                                                            $filter_query->query_where );
            }
        }

        return $filter_query;
    }

    public function um_admin_custom_hook_happy_birthday_greetings_resend( $redirect, $doaction, $user_ids ) {

        $result = 'A';

        if ( $doaction == 'happy_birthday_greetings' ) {
            if ( is_array( $user_ids ) && ! empty( $user_ids )) {                

                $cron = new DateTime( date_i18n( 'Y-m-d H:i:s', wp_next_scheduled( $this->wp_cron_event ) + 1200 ), new DateTimeZone( 'UTC' ));
                $cron->setTimezone( new DateTimeZone( wp_timezone_string() ));

                $today = date_i18n( 'Y/m/d', current_time( 'timestamp' ));

                if ( $cron->format( 'Y/m/d' ) == $today ) {

                    $count = 0;
                    foreach( $user_ids as $user_id ) {

                        um_fetch_user( $user_id );

                        if ( substr( um_user( 'birth_date' ), 4 ) == substr( $today, 4 ) ) {

                            update_user_meta( $user_id, $this->last_greeted, 'resend' );
                            $count++;
                        }

                        UM()->user()->remove_cache( $user_id );
                    }

                    $result = $count;

                } else {
                    $result = 'B';
                }

            } else {
                $result = 'C';
            }
        }

        $url = add_query_arg(
                                array(
                                        'update'         => 'happy_birthday_greetings',
                                        'happy_birthday' => 'happy_birthday_greetings',
                                        'result'         =>  $result,
                                        '_wpnonce'       =>  wp_create_nonce( 'happy_birthday_greetings' ),
                                ),
                                admin_url( 'users.php' )
                            );

        wp_safe_redirect( $url );
        exit;
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
                            }
                        }
                    }

                } else {

                    unset( $celebrants[$user_id] );
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

                    $prio_role = UM()->roles()->get_priority_user_role( $user_id );
                    if ( in_array( $prio_role, $this->all_selected_user_roles )) {

                        $user_selection[$user_id] = $type;
                        $this->prio_roles[$user_id] = UM()->roles()->get_role_name( $prio_role );
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
                    $status[] = sprintf( esc_html__( 'Emails OK: %d',      'happy-birthday' ), intval( $this->email_send_counter ));
                    $status[] = sprintf( esc_html__( 'Email failures: %d', 'happy-birthday' ), intval( $this->email_failure_counter ));
                }

                if ( UM()->options()->get( $this->slug . '_sms' ) == 1 && defined( 'WP_SMS_DIR' ) ) {
                    $status[] = sprintf( esc_html__( 'SMS text OK: %d',       'happy-birthday' ), intval( $this->sms_send_counter ));
                    $status[] = sprintf( esc_html__( 'SMS text failures: %d', 'happy-birthday' ), intval( $this->sms_failure_counter ));
                }

                if ( ! empty( $status )) {

                    $status = '<br />' . implode( '<br />', $status ) . '<br />';

                    $url = sanitize_text_field( UM()->options()->get( $this->slug . '_um_form_url' ));

                    $link = substr( $this->today, 0, 10 );
                    if ( ! empty( $url )) {
                        $link = sprintf( '<a href="%s?date=%s" target="Happy_Birthday">%s</a>', esc_url( $url ), substr( $this->today, 0, 10 ), substr( $this->today, 0, 10 ));
                    }

                    $subject = wp_kses( sprintf( esc_html__( 'Birthday greetings today %s', 'happy-birthday' ), substr( $this->today, 0, 10 ) ), UM()->get_allowed_html( 'templates' ) );

                    $body  = sprintf( esc_html__( 'Birthday greetings today %s', 'happy-birthday' ), $link ) . '<br />';
                    $body .= sprintf( esc_html__( 'Number of greetings sent in this batch: %s', 'happy-birthday' ), $status ) . '<br />';
                    $body .= implode( '<br />', $this->sent_user_list );
                    $body  = str_replace( array( '<br /><br /><br /><br />', '<br /><br /><br />' ), '<br /><br />', $body );

                    $body = $this->custom_wp_kses( $body );

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
                        case 'age'             :    $s = sprintf( esc_html__( 'Age',                'happy-birthday' ) . ' %d', $this->get_user_age() );      break;
                        case 'birth_date'      :    $s = sprintf( esc_html__( 'Birth date',         'happy-birthday' ) . ' %s', um_user( 'birth_date' ));     break;
                        case 'user_login'      :    $s = sprintf( esc_html__( 'Username',           'happy-birthday' ) . ' %s', um_user( 'user_login' ));     break;
                        case 'display_name'    :    $s = sprintf( esc_html__( 'Display name',       'happy-birthday' ) . ' %s', um_user( 'display_name' ));   break;
                        case 'account_status'  :    $s = sprintf( esc_html__( 'Accoun status',      'happy-birthday' ) . ' %s', um_user( 'account_status' )); break;
                        case 'user_registered' :    $s = sprintf( esc_html__( 'Registration date',  'happy-birthday' ) . ' %s', substr( um_user( 'user_registered' ), 0, 10 )); break;
                        case '_um_last_login'  :    $s = sprintf( esc_html__( 'Last login',         'happy-birthday' ) . ' %s', substr( um_user( '_um_last_login' ), 0, 10 ));  break;
                        case 'prio_role'       :    $s = sprintf( esc_html__( 'User priority role', 'happy-birthday' ) . ' %s', $this->prio_roles[$user_id] ); break;
                        case 'user_id'         :    $s = sprintf( esc_html__( 'User ID',            'happy-birthday' ) . ' %s', $user_id ); break;
                        case 'profile_page'    :    $s = sprintf( esc_html__( 'Profile page',       'happy-birthday' ) . ' %s', '<a href="' . esc_url( um_user_profile_url( $user_id )) . '">' . um_user( 'user_login' ) . '</a>' ); break;
                        case 'send_status'     :    $s = '##send_status##'; break;
                        default                :    $s = '';
                    }
                    $this->content_admin_email[] = $s;
                }
            }
        }
    }

    public function get_user_age() {

        return intval( substr( $this->today, 0, 4 )) - intval( substr( um_user( 'birth_date' ), 0, 4 ));
    }

    public function prepare_sms_message( $bool ) {

        if ( $bool ) {

            $this->sms_text_message = sanitize_text_field( UM()->options()->get( $this->slug . '_sms_text' ));

            if ( empty( $this->sms_text_message )) {
                $this->sms_text_message = esc_html__( 'Happy Birthday {display_name}! Hope you have a fantastic day! Regards {site_name}', 'happy-birthday' );
            }

            $this->sms_text_message = um_convert_tags( $this->sms_text_message, $this->happy_birthday_args );
        }
    }

    public function age_with_ordinal( $age ) {

        $ends = array( 'th','st','nd','rd','th','th','th','th','th','th' );

        if ((( $age % 100) >= 11 ) && (( $age%100) <= 13 )) {
            return $age . 'th';

        } else {
            return $age . $ends[$age % 10];
        }
    }

    public function prepare_placeholders( $user_id ) {

        $this->mobile_number = um_user( 'mobile_number' );
        $this->display_name  = um_user( 'display_name' );

        $gender = um_user( 'gender' );

        switch( strtolower( $gender )) {
            case 'male':    $his_her = esc_html__( 'his', 'happy-birthday' );     $he_she = esc_html__( 'he', 'happy-birthday' ); break;
            case 'female':  $his_her = esc_html__( 'her', 'happy-birthday' );     $he_she = esc_html__( 'she', 'happy-birthday' ); break;
            default:        $his_her = esc_html__( 'his/her', 'happy-birthday' ); $he_she = esc_html__( 'he/she', 'happy-birthday' ); break;
        }

        $his_her = apply_filters( 'happy_birthday_his_her', $his_her, $gender, $user_id );
        $he_she  = apply_filters( 'happy_birthday_he_she',  $he_she,  $gender, $user_id );

        $this->happy_birthday_args = array(
                                            'tags'          => array(
                                                                '{today}',
                                                                '{age}',
                                                                '{age_ordinal}',
                                                                '{user_id}',
                                                                '{mobile_number}',
                                                                '{his_her}',
                                                                '{he_she}',
                                                            ),

                                            'tags_replace'  => array(
                                                                substr( $this->today, 0, 10 ),
                                                                $this->get_user_age(),
                                                                $this->age_with_ordinal( $this->get_user_age() ),
                                                                $user_id,
                                                                $this->mobile_number,
                                                                $his_her,
                                                                $he_she,
                                                            ),
                                        );
    }

    public function send_happy_birthday_sms( $bool ) {

        if ( $bool ) {

            if ( um_user( $this->last_greeted ) == 'resend' ) {
                $this->content_admin_email = array_merge( array( esc_html__( 'SMS resend by Admin', 'happy-birthday' )), $this->content_admin_email );
            }

            $flash_sms = ( UM()->options()->get( $this->slug . '_flash_sms' ) == 1 ) ? true : null;

            $sms_status = wp_sms_send( $this->mobile_number, $this->sms_text_message, $flash_sms );

            if ( is_wp_error( $sms_status )) {

                $this->sms_failure_counter++;
                $this->sms_send_failure = true;
                $this->sms_send_status = sprintf( esc_html__( 'SMS Gateway error: %s', 'happy-birthday' ), $sms_status->get_error_message() );
            }

            if ( empty( $this->sms_send_status )) {

                if ( is_array( $sms_status )) {
                    $sms_status = implode( ', ', $sms_status );
                }

                $this->sms_send_status = sprintf( esc_html__( 'SMS Gateway return message: %s', 'happy-birthday' ), $sms_status );
                $this->sms_send_counter++;
                $this->sms_send_failure = false;
            }
        }
    }

    public function send_happy_birthday_email( $bool ) {

        if ( $bool && ! empty( $this->email_speed )) {

            $save_email = um_user( 'user_email' );

            if ( um_user( $this->last_greeted ) == 'resend' ) {
                $this->content_admin_email = array_merge( array( esc_html__( 'Email resent by Admin', 'happy-birthday' )), $this->content_admin_email );
            }

            switch( $this->short_status( um_user( $this->last_greeted_status ) )) {
                case 'email failure':   $this->content_admin_email = array_merge( array( '', esc_html__( 'Email failure = email resend', 'happy-birthday' )), $this->content_admin_email ); break;
                case 'sms failure':     $this->content_admin_email = array_merge( array( '', esc_html__( 'SMS failure = email resend',   'happy-birthday' )), $this->content_admin_email ); break;
                default: break;
            }

            UM()->mail()->send( $save_email, $this->slug, $this->happy_birthday_args );

            if ( $save_email == $this->send_to_email ) {
                $this->email_send_status = sprintf( esc_html__( 'Email WP status %s to %s', 'happy-birthday' ), esc_html__( 'Sent OK', 'happy-birthday' ), $save_email );
                $this->email_send_counter++;

            } else {
                $this->email_send_status = sprintf( esc_html__( 'Email WP status %s to &s', 'happy-birthday' ), esc_html__( 'Not sent', 'happy-birthday' ), $save_email );
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
                $this->wp_mail_sleep( $user_id );
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

    public function wp_mail_sleep( $user_id = false ) {

        if ( UM()->options()->get( $this->slug . '_wp_mail' ) == 1 ) {

            $sleep_seconds = intval( UM()->options()->get( $this->slug . '_email_delay' ));

            if ( empty( $sleep_seconds )) {
                $sleep_seconds = 1;
            }

            if ( ! empty( $user_id )) {
                UM()->user()->remove_cache( $user_id );
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
                    esc_html__( 'Age',                'happy-birthday' ),
                    esc_html__( 'Birth date',         'happy-birthday' ),
                    esc_html__( 'Username',           'happy-birthday' ),
                    esc_html__( 'Display name',       'happy-birthday' ),
                    esc_html__( 'Accoun status',      'happy-birthday' ),
                    esc_html__( 'Registration date',  'happy-birthday' ),
                    esc_html__( 'Last login',         'happy-birthday' ),
                    esc_html__( 'User priority role', 'happy-birthday' ),
                    esc_html__( 'User ID',            'happy-birthday' ),
                    esc_html__( 'Profile page',       'happy-birthday' ),
                    esc_html__( 'Email WP status',    'happy-birthday' ),
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
                        $this->sent_user_list[] = esc_html__( 'Second attempt this hour to send remaining Happy Birthday greetings', 'happy-birthday' );
                        $this->sent_user_list[] = '';

                        $celebrants = $this->get_all_celebrants();

                        $this->send_happy_birthday_greetings( $celebrants );

                    } else {

                        $celebrants = $this->get_all_celebrants();
                        if ( ! empty( $celebrants )) {

                            $this->sent_user_list[] = '';
                            $this->sent_user_list[] = esc_html__( 'Impossible to send Happy Birthday greetings', 'happy-birthday' );
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

            case 'SMS':     $send  = um_user( 'mobile_number' );
                            $title = esc_html__( 'Greetings to mobile number %s', 'happy-birthday' );
                            break;
            case 'none':    $title = esc_html__( 'SMS not possible and email greetings are disabled: No greetings will be sent', 'happy-birthday' );
                            $send  = '';
                            break;
            case 'email':   $send  = um_user( 'user_email' );
                            $title = esc_html__( 'Greetings to email address %s', 'happy-birthday' );
                            break;
            default:        break;
        }

        $title = sprintf( $title, $send );

        $greeted = sprintf( '<a href="%s" target="Happy_Birthday" title="%s">%s</a>', esc_url( um_user_profile_url( $user_id )), $title, um_user( $celebrant_name ));

        if ( ! $this->cronjob && UM()->options()->get( $this->slug . '_modal_list' ) == 1 ) {

            if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'ultimatemember' ) {
                if ( isset( $_REQUEST['update'] ) && $_REQUEST['update'] == 'um_cleared_cache' ) {

                    UM()->user()->remove_cache( $user_id );
                }
            }
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

            $td   = '<td style="padding-bottom:0px;padding-top:0px;">';
            $td2  = '<td colspan="2" style="padding-bottom:0px;padding-top:0px;">';
            $span = '<span style="padding-bottom:0px;padding-top:0px;" title="%s">%s</span>';

            if ( $delta <= 0 ) {

                $greeted = '<table>';
                $celebrants = array_keys( $celebrants );

                foreach( $celebrants as $user_id ) {
                    um_fetch_user( $user_id );

                    $greeted .= '<tr>';

                    if ( um_user( $this->last_greeted ) == 'resend' ) {
                        $greeted .= $td2;
                        $greeted .= esc_html__( 'Resend by Admin', 'happy-birthday' );
                        $greeted .= '</td>';

                    } else {

                        if ( substr( um_user( $this->last_greeted ), 0, 10 ) == substr( $this->today, 0, 10 ) ) {
                            $greeted .= $td;
                            $greeted .= sprintf( $span, esc_html__( 'Time when birthday greeting was sent', 'happy-birthday' ), substr( um_user( $this->last_greeted ), 10 ));
                            $greeted .= '</td>';

                            $greeted .= $td;
                            $greeted .= sprintf( $span, um_user( $this->last_greeted_status ), $this->short_status( um_user( $this->last_greeted_status ) ) );
                            $greeted .= '</td>';

                        } else {

                            if ( substr( um_user( $this->last_greeted_error ), 0, 10 ) == substr( $this->today, 0, 10 ) ) {

                                $greeted .= $td;
                                $greeted .= sprintf( $span, esc_html__( 'Time for birthday greeting attempt', 'happy-birthday' ), substr( um_user( $this->last_greeted_error ), 10 ));
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
                                        $greeted .= sprintf( $span, $status, sprintf( esc_html__( '%s greetings failed', 'happy-birthday' ), $media ));
                                        $greeted .= '</td>';

                                    } else {
                                        $greeted .=$td2;
                                        $greeted .= sprintf( esc_html__( 'Pending %s greetings', 'happy-birthday' ), $media );
                                        $greeted .= '</td>';
                                    }

                                } else {
                                    $greeted .= $td2;
                                    $greeted .= esc_html__( 'Greetings not possible', 'happy-birthday' );
                                    $greeted .= '</td>';
                                }
                            }
                        }
                    }

                    $greeted .= $td;
                    $greeted .= $this->user_profile_link( $user_id, $celebrant_name );
                    $greeted .= '</td>';

                    $greeted .= $td;
                    $greeted .= $this->get_user_age();
                    $greeted .= '</td>';

                    $greeted .= '</tr>';
                }

                $greeted .= '</table>';
                $this->description[] = $greeted;

            } else {

                $greeted = '<table>';

                foreach( $celebrants as $user_id => $type ) {
                    um_fetch_user( $user_id );
                    $greeted .= '<tr>' . $td . $this->user_profile_link( $user_id, $celebrant_name ) . '</td>' . $td . $this->get_user_age() .'</td></tr>';
                }

                $greeted .= '</table>';
                $this->description[] = $greeted;
            }

        } else {

            $this->description[] = '<br />';
        }
    }

    public function current_status_celebrants( $delta ) {

        $this->today = date_i18n( 'Y/m/d l', current_time( 'timestamp' ) + ( $delta * DAY_IN_SECONDS ) );
        $celebrants = $this->get_all_celebrants();

        $link = '';
        if ( UM()->options()->get( 'member_directory_own_table' ) != 1 ) {

            $url = trim( sanitize_text_field( UM()->options()->get( $this->slug . '_um_form_url' )));
            if ( ! empty( $url )) {

                $link = sprintf( '<a href="%s?delta=%s" title="%s" target="Happy_Birthday">%s</a>', esc_url( $url ), $delta,
                esc_html__( 'Members Directory page', 'happy-birthday' ), esc_html__( 'Show', 'happy-birthday' ));
            }
        }

        switch( count( $celebrants ) ) {
            case 0:     $hdr = sprintf( esc_html__( 'No celebrants %s',    'happy-birthday' ), $this->today ); break;
            case 1:     $hdr = sprintf( esc_html__( 'One celebrant %s %s', 'happy-birthday' ), $this->today, $link ); break;
            default:    $hdr = sprintf( esc_html__( '%d celebrants %s %s', 'happy-birthday' ), count( $celebrants ), $this->today, $link );
        }

        if ( $delta == 0 ) {

            $hdr = str_replace( substr( $this->today, 0, 10 ), esc_html__( 'today', 'happy-birthday' ), $hdr );
            $this->celebrants_today = trim( str_replace( $link, '', $hdr ));

            $text_color = UM()->options()->get( $this->slug . '_text_color' );
            if ( empty( $text_color )) {
                $text_color = 'green';
            }

            $hdr = '<span style="color: ' . esc_attr( $text_color ) . '; font-weight: bold;">' . $hdr . '</span>';
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

        $this->plugin_status[] = '<hr>' . sprintf( esc_html__( 'Status: %d Users accepts and %d Users rejects birthday greetings. Default %s: %d', 'happy-birthday' ),
                                            UM()->classes['um_happy_birthday_transients']->transient_manager_counters( 'yes' ),
                                            UM()->classes['um_happy_birthday_transients']->transient_manager_counters( 'no' ),
                                            $default,
                                            UM()->classes['um_happy_birthday_transients']->transient_manager_counters( 'default' ));

        if ( $modal ) {

            $url = strtolower( $this->get_plugin_settings_url());
            $this->plugin_status[] = '<hr>' . sprintf( esc_html__( 'With current %s of User Roles and Account statuses:', 'happy-birthday' ), $url );

        } else {
            $this->plugin_status[] = '<hr>' . esc_html__( 'With current plugin settings of User Roles and Account statuses:', 'happy-birthday' );
        }

        $this->description = array();

        $backward = UM()->options()->get( $this->slug . '_backward' );
        if ( ! is_numeric( $backward ) || intval( $backward ) > 14 ) {
            $backward = 1;
        }

        $forward = UM()->options()->get( $this->slug . '_forward' );
        if ( ! is_numeric( $forward ) || intval( $forward ) > 14 ) {
            $forward = 6;
        }

        $i = -intval( $backward );
        while( $i <= intval( $forward ) ) {
            $this->current_status_celebrants( $i );
            $i++;
        }

        $desc = implode( '<br />', $this->plugin_status );
        $desc .= '<br />';
        $desc .= implode( '', $this->description );

        if ( $modal ) {
            $desc .= $this->cron_job_restart_button();
        }

        return $this->custom_wp_kses( $desc );
    }

    public function cron_job_restart_button() {

        $url_happy_birthday = add_query_arg(
                                            array(
                                                    'um_adm_action' => 'happy_birthday_cron',
                                                    '_wpnonce'      => wp_create_nonce( 'happy_birthday_cron' ),
                                                )
                                            );

        $button_text  = esc_html__( 'Restart Plugin WP Cronjob', 'happy-birthday' );
        $button_title = esc_html__( 'Press this button if you want to have the Plugin WP Cronjob to be scheduled at 5 minutes past the hour.', 'happy-birthday' );

        ob_start();
?>
        <hr>
        <p>
            <a href="<?php echo esc_url( $url_happy_birthday ); ?>" class="button" title="<?php echo esc_attr( $button_title ); ?>">
                <?php esc_attr_e( $button_text ); ?>
            </a>
        </p>
<?php
        $reset_button = ob_get_contents();
        ob_end_clean();

        return $reset_button;
    }

    public function custom_wp_kses( $desc ) {

        add_filter( 'um_late_escaping_allowed_tags', array( $this, 'um_happy_birthday_allowed_tags' ), 99, 2 );
        $desc = wp_kses( $desc, UM()->get_allowed_html( 'templates' ) );
        remove_filter( 'um_late_escaping_allowed_tags', array( $this, 'um_happy_birthday_allowed_tags' ), 99, 2 );

        return $desc;
    }

    public function um_happy_birthday_allowed_tags( $allowed_html, $context ) {

        require_once( Plugin_Path_HB . 'includes/admin/allowed-html-list.php' );

        return $allowed_html;
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
        $url = sprintf( ' <a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Plugin settings', 'happy-birthday' ));

        return $url;
    }

    public function um_email_notifications_happy_birthday( $notifications ) {

        $url = $this->get_plugin_settings_url();

        $notifications[$this->slug] = array(
                                            'key'            => $this->slug,
                                            'title'          => esc_html__( 'Happy Birthday', 'happy-birthday' ),
                                            'subject'        => esc_html__( 'Happy Birthday from {site_name}', 'happy-birthday' ),
                                            'body'           => 'Hi {first_name},<br /><br />We wish you a happy birthday today!<br /><br />The {site_name} Team',
                                            'description'    => esc_html__( 'Whether to send the user an email when someone is today\'s birthday.','happy-birthday' ) . $url,
                                            'default_active' => true,
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

    public function um_happy_birthday_current_directory( $directory_data ) {

        global $current_user;

        $happy_birthday_form = sanitize_text_field( UM()->options()->get( $this->slug . '_um_form' ));

        if ( isset( $directory_data['form_id'] ) && $directory_data['form_id'] == $happy_birthday_form ) {

            $this->account_status_user = $this->get_account_status();

            if ( ! empty( $this->account_status_user )) {

                $delta = get_transient( $this->transient_prefix . $current_user->ID );
                $this->today = date_i18n( 'Y/m/d', current_time( 'timestamp' ) + intval( $delta ) * DAY_IN_SECONDS );
                return true;
            }
        }

        return false;
    }

    public function um_happy_birthday_directories( $query_args, $directory_data ) {

        if ( $this->um_happy_birthday_current_directory( $directory_data )) {

            $query_args['meta_query'] = $this->get_happy_birthday_meta_query( $this->account_status_user );
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

            if ( absint( $delta ) > 14 ) {
                $delta = 0;
            } 

            set_transient( $this->transient_prefix . $current_user->ID, $delta, DAY_IN_SECONDS );
        }

        return $args;
    }

}
