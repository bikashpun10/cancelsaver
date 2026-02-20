<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CancelSaver_Settings {

    public static function init() {}

    public static function get( $key, $default = '' ) {
        return get_option( $key, $default );
    }

    public static function is_enabled() {
        return self::get( 'cancelsaver_enabled', '1' ) === '1';
    }

    public static function get_all() {
        return [
            'enabled'         => self::is_enabled(),
            'offer_pause'     => self::get( 'cancelsaver_offer_pause', '1' ) === '1',
            'offer_skip'      => self::get( 'cancelsaver_offer_skip', '1' ) === '1',
            'offer_discount'  => self::get( 'cancelsaver_offer_discount', '1' ) === '1',
            'discount_amount' => (float) self::get( 'cancelsaver_discount_amount', 20 ),
            'discount_type'   => self::get( 'cancelsaver_discount_type', 'percent' ),
            'headline'        => self::get( 'cancelsaver_headline', 'Wait — before you go!' ),
            'subheadline'     => self::get( 'cancelsaver_subheadline', "We'd hate to lose you." ),
        ];
    }
}
