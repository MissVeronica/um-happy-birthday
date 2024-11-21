<?php

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

class UM_Happy_Birthday_Predefined {

    public $slug                = 'um_greet_todays_birthdays';

    public $last_greeted        = 'um_birthday_greeted_last';
    public $last_greeted_status = 'um_birthday_greeted_last_status';
    public $last_greeted_error  = 'um_birthday_greeted_last_error';
    public $greetings_consent   = 'um_birthday_greetings_consent';

    public $privacy_options     = array();

    function __construct() {

        add_filter( 'um_predefined_fields_hook', array( $this, 'custom_predefined_fields_happy_birthday' ), 10, 1 );
        add_action( 'init',                      array( $this, 'plugin_load_translations' ), 10 );
    }

    public function plugin_load_translations() {

        $this->privacy_options = array(
                                        'no'  => __( 'No',  'happy-birthday' ),
                                        'yes' => __( 'Yes', 'happy-birthday' ),
                                    );
    }

    public function custom_predefined_fields_happy_birthday( $predefined_fields ) {

        UM()->classes['um_happy_birthday_core']->icon_options = apply_filters( 'happy_birthday_icons', array(

            'fas fa-cake-candles'      => __( 'A Cake with three Candles', 'happy-birthday' ),
            'fas fa-champagne-glasses' => __( 'Champagne two Glasses',     'happy-birthday' ),
            'fas fa-heart'             => __( 'Heart solid',               'happy-birthday' ),
            'far fa-heart'             => __( 'Heart regular',             'happy-birthday' ),
            'fas fa-thumbs-up'         => __( 'Thumbs up solid',           'happy-birthday' ),

            'far fa-thumbs-up'         => __( 'Thumbs up regular',         'happy-birthday' ),
            'fas fa-hand-point-up'     => __( 'Hand point up solid',       'happy-birthday' ),
            'far fa-hand-point-up'     => __( 'Hand point up regular',     'happy-birthday' ),
            'fas fa-hands'             => __( 'Hands solid',               'happy-birthday' ),
            'fas fa-hands-clapping'    => __( 'Hands clapping solid',      'happy-birthday' ),

            'fas fa-face-smile'        => __( 'Face smile solid',          'happy-birthday' ),
            'far fa-face-smile'        => __( 'Face smile regular',        'happy-birthday' ),
            'fas fa-face-grin'         => __( 'Face grin solid',           'happy-birthday' ),
            'far fa-face-grin'         => __( 'Face grin regular',         'happy-birthday' ),
            'fas fa-users'             => __( 'Users solid',               'happy-birthday' ),

            'fas fa-user'              => __( 'User solid',                'happy-birthday' ),
            'far fa-user'              => __( 'User regular',              'happy-birthday' ),
            'fas fa-calendar'          => __( 'Calendar solid',            'happy-birthday' ),
            'far fa-calendar'          => __( 'Calendar regular',          'happy-birthday' ),
            'fas fa-calendar-days'     => __( 'Calendar days solid',       'happy-birthday' ),

            'far fa-calendar-days'     => __( 'Calendar days regular',     'happy-birthday' ),
            'fas fa-circle-user'       => __( 'Circle User solid',         'happy-birthday' ),
            'far fa-circle-user'       => __( 'Circle User regular',       'happy-birthday' ),
            'fas fa-circle'            => __( 'Circle solid',              'happy-birthday' ),
            'far fa-circle'            => __( 'Circle regular',            'happy-birthday' ),

            'fas fa-comment'           => __( 'Comment solid',             'happy-birthday' ),
            'far fa-comment'           => __( 'Comment regular',           'happy-birthday' ),
            'fas fa-comments'          => __( 'Comments solid',            'happy-birthday' ),
            'far fa-comments'          => __( 'Comments regular',          'happy-birthday' ),
            'fas fa-bell'              => __( 'Bell solid',                'happy-birthday' ),

            'far fa-bell'              => __( 'Bell regular',              'happy-birthday' ),
            'fas fa-square'            => __( 'Square solid',              'happy-birthday' ),
            'far fa-square'            => __( 'Square regular',            'happy-birthday' ),
            'fas fa-sun'               => __( 'Sun solid',                 'happy-birthday' ),
            'far fa-sun'               => __( 'Sun regular',               'happy-birthday' ),

            'fas fa-flag'              => __( 'Flag solid',                'happy-birthday' ),
            'far fa-flag'              => __( 'Flag regular',              'happy-birthday' ),
            'fas fa-flag-usa'          => __( 'Flag USA solid',            'happy-birthday' ),
            'fas fa-gift'              => __( 'Gift solid',                'happy-birthday' ),
            'fas fa-gifts'             => __( 'Gifts solid',               'happy-birthday' ),

            'fas fa-trophy'            => __( 'Trophy solid',              'happy-birthday' ),
            'fas fa-medal'             => __( 'Medal solid',               'happy-birthday' ),
            'fas fa-star'              => __( 'Star solid',                'happy-birthday' ),
            'far fa-star'              => __( 'Star regular',              'happy-birthday' ),

        ));

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
                                                    'help'          => __( 'Enable/Disable birthday greetings via email or SMS text message inclusive a Celebration icon at the Profile page', 'happy-birthday' ),
                                                    'required'      => 0,
                                                    'public'        => 1,
                                                    'editable'      => true,
                                                    'default'       => $default,
                                                    'options'       => $this->privacy_options,
                                                    'account_only'  => true,
                                                );
        }

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

new UM_Happy_Birthday_Predefined();

