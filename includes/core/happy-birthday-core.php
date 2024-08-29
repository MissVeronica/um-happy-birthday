<?php


if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

class UM_Happy_Birthday_Core {

    public $px           = '40';
    public $cake_color   = 'white';
    public $icon_options = array();

    function __construct() {

        add_shortcode( 'birthdays_today',            array( $this, 'happy_birthdays_today' ));

        add_action( 'um_after_profile_name_inline',  array( $this, 'um_after_profile_name_show_celebration_icon' ), 9, 1 );
        add_filter( 'um_account_tab_privacy_fields', array( $this, 'um_account_tab_privacy_fields_happy_birthday' ), 10, 2 );
    }

    public function um_account_tab_privacy_fields_happy_birthday( $args, $shortcode_args ) {

        global $current_user;

        UM()->classes['um_happy_birthday']->get_all_selected_user_roles();

        if ( ! empty( UM()->classes['um_happy_birthday']->all_selected_user_roles )) {

            $prio_role = UM()->roles()->get_priority_user_role( $current_user->ID );
            if ( in_array( $prio_role, UM()->classes['um_happy_birthday']->all_selected_user_roles )) {

                $args .= ',' . UM()->classes['um_happy_birthday']->slug . '_privacy';
            }
        }

        return $args;
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

    public function um_after_profile_name_show_celebration_icon( $args ) {

        if ( UM()->options()->get( UM()->classes['um_happy_birthday']->slug . '_cake_candles' ) == 1 ) {

            if ( strpos( um_user( 'birth_date' ), date_i18n( '/m/d', current_time( 'timestamp' )) ) !== false ) {

                if ( $this->get_user_account_consent_setting() !== false ) {

                    $this->show_current_happy_birthday_icon();
                }
            }
        }
    }

    public function show_current_happy_birthday_icon() {

        $title = __( 'Happy Birthday today', 'happy-birthday' );

        $color = trim( UM()->options()->get( UM()->classes['um_happy_birthday']->slug . '_cake_color' ));
        if ( ! empty( $color )) {
            $this->cake_color = sanitize_text_field( $color );
        }

        $px = trim( UM()->options()->get( UM()->classes['um_happy_birthday']->slug . '_cake_size' ));
        if ( ! empty( $px )) {
            $this->px = absint( str_replace( 'px', '', strtolower( sanitize_text_field( $px ))));
        }

        $class_icon = 'fas fa-cake-candles';
        $icon = UM()->options()->get( UM()->classes['um_happy_birthday']->slug . '_celebration_icon' );

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

        $current_consent = um_user( UM()->classes['um_happy_birthday']->slug . '_privacy' );
        $consent = false;

        if ( empty( $current_consent )) {

            if ( UM()->options()->get( UM()->classes['um_happy_birthday']->slug . '_without_consent' ) == 1 ) {
                $consent = true;
            }

        } else {

            if ( is_array( $current_consent ) && $current_consent[0] == 'yes' ) {
                $consent = true;
            } 
        }

        return $consent;
    }
}
