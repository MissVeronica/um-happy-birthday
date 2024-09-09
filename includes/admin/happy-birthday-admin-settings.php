<?php


if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

class UM_Happy_Birthday_Admin_Settings {

    public $slug               = 'um_greet_todays_birthdays';
    public $new_plugin_version = '';

    function __construct() {

        add_filter( 'um_settings_structure', array( $this, 'um_admin_settings_extension_happy_birthday' ), 10, 1 );
        add_action( 'um_extend_admin_menu',  array( $this, 'copy_email_notifications_happy_birthday' ), 10 );
    }

    public function get_possible_plugin_update( $plugin ) {

        $update = __( 'Plugin version update failure', 'happy-birthday' );
        $transient = get_transient( $plugin );

        if ( is_array( $transient ) && isset( $transient['status'] )) {
            $update = $transient['status'];
        }

        if ( defined( 'Plugin_File_HB' )) {

            $plugin_data = get_plugin_data( Plugin_File_HB );
            if ( ! empty( $plugin_data )) {

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
                                case 1:     $update = '<a href="' . esc_url( $plugin_data['UpdateURI'] ) . '" target="_blank">';
                                            $update = sprintf( __( 'Update to %s plugin version %s%s is now available for download.', 'happy-birthday' ), $update, esc_attr( $this->new_plugin_version ), '</a>' );
                                            break;
                                case 2:     $update = sprintf( __( 'Plugin is updated to the latest version %s.', 'happy-birthday' ), esc_attr( $plugin_data['Version'] ));
                                            break;
                                case 3:     $update = __( 'Unknown encoding format returned from GitHub', 'happy-birthday' );
                                            break;
                                case 4:     $update = __( 'Version number not found', 'happy-birthday' );
                                            break;
                                case 5:     $update = sprintf( __( 'Update to plugin version %s is now available for download from GitHub.', 'happy-birthday' ), esc_attr( $this->new_plugin_version ));
                                            break;
                                default:    $update = __( 'Plugin version update validation failure', 'happy-birthday' );
                                            break;
                            }

                            if ( isset( $plugin_data['PluginURI'] ) && ! empty( $plugin_data['PluginURI'] )) {

                                $update .= sprintf( ' <a href="%s" target="_blank" title="%s">%s</a>',
                                                            esc_url( $plugin_data['PluginURI'] ),
                                                            __( 'GitHub plugin documentation and download', 'happy-birthday' ),
                                                            __( 'Plugin documentation', 'happy-birthday' ));
                            }

                            $today = date_i18n( 'Y/m/d H:i:s', current_time( 'timestamp' ));
                            $update .= '<br />' . sprintf( __( 'Github plugin version status is checked each 24 hours last at %s.', 'happy-birthday' ), esc_attr( $today ));

                            set_transient( $plugin,
                                            array( 'status'       => $update,
                                                'last_version' => $plugin_data['Version'] ),
                                            24 * HOUR_IN_SECONDS
                                        );

                        } else {
                            $update = sprintf( __( 'GitHub remote connection cURL error: %s', 'happy-birthday' ), $error );
                        }

                    } else {
                        $update = __( 'cURL extension not loaded by PHP', 'happy-birthday' );
                    }
                }
            }
        }

        return wp_kses( $update, UM()->get_allowed_html( 'templates' ) );
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

            $validation = 3;
            if ( $content['encoding'] == 'base64' ) {

                $readme  = base64_decode( $content['content'] );
                $version = strrpos( $readme, '# Version' );

                $validation = 4;
                if ( $version !== false ) {

                    $version = array_map( 'trim', array_map( 'sanitize_text_field', explode( "\n", substr( $readme, $version, 40 ))));

                    if ( isset( $plugin_data['Version'] ) && ! empty( $plugin_data['Version'] )) {

                        $version = explode( ' ', $version[0] );
                        if ( isset( $version[2] ) && ! empty( $version[2] )) {

                            $validation = 2;
                            if ( sanitize_text_field( $plugin_data['Version'] ) != $version[2] ) {

                                $validation = 5;
                                if ( isset( $plugin_data['UpdateURI'] ) && ! empty( $plugin_data['UpdateURI'] )) {

                                    $this->new_plugin_version = $version[2];
                                    $validation = 1;
                                }
                            } 
                        }
                    }
                }
            }
        }

        return $validation;
    }

    public function um_admin_settings_extension_happy_birthday( $settings ) {

        if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'um_options' ) {
            if ( isset( $_REQUEST['tab'] ) && $_REQUEST['tab'] == 'extensions' ) {

                $settings['extensions']['sections']['happy-birthday']['title'] = __( 'Happy Birthday', 'happy-birthday' );

                if ( ! isset( $_REQUEST['section'] ) || $_REQUEST['section'] == 'happy-birthday' ) {

                    if ( ! isset( $settings['extensions']['sections']['happy-birthday']['fields'] ) ) {

                        $settings['extensions']['sections']['happy-birthday']['description'] = $this->get_possible_plugin_update( 'um-happy-birthday' );
                        $settings['extensions']['sections']['happy-birthday']['fields']      = $this->create_plugin_settings_fields();
                    }
                }
            }
        }

        return $settings;
    }

    public function create_plugin_settings_fields() {

        if ( isset( $_POST['um-settings-action'] ) &&  $_POST['um-settings-action'] == 'save' ) {

            delete_transient( UM()->classes['um_happy_birthday_transients']->transient_prefix . 'yes' );
            delete_transient( UM()->classes['um_happy_birthday_transients']->transient_prefix . 'no' );
            delete_transient( UM()->classes['um_happy_birthday_transients']->transient_prefix . 'default' );
        }

        $prefix = '&nbsp; * &nbsp;';
        $wp_cron_job = sprintf( '<a href="https://developer.wordpress.org/plugins/cron/" title="%s" target="_blank">WP Cronjob</a>', __( 'What is WP-Cron?', 'happy-birthday' ));

        $description = array();
        $description[] = __( 'Select the hour during the day when the Happy Birthday plugin first will try to send greetings to the User.', 'happy-birthday' );
        $description[] = __( 'New sending attempt each hour if plugin or email/WP-SMS been inactive.', 'happy-birthday' );
        $description[] = __( 'New sending attempt also during next hour if additional Account Status or Roles are selected', 'happy-birthday' );
        $description[] = __( 'A "Resend" must be applied from the WP All Users page and UM Action dropdown at least 2 hours before midnight.', 'happy-birthday' );

        $um_directory_forms = get_posts( array( 'numberposts' => -1,
                                                'post_type'   => 'um_directory',
                                                'post_status' => 'publish'
                                            )
                                        );

        $members_directories = array();
        foreach( $um_directory_forms as $um_directory_form ) {
            $members_directories[$um_directory_form->ID] = $um_directory_form->post_title;
        }

        $cake_color  = UM()->classes['um_happy_birthday_core']->cake_color;
        $title_color = UM()->classes['um_happy_birthday_core']->title_color;
        $title_font  = UM()->classes['um_happy_birthday_core']->title_font;
        $px = UM()->classes['um_happy_birthday_core']->px;

        asort( UM()->classes['um_happy_birthday_core']->icon_options );

        ob_start();
            UM()->classes['um_happy_birthday_core']->show_current_happy_birthday_icon( 'celebrant' );
            $icon_html_1 = ob_get_contents();
        ob_end_clean();

        ob_start();
            UM()->classes['um_happy_birthday_core']->show_current_happy_birthday_icon( 'viewer' );
            $icon_html_2 = ob_get_contents();
        ob_end_clean();

        $two_weeks = array(
                            '0'   => '0',
                            '1'   => '1',
                            '2'   => '2',
                            '3'   => '3',
                            '4'   => '4',
                            '5'   => '5',
                            '6'   => '6',
                            '7'   => '7',
                            '8'   => '8',
                            '9'   => '9',
                            '10'  => '10',
                            '11'  => '11',
                            '12'  => '12',
                            '13'  => '13',
                            '14'  => '14',
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
                    'description'     => UM()->classes['um_happy_birthday']->prepare_status_listing(),
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
                    'options'         => UM()->classes['um_happy_birthday']->account_status,
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
                    'options'         => array( 'user_login'   => __( 'User Login', 'happy-birthday' ),
                                                'display_name' => __( 'Display Name', 'happy-birthday' ),
                                            ),
                    'default'         => 'display_name',
                    'size'            => 'short',
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
                    'id'              => $this->slug . '_text_color',
                    'type'            => 'text',
                    'size'            => 'short',
                    'label'           => $prefix . sprintf( __( 'Select the text color for "%s"', 'happy-birthday' ), UM()->classes['um_happy_birthday']->celebrants_today ),
                    'description'     => __( 'Enter the color for the number of celebrants today text either by the color name or HEX code.', 'happy-birthday' ) . '<br />' .
                                         __( 'Default color is "green".', 'happy-birthday' ) .
                                             ' <a href="https://www.w3schools.com/colors/colors_groups.asp" target="_blank">W3Schools HTML Color Groups</a>',
        );

        $section_fields[] = array(
                    'id'              => $this->slug . '_backward',
                    'type'            => 'select',
                    'size'            => 'short',
                    'label'           => $prefix . __( 'Past Celebrants to show', 'happy-birthday' ),
                    'options'         => $two_weeks,
                    'description'     => __( 'Select the number of past birthdays to show. Default is one day.', 'happy-birthday' ),
        );

        $section_fields[] = array(
                    'id'              => $this->slug . '_forward',
                    'type'            => 'select',
                    'size'            => 'short',
                    'label'           => $prefix . __( 'Upcoming Celebrants to show', 'happy-birthday' ),
                    'options'         => $two_weeks,
                    'description'     => __( 'Select the number of upcoming birthdays to show. Default is 6 days.', 'happy-birthday' ),
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
                                                '1'    => '1',
                                                '5'    => '5',
                                                '10'   => '10',
                                                '15'   => '15',
                                                '20'   => '20',
                                                '25'   => '25',
                                                '50'   => '50',
                                                '75'   => '75',
                                                '100'  => '100',
                                                '125'  => '125',
                                                '150'  => '150',
                                                '175'  => '175',
                                                '200'  => '200',
                                                '225'  => '225',
                                                '250'  => '250',
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
                                                '1'    => '1',
                                                '5'    => '5',
                                                '10'   => '10',
                                                '15'   => '15',
                                                '20'   => '20',
                                                '25'   => '25',
                                                '30'   => '30',
                                                '60'   => '60',
                                                '90'   => '90',
                                                '120'  => '120',
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

        if ( UM()->options()->get( 'member_directory_own_table' ) != 1 ) {

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
        } else {

            $section_fields[] = array(
                            'id'             => $this->slug . '_header',
                            'type'           => 'header',
                            'label'          => __( 'Members Directory for display of Celebrants', 'happy-birthday' ) . '<br />' .
                                                __( 'is disabled because "Custom table for usermeta" is enabled', 'happy-birthday' ),
                        );
        }

        $section_fields[] = array(
                        'id'             => $this->slug . '_header',
                        'type'           => 'header',
                        'label'          => __( 'User Accoun Privacy setting', 'happy-birthday' ),
                    );

        $section_fields[] = array(
                        'id'             => $this->slug . '_account',
                        'type'           => 'checkbox',
                        'label'          => $prefix . __( 'Allow users to enable/disable greetings', 'happy-birthday' ),
                        'checkbox_label' => __( 'Click to allow Users to enable/disable greetings at their Account Privacy page.', 'happy-birthday' ),
                    );

        $section_fields[] = array(
                        'id'             => $this->slug . '_header',
                        'type'           => 'header',
                        'label'          => __( 'Birthday Celebration icons', 'happy-birthday' ),
                    );

        $section_fields[] = array(
                        'id'             => $this->slug . '_cake_candles',
                        'type'           => 'checkbox',
                        'label'          => $prefix . __( 'Birthday Celebration icon', 'happy-birthday' ),
                        'checkbox_label' => __( 'Click to enable a Birthday Celebration icon at the User Profile page after the Celebrant\'s name.', 'happy-birthday' ),
                    );

        $section_fields[] = array(
                        'id'             => $this->slug . '_celebration_icon',
                        'type'           => 'select',
                        'size'           => 'short',
                        'label'          => $prefix . __( 'Select Birthday Celebration icon', 'happy-birthday' ),
                        'options'        => UM()->classes['um_happy_birthday_core']->icon_options,
                        'description'    => __( 'Default Birthday Celebration icon is "A Cake with three Candles"', 'happy-birthday' ) . '<br />' .
                                            __( 'Current Birthday Celebration icons with a simulation of current Title text settings for your Profile:', 'happy-birthday' ) . '<br />' .
                                            __( 'As Celebrant:', 'happy-birthday' ) . $icon_html_1 .
                                            __( 'As Profile Viewer:', 'happy-birthday' ) . $icon_html_2,
                        'conditional'    => array( $this->slug . '_cake_candles', '=', 1 ),
                    );

        $section_fields[] = array(
                        'id'             => $this->slug . '_cake_color',
                        'type'           => 'text',
                        'size'           => 'short',
                        'label'          => $prefix . __( 'Birthday Celebration icon color', 'happy-birthday' ),
                        'description'    => __( 'Enter the color for the Birthday Celebration icon either by the color name or HEX code.', 'happy-birthday' ) . '<br />' .
                                            __( 'Ultimate Member default blue color is #3ba1da and hover color is #44b0ec', 'happy-birthday' ) . '<br />' .
                                            sprintf( __( 'Default color is "%s".', 'happy-birthday' ), $cake_color ) .
                                            ' <a href="https://www.w3schools.com/colors/colors_groups.asp" target="_blank">W3Schools HTML Color Groups</a>',
                        'conditional'    => array( $this->slug . '_cake_candles', '=', 1 ),
                    );

        $section_fields[] = array(
                        'id'             => $this->slug . '_cake_size',
                        'type'           => 'text',
                        'size'           => 'short',
                        'label'          => $prefix . __( 'Birthday Celebration icon size', 'happy-birthday' ),
                        'description'    => sprintf( __( 'Enter the size value in pixels for the Birthday Celebration icon, default value is %s pixels.', 'happy-birthday' ), $px ),
                        'conditional'    => array( $this->slug . '_cake_candles', '=', 1 ),
                    );

        $section_fields[] = array(
                        'id'             => $this->slug . '_title_color',
                        'type'           => 'text',
                        'size'           => 'short',
                        'label'          => $prefix . __( 'Birthday Celebration icon title text and border color', 'happy-birthday' ),
                        'description'    => __( 'Enter the text and border color for the Birthday Celebration icon title either by the color name or HEX code.', 'happy-birthday' ) . '<br />' .
                                            sprintf( __( 'Default color is "%s".', 'happy-birthday' ), $title_color ) .
                                            ' <a href="https://www.w3schools.com/colors/colors_groups.asp" target="_blank">W3Schools HTML Color Groups</a>',
                        'conditional'    => array( $this->slug . '_cake_candles', '=', 1 ),
                    );

        $section_fields[] = array(
                        'id'             => $this->slug . '_title_font',
                        'type'           => 'text',
                        'size'           => 'short',
                        'label'          => $prefix . __( 'Birthday Celebration icon title size and font', 'happy-birthday' ),
                        'description'    => __( 'Enter the text size in pixels and font name for the Birthday Celebration icon title text.', 'happy-birthday' ) . '<br />' .
                                            sprintf( __( 'Default text size and font name "%s"', 'happy-birthday' ), $title_font ),
                        'conditional'    => array( $this->slug . '_cake_candles', '=', 1 ),
                    );

        $section_fields[] = array(
                        'id'             => $this->slug . '_title_celebrant',
                        'type'           => 'text',
                        'label'          => $prefix . __( 'Title text Birthday Celebration icon for the Celebrant', 'happy-birthday' ),
                        'description'    => __( 'Enter the title text and placeholders when the Celebrant hover the Birthday Celebration icon.', 'happy-birthday' ) . '<br />' .
                                            __( 'Default title text: "Congratulations to you {display_name} on your {age_ordinal} birthday. Greetings from the {site_name} team."', 'happy-birthday' ),
                        'conditional'    => array( $this->slug . '_cake_candles', '=', 1 ),
                    );

        $section_fields[] = array(
                        'id'             => $this->slug . '_title_viewer',
                        'type'           => 'text',
                        'label'          => $prefix . __( 'Title text Birthday Celebration icon for a Profile Viewer', 'happy-birthday' ),
                        'description'    => __( 'Enter the title text and placeholders when a Profile visitor hover the Birthday Celebration icon.', 'happy-birthday' ) . '<br />' .
                                            __( 'Default title text: "{display_name} is celebrating {his_her} {age_ordinal} birthday today."', 'happy-birthday' ),
                        'conditional'    => array( $this->slug . '_cake_candles', '=', 1 ),
                    );

        $section_fields[] = array(
                        'id'             => $this->slug . '_title_left',
                        'type'           => 'text',
                        'size'           => 'short',
                        'label'          => $prefix . __( 'Title text Birthday Celebration box horizontal position', 'happy-birthday' ),
                        'description'    => __( 'Enter the title text box horizontal position to the left or right as negative or positiv pixels numbers. Default value is 0.', 'happy-birthday' ),
                        'conditional'    => array( $this->slug . '_cake_candles', '=', 1 ),
                    );

        $section_fields[] = array(
                        'id'             => $this->slug . '_title_width',
                        'type'           => 'text',
                        'size'           => 'short',
                        'label'          => $prefix . __( 'Title text Birthday Celebration box width', 'happy-birthday' ),
                        'description'    => __( 'Enter the title text box width in pixels. Default value is 300.', 'happy-birthday' ),
                        'conditional'    => array( $this->slug . '_cake_candles', '=', 1 ),
                    );

        return $section_fields;
    }

    public function copy_email_notifications_happy_birthday() {

        $located = UM()->mail()->locate_template( $this->slug );

        if ( ! is_file( $located ) || filesize( $located ) == 0 ) {
            $located = wp_normalize_path( STYLESHEETPATH . '/ultimate-member/email/' . $this->slug . '.php' );
        }

        clearstatcache();
        if ( ! file_exists( $located ) || filesize( $located ) == 0 ) {

            wp_mkdir_p( dirname( $located ) );

            $email_source = file_get_contents( Plugin_Path_HB . $this->slug . '.php' );
            file_put_contents( $located, $email_source );

            if ( ! file_exists( $located ) ) {
                file_put_contents( um_path . 'templates/email/' . $this->slug . '.php', $email_source );
            }
        }
    }
}


new UM_Happy_Birthday_Admin_Settings();
