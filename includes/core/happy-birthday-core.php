<?php


if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

class UM_Happy_Birthday_Core {

    public $px                 = '40';
    public $cake_color         = 'white';
    public $title_color        = 'black';
    public $title_font         = '20px arial';
    public $title_left         = '-190';
    public $title_width        = '300';
    public $title_left_default = -190;

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
            if ( UM()->options()->get( 'member_directory_own_table' ) != 1 ) {

                $url = UM()->options()->get( UM()->classes['um_happy_birthday']->slug . '_um_form_url' );

                if ( ! empty( $url )) {
                    $content = sanitize_text_field( $content );

                    if ( empty( $content )) {
                        $content = __( 'Happy Birthdays today', 'happy-birthday' );
                    }

                    $output = '<a href="' . esc_url( $url ) . '" title="' . esc_attr( $content ) . '">' . esc_attr( $content ) . '</a>';
                }
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

    public function get_title_text( $type ) {

        $title = trim( stripslashes( sanitize_text_field( UM()->options()->get( UM()->classes['um_happy_birthday']->slug . $type ))));

        if ( empty( $title )) {

            switch( $type ) {
                case '_title_celebrant':    $title = __( 'Congratulations to you {display_name} on your {age_ordinal} birthday. Greetings from the {site_name} team.', 'happy-birthday' );
                                            break;
                case '_title_viewer':       $title = __( '{display_name} is celebrating {his_her} {age_ordinal} birthday today.', 'happy-birthday' );
                                            break;
                default:                    $title = '';
            }
        }

        $title = um_convert_tags( $title, UM()->classes['um_happy_birthday']->happy_birthday_args );

        return $title;
    }

    public function show_current_happy_birthday_icon( $type = false ) {

        global $current_user;

        UM()->classes['um_happy_birthday']->prepare_placeholders( um_profile_id() );

        if ( ! is_admin()) {

            if ( $current_user->ID == um_profile_id()) {

                $title = $this->get_title_text( '_title_celebrant' );

            } else {

                $title = $this->get_title_text( '_title_viewer' );
            }

        } else {

            $title = '';
            if ( ! empty( $type )) {
                switch( $type ) {
                    case 'celebrant':   $title = $this->get_title_text( '_title_celebrant' ); break;
                    case 'viewer':      $title = $this->get_title_text( '_title_viewer' ); break;
                    default:            $title = '';
                }
            }
        }

        $title_font = UM()->options()->get( UM()->classes['um_happy_birthday']->slug . '_title_font' );
        if ( ! empty( $title_font ) && strlen( $title_font ) > 5 ) {
            $this->title_font = sanitize_text_field( $title_font );
        }

        $color = trim( UM()->options()->get( UM()->classes['um_happy_birthday']->slug . '_cake_color' ));
        if ( ! empty( $color )) {
            $this->cake_color = sanitize_text_field( $color );
        }

        $title_color = trim( UM()->options()->get( UM()->classes['um_happy_birthday']->slug . '_title_color' ));
        if ( ! empty( $title_color )) {
            $this->title_color = sanitize_text_field( $title_color );
        }

        $title_left = trim( str_replace( 'px', '', strtolower( UM()->options()->get( UM()->classes['um_happy_birthday']->slug . '_title_left' ))));
        if ( ! empty( $title_left ) && is_numeric( $title_left )) {
            $this->title_left = $this->title_left_default + intval( sanitize_text_field( $title_left ));
        }

        $title_width = trim( str_replace( 'px', '', strtolower( UM()->options()->get( UM()->classes['um_happy_birthday']->slug . '_title_width' ))));
        if ( ! empty( $title_width ) && is_numeric( $title_width )) {
            $this->title_width = absint( sanitize_text_field( $title_width ));
        }

        $px = trim( str_replace( 'px', '', strtolower( UM()->options()->get( UM()->classes['um_happy_birthday']->slug . '_cake_size' ))));
        if ( ! empty( $px ) && is_numeric( $px )) {
            $this->px = absint( sanitize_text_field( $px ));
        }

        $class_icon = 'fas fa-cake-candles';
        $icon = UM()->options()->get( UM()->classes['um_happy_birthday']->slug . '_celebration_icon' );

        if ( ! empty( $icon ) && isset( $this->icon_options[$icon] )) {
            $class_icon = $icon;
        }
?>
        <style>
            .um-field-label-icon-happy-birthday {
                position: relative;
            }

            .um-field-label-icon-happy-birthday:hover:after {
                content: attr(happy-birthday-title);
                position: absolute;
                font: <?php echo esc_attr( $this->title_font ); ?>;
                top: 110%;
                left: <?php echo esc_attr( $this->title_left ); ?>px;
                background: <?php echo esc_attr( $this->cake_color ); ?>;
                color: <?php echo esc_attr( $this->title_color ); ?>;
                width: <?php echo esc_attr( $this->title_width ); ?>px;
                box-sizing: border-box;
                border: 2px solid <?php echo esc_attr( $this->title_color ); ?>;
                border-radius: 8%;
                padding-top: 8px;
                padding-right: 8px;
                padding-bottom: 8px;
                padding-left: 8px;
                opacity: 1.0;
                z-index: 10;
            }
        </style>
        <span class="um-field-label-icon-happy-birthday" 
              style="font-size: <?php echo esc_attr( $this->px ); ?>px; color: <?php echo esc_attr( $this->cake_color ); ?>;"
              happy-birthday-title="<?php echo esc_attr( $title ); ?>">
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
