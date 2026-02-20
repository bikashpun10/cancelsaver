<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CancelSaver_Interceptor {

    public static function init() {
        if ( ! CancelSaver_Settings::is_enabled() ) return;

        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
        add_action( 'wp_ajax_cancelsaver_accept_offer', [ __CLASS__, 'handle_offer' ] );

        // Track actual cancellations
        CancelSaver_Compat::on_cancelled( [ __CLASS__, 'on_cancelled' ] );
    }

    public static function enqueue() {
        if ( ! is_account_page() ) return;

        wp_enqueue_style(
            'cancelsaver',
            CANCELSAVER_URL . 'assets/css/cancelsaver.css',
            [], CANCELSAVER_VERSION
        );

        wp_enqueue_script(
            'cancelsaver',
            CANCELSAVER_URL . 'assets/js/cancelsaver.js',
            [ 'jquery' ], CANCELSAVER_VERSION, true
        );

        $s = CancelSaver_Settings::get_all();

        wp_localize_script( 'cancelsaver', 'CancelSaver', [
            'ajaxurl'   => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'cancelsaver' ),
            'selectors' => CancelSaver_Compat::get_cancel_selectors(),
            'plugin'    => CancelSaver_Compat::$active_plugin,
            'settings'  => $s,
            'strings'   => [
                'headline'       => $s['headline'],
                'subheadline'    => $s['subheadline'],
                'pause_1'        => 'Pause my subscription for 1 month',
                'pause_3'        => 'Pause my subscription for 3 months',
                'skip'           => 'Skip my next payment',
                'discount'       => self::discount_label( $s ),
                'cancel_anyway'  => 'No thanks, cancel my subscription',
                'processing'     => 'Just a moment...',
                'success'        => "Done! Your subscription has been saved.",
            ],
        ] );
    }

    private static function discount_label( $s ) {
        $a = $s['discount_amount'];
        $t = $s['discount_type'];
        $label = $t === 'percent' ? "{$a}% off" : "\${$a} off";
        return "Get {$label} your next payment — and stay!";
    }

    /**
     * AJAX: Accept a retention offer
     */
    public static function handle_offer() {
        check_ajax_referer( 'cancelsaver', 'nonce' );

        $offer = sanitize_text_field( $_POST['offer'] ?? '' );
        $sub_id = absint( $_POST['sub_id'] ?? 0 );

        if ( ! $offer || ! $sub_id ) {
            wp_send_json_error( [ 'message' => 'Invalid request.' ] );
        }

        $sub = CancelSaver_Compat::get_subscription( $sub_id );

        if ( ! $sub ) {
            wp_send_json_error( [ 'message' => 'Subscription not found.' ] );
        }

        if ( (int) $sub->get_customer_id() !== get_current_user_id() ) {
            wp_send_json_error( [ 'message' => 'Unauthorized.' ] );
        }

        $result = CancelSaver_Offers::apply( $sub, $offer );

        if ( $result['success'] ) {
            CancelSaver_Tracker::track( $sub_id, $sub->get_customer_id(), 'offer_accepted', $offer, $sub->get_total() );
            wp_send_json_success( [ 'message' => $result['message'] ] );
        } else {
            wp_send_json_error( [ 'message' => $result['message'] ] );
        }
    }

    /**
     * Fired when subscription is actually cancelled (user chose "cancel anyway")
     */
    public static function on_cancelled( $sub ) {
        $sub_id = is_object( $sub ) ? $sub->get_id() : $sub;
        $obj    = is_object( $sub ) ? $sub : CancelSaver_Compat::get_subscription( $sub_id );
        if ( ! $obj ) return;

        CancelSaver_Tracker::track(
            $sub_id,
            $obj->get_customer_id(),
            'cancelled',
            null,
            $obj->get_total()
        );

        // Trigger win-back email if enabled
        if ( CancelSaver_Settings::get( 'cancelsaver_winback_enabled' ) === '1' ) {
            $delay = (int) CancelSaver_Settings::get( 'cancelsaver_winback_delay', 1 );
            wp_schedule_single_event(
                time() + ( $delay * DAY_IN_SECONDS ),
                'cancelsaver_send_winback',
                [ $sub_id, $obj->get_customer_email() ]
            );
        }
    }
}
