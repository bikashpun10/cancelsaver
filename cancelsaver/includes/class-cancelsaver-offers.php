<?php
// ─── Offers ───────────────────────────────────────────────────────────────────
if ( ! defined( 'ABSPATH' ) ) exit;

class CancelSaver_Offers {

    public static function apply( $sub, $offer ) {
        switch ( $offer ) {
            case 'pause_1':
                $ok = $sub->pause( 1 );
                return $ok
                    ? [ 'success' => true,  'message' => 'Your subscription has been paused for 1 month.' ]
                    : [ 'success' => false, 'message' => 'Could not pause subscription. Please contact support.' ];

            case 'pause_3':
                $ok = $sub->pause( 3 );
                return $ok
                    ? [ 'success' => true,  'message' => 'Your subscription has been paused for 3 months.' ]
                    : [ 'success' => false, 'message' => 'Could not pause subscription.' ];

            case 'skip':
                $ok = $sub->skip_payment();
                return $ok
                    ? [ 'success' => true,  'message' => 'Your next payment has been skipped.' ]
                    : [ 'success' => false, 'message' => 'Could not skip payment.' ];

            case 'discount':
                $amount = CancelSaver_Settings::get( 'cancelsaver_discount_amount', 20 );
                $type   = CancelSaver_Settings::get( 'cancelsaver_discount_type', 'percent' );
                $code   = $sub->apply_discount( $amount, $type );
                if ( $code ) {
                    $label = $type === 'percent' ? "{$amount}%" : "\${$amount}";
                    return [ 'success' => true, 'message' => "{$label} discount has been applied to your subscription!" ];
                }
                return [ 'success' => false, 'message' => 'Could not apply discount.' ];

            default:
                return [ 'success' => false, 'message' => 'Unknown offer.' ];
        }
    }
}
