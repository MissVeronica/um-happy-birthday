<?php


if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

class UM_Happy_Birthday_Shortcode {

    function __construct() {

        add_shortcode( 'birthdays_today', array( $this, 'happy_birthdays_today'));
    }

    public function happy_birthdays_today( $atts, $content ) {

        global $current_user;

        $output = '';
        if ( $current_user->ID == um_profile_id() ) {

            $url = UM()->options()->get( UM()->classes['um_happy_birthday']->slug . '_um_form_url' );

            if ( ! empty( $url )) {
                $content = sanitize_text_field( $content );

                if ( empty( $content )) {
                    $content = __( 'Happy Birthdays today', 'happy-birthday' );
                }

                $output = '<a href="' . esc_url( $url ) . '" title="' . esc_attr( $content ) . '">' . esc_attr( $content ) . '</a>';
            }
        }

        return $output;  
    }
}

new UM_Happy_Birthday_Shortcode();
