<?php
/**
 * Plugin Name:         Ultimate Member - Happy Birthday
 * Description:         Extension to Ultimate Member for Birthday greeting emails.
 * Version:             1.4.0
 * Requires PHP:        7.4
 * Author:              Miss Veronica
 * License:             GPL v3 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:          https://github.com/MissVeronica
 * Text Domain:         ultimate-member
 * Domain Path:         /languages
 * UM version:          2.8.5
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

use um\core\Options;

class UM_Happy_Birthday {

    function __construct() {

        add_filter( 'um_email_notifications',                 array( $this, 'um_email_notifications' ), 10, 1 );
        add_action(	'um_extend_admin_menu',                   array( $this, 'copy_email_notifications_happy_birthday' ), 10 );
        add_filter( 'um_admin_settings_email_section_fields', array( $this, 'um_admin_settings_email_section_happy_birthday' ), 9, 2 );

        $UM_class = new Options();
        $reflectionProperty = new \ReflectionProperty( Options::class, 'options' );
        $reflectionProperty->setAccessible( true );

        $UM_options = $reflectionProperty->getValue( $UM_class );

        if ( isset( $UM_options[ 'um_greet_todays_birthdays_on' ] ) && $UM_options[ 'um_greet_todays_birthdays_on' ] == 1 ) {

            add_action( 'um_cron_birthday_greet_notification', array( $this, 'um_cron_task_birthday_greet_notification' ));

            if ( ! wp_next_scheduled ( 'um_cron_birthday_greet_notification' ) ) {
                wp_schedule_event( time(), 'hourly', 'um_cron_birthday_greet_notification' );
            }
        }

        define( 'Happy_Birthday_Path', plugin_dir_path( __FILE__ ) );
    }

    public function um_email_notifications( $notifications ) {

        $notifications['um_greet_todays_birthdays'] = array(
                                'key'            => 'um_greet_todays_birthdays',
                                'title'          => __( 'Happy Birthday!', 'ultimate-member' ),
                                'subject'        => __( 'Happy Birthday from {site_name}', 'ultimate-member' ),
                                'body'           => 'Hi {first_name},<br /><br />We wish you a happy birthday today!<br /><br />The {site_name} Team',
                                'description'    => __('Whether to send the user an email when someone is today\'s birthday.','ultimate-member'),
                                'recipient'   	 => 'member',
                                'default_active' => true,
                            );

        $UM_class = new Options();
        $reflectionProperty = new \ReflectionProperty( Options::class, 'options' );
        $reflectionProperty->setAccessible( true );

        if ( ! array_key_exists( 'um_greet_todays_birthdays_on', $reflectionProperty->getValue( $UM_class ) ) ) {

            $reflectionProperty->setValue( $UM_class[ 'um_greet_todays_birthdays_on' ], 1 );
            $reflectionProperty->setValue( $UM_class[ 'um_greet_todays_birthdays_sub' ], $notifications['um_greet_todays_birthdays']['subject'] );
        }

        return $notifications;
    }

    public function um_admin_settings_email_section_happy_birthday( $section_fields, $email_key ) {

        if ( $email_key == 'um_greet_todays_birthdays' ) {

            $section_fields[] = array(
                'id'            => $email_key . '_hour',
                'type'          => 'select',
                'size'          => 'short',
                'options'       => array(
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
                'label'         => __( 'Happy Birthday - Send email during this hour', 'ultimate-member' ),
                'description'   => __( 'Select the hour when the Happy Birthday email will be sent to the User.', 'ultimate-member' ),
                'conditional'   => array( $email_key . '_on', '=', 1 ),
            );
        }

        return $section_fields;
    }

    public function copy_email_notifications_happy_birthday() {

        $slug = 'um_greet_todays_birthdays';

        $located = UM()->mail()->locate_template( $slug );
        if ( ! is_file( $located ) || filesize( $located ) == 0 ) {
            $located = wp_normalize_path( STYLESHEETPATH . '/ultimate-member/email/' . $slug . '.php' );
        }

        clearstatcache();
        if ( ! file_exists( $located ) || filesize( $located ) == 0 ) {

            wp_mkdir_p( dirname( $located ) );

            $email_source = file_get_contents( Happy_Birthday_Path . $slug . '.php' );
            file_put_contents( $located, $email_source );

            if ( ! file_exists( $located ) ) {
                file_put_contents( um_path . 'templates/email/' . $slug . '.php', $email_source );
            }
        }
    }

    public function um_cron_task_birthday_greet_notification() {

        $happy_birthday_hour = sanitize_text_field( UM()->options()->get( 'um_greet_todays_birthdays_hour' ));
        $current_hour = date_i18n( 'H', current_time( 'timestamp' ));

        if ( empty( $happy_birthday_hour ) || $current_hour >= $happy_birthday_hour ) {

            $args = array(
                        'fields'     => 'ids',
                        'number'     => -1,
                        'meta_query' => array(
                                    'relation' => 'AND',
                                    array(
                                        'key'     => 'birth_date',
                                        'value'   => date_i18n( '/m/d', current_time( 'timestamp' )),
                                        'compare' => 'LIKE',
                                        ),
                                    ),
                        );

            $celebrants = get_users( $args );

            if ( ! empty( $celebrants ) ) {

                $today = date_i18n( 'Y/m/d', current_time( 'timestamp' ));
                foreach( $celebrants as $user_id ) {

                    um_fetch_user( $user_id );

                    if ( empty ( um_user( 'um_birthday_greeted_last' ) ) || um_user( 'um_birthday_greeted_last' ) != $today ) {

                        UM()->mail()->send( um_user( 'user_email' ), 'um_greet_todays_birthdays', array(

                                            'tags'		    => array(
                                                                '{display_name}',
                                                                '{first_name}',
                                                                '{last_name}',
                                                                '{title}',
                                                                '{today}',
                                                            ),

                                            'tags_replace'  => array(
                                                                um_user( 'display_name' ),
                                                                um_user( 'first_name' ),
                                                                um_user( 'last_name' ),
                                                                um_user( 'title' ),
                                                                $today,
                                                            ),
                                        )
                                    );

                        update_user_meta( $user_id, 'um_birthday_greeted_last', $today );
                    }

                    UM()->user()->remove_cache( $user_id );
                }
            }
        }
    }
}

new UM_Happy_Birthday();
