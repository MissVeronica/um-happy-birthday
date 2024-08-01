<?php


if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

class UM_Happy_Birthday_Transients {

    public $transient_life   = 5 * DAY_IN_SECONDS;
    public $transient_prefix = 'birthday_greetings_';
    public $slug             = 'um_greet_todays_birthdays';

    function __construct() {


    }

    public function transient_manager_counters( $transient, $delta = false ) {

        $transient_name = $this->transient_prefix . $transient;

        $value = get_transient( $transient_name );

        if ( $value === false ) {

            $args = array(  'fields'     => array( 'ID' ),
                            'meta_query' => array( 'relation' => 'AND' )
                        );

            $account_status = UM()->classes['um_happy_birthday']->get_account_status();

            $args['meta_query'][] = array(
                                            'key'     => 'account_status',
                                            'value'   => $account_status,
                                            'compare' => 'IN',
                                        );

            switch( $transient ) {

                case 'no':      $args['meta_query'][] = array(
                                                                'key'     => $this->slug . '_privacy',
                                                                'value'   => '"no"',
                                                                'compare' => 'LIKE'
                                                            );
                                break;

                case 'yes':     $args['meta_query'][] = array(
                                                                'key'     => $this->slug . '_privacy',
                                                                'value'   => '"yes"',
                                                                'compare' => 'LIKE'
                                                            );
                                break;

                case 'default': $args['meta_query'][] = array(  
                                                                'key'     => $this->slug . '_privacy',
                                                                'compare' => 'NOT EXISTS'
                                                            );
                                break;

                default:    return;
                            break;
            }

            $users = get_users( $args );
            $count = count( $users );

            set_transient( $transient_name, $count, $this->transient_life );

            return $count;
        }

        if ( $delta !== false ) {

            $value = $value + $delta;
            set_transient( $transient_name, $value );
        }

        return $value;
    }

}
