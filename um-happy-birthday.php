<?php
/**
 * Plugin Name:         Ultimate Member - Happy Birthday
 * Description:         Extension to Ultimate Member for Birthday greeting emails.
 * Version:             1.1.0
 * Requires PHP:        7.4
 * Author:              Miss Veronica
 * License:             GPL v3 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:          https://github.com/MissVeronica
 * Text Domain:         ultimate-member
 * Domain Path:         /languages
 * UM version:          2.8.2
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

class UM_Happy_Birthday {

    function __construct() {

        add_filter( 'um_email_notifications', array( $this, 'um_email_notifications' ), 10, 1 );        

        if ( isset( UM()->options()->options[ 'um_greet_todays_birthdays_on' ] ) && UM()->options()->options[ 'um_greet_todays_birthdays_on' ] == 1 ) {

            add_action( 'um_cron_birthday_greet_notification', array( $this, 'um_cron_task_birthday_greet_notification' ));

            if ( ! wp_next_scheduled ( 'um_cron_birthday_greet_notification' ) ) {
                wp_schedule_event( time(), 'hourly', 'um_cron_birthday_greet_notification' );
            }
        }
    }

    public function um_email_notifications( $notifications ) {

        $notifications['um_greet_todays_birthdays'] = array(
                                'key'            => 'um_greet_todays_birthdays',
                                'title'          => __( 'Happy Birthday!', 'ultimate-member' ),
                                'subject'        => __( 'Happy Birthday from {site_name}', 'ultimate-member' ),
                                'body'           => 'Hi {first_name},<br /><br />We wish you a happy birthday!<br /><br />The {site_name} Team',
                                'description'    => __('Whether to send the user an email when someone is today\'s birthday.','ultimate-member'),
                                'recipient'   	 => 'member',
                                'default_active' => true,
                            );

        if ( ! array_key_exists( 'um_greet_todays_birthdays_on', UM()->options()->options ) ) {

            UM()->options()->options[ 'um_greet_todays_birthdays_on' ]  = 1;
            UM()->options()->options[ 'um_greet_todays_birthdays_sub' ] = $notifications['um_greet_todays_birthdays']['subject'];
        }

        $located = wp_normalize_path( STYLESHEETPATH . '/ultimate-member/email/um_greet_todays_birthdays.php' );

        if ( ! file_exists( $located )) {
            if ( is_dir( STYLESHEETPATH . '/ultimate-member/email' )) {
                file_put_contents( $located, $notifications['um_greet_todays_birthdays']['body']  );
            }
        }

        return $notifications;
    }

    public function um_cron_task_birthday_greet_notification() {

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

new UM_Happy_Birthday();


