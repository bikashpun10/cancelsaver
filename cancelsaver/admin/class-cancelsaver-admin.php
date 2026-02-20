<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CancelSaver_Admin {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
    }

    public static function menu() {
        add_menu_page( 'CancelSaver', 'CancelSaver', 'manage_woocommerce', 'cancelsaver',
            [ __CLASS__, 'dashboard' ], 'dashicons-shield-alt', 58 );
        add_submenu_page( 'cancelsaver', 'Dashboard', 'Dashboard', 'manage_woocommerce', 'cancelsaver', [ __CLASS__, 'dashboard' ] );
        add_submenu_page( 'cancelsaver', 'Settings', 'Settings', 'manage_woocommerce', 'cancelsaver-settings', [ __CLASS__, 'settings' ] );
    }

    public static function register_settings() {
        $keys = [
            'cancelsaver_enabled', 'cancelsaver_offer_pause', 'cancelsaver_offer_skip',
            'cancelsaver_offer_discount', 'cancelsaver_discount_amount', 'cancelsaver_discount_type',
            'cancelsaver_headline', 'cancelsaver_subheadline',
            'cancelsaver_winback_enabled', 'cancelsaver_winback_subject', 'cancelsaver_winback_delay',
        ];
        foreach ( $keys as $k ) register_setting( 'cancelsaver_options', $k );
    }

    public static function enqueue( $hook ) {
        if ( strpos( $hook, 'cancelsaver' ) === false ) return;
        wp_enqueue_style( 'cancelsaver-admin', CANCELSAVER_URL . 'assets/css/cancelsaver-admin.css', [], CANCELSAVER_VERSION );
    }

    private static function nav( $current ) {
        $pages = [
            'cancelsaver'          => [ 'icon' => '📊', 'label' => 'Dashboard' ],
            'cancelsaver-settings' => [ 'icon' => '⚙️', 'label' => 'Settings'  ],
        ];
        echo '<div class="cs-nav">';
        foreach ( $pages as $slug => $item ) {
            $url    = admin_url( 'admin.php?page=' . $slug );
            $active = ( $current === $slug ) ? 'cs-active' : '';
            echo '<a href="' . esc_url( $url ) . '" class="' . $active . '">' . $item['icon'] . ' ' . $item['label'] . '</a>';
        }
        echo '</div>';
    }

    public static function dashboard() {
        $stats  = CancelSaver_Tracker::get_stats( 30 );
        $plugin = CancelSaver_Compat::get_plugin_name();
        ?>
        <div class="wrap cs-wrap">

            <!-- Header -->
            <div class="cs-page-header">
                <div class="cs-page-header-left">
                    <div class="cs-logo">🛡️</div>
                    <div>
                        <h1>CancelSaver</h1>
                        <div class="cs-version">v<?php echo CANCELSAVER_VERSION; ?> Free</div>
                    </div>
                </div>
                <div class="cs-plugin-badge">
                    <span class="cs-plugin-badge-dot"></span>
                    Connected: <?php echo esc_html( $plugin ); ?>
                </div>
            </div>

            <?php self::nav( 'cancelsaver' ); ?>

            <!-- Stats -->
            <div class="cs-section-title">Last 30 days</div>
            <div class="cs-stats-row">
                <div class="cs-stat cs-green">
                    <span class="cs-stat-icon">✅</span>
                    <div class="cs-stat-num"><?php echo esc_html( $stats['saved'] ); ?></div>
                    <div class="cs-stat-lbl">Cancellations Saved</div>
                </div>
                <div class="cs-stat cs-blue">
                    <span class="cs-stat-icon">💰</span>
                    <div class="cs-stat-num">$<?php echo number_format( $stats['rev_saved'], 0 ); ?></div>
                    <div class="cs-stat-lbl">Est. Revenue Saved</div>
                </div>
                <div class="cs-stat cs-purple">
                    <span class="cs-stat-icon">📈</span>
                    <div class="cs-stat-num"><?php echo esc_html( $stats['save_rate'] ); ?>%</div>
                    <div class="cs-stat-lbl">Save Rate</div>
                </div>
                <div class="cs-stat cs-amber">
                    <span class="cs-stat-icon">👁️</span>
                    <div class="cs-stat-num"><?php echo esc_html( $stats['shown'] ); ?></div>
                    <div class="cs-stat-lbl">Popups Shown</div>
                </div>
            </div>

            <!-- Two column -->
            <div class="cs-two-col">

                <!-- Offer breakdown -->
                <div class="cs-card">
                    <div class="cs-card-head">
                        <h2>🎯 Offer Performance</h2>
                    </div>
                    <div class="cs-card-body">
                        <?php if ( empty( $stats['breakdown'] ) ) : ?>
                            <div class="cs-empty">
                                <span class="cs-empty-icon">📭</span>
                                No offers accepted yet. Your popup is live and tracking — data will appear here once subscribers interact with it.
                            </div>
                        <?php else : ?>
                            <table class="cs-table">
                                <thead>
                                    <tr>
                                        <th>Offer</th>
                                        <th>Accepted</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $stats['breakdown'] as $row ) :
                                        $icons = [ 'pause_1' => '⏸', 'pause_3' => '⏸', 'skip' => '⏭', 'discount' => '🎁' ];
                                        $icon  = $icons[ $row->offer ] ?? '•';
                                    ?>
                                        <tr>
                                            <td><?php echo $icon . ' ' . esc_html( ucfirst( str_replace( '_', ' ', $row->offer ) ) ); ?></td>
                                            <td><span class="cs-pill"><?php echo esc_html( $row->cnt ); ?> times</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tips -->
                <div class="cs-card">
                    <div class="cs-card-head">
                        <h2>💡 Tips to Improve</h2>
                    </div>
                    <div class="cs-card-body">
                        <ul class="cs-tips">
                            <li><span class="cs-tip-icon">🎯</span> Enable all 3 offers — more options = higher save rate</li>
                            <li><span class="cs-tip-icon">💸</span> 20% discount is the sweet spot for most stores</li>
                            <li><span class="cs-tip-icon">📧</span> Win-back emails convert at 5-15% — keep them enabled</li>
                            <li><span class="cs-tip-icon">📊</span> A save rate above 20% is considered excellent</li>
                            <li><span class="cs-tip-icon">✏️</span> Customize your headline to match your brand voice</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Pro upsell -->
            <div class="cs-pro-card">
                <h3>Unlock CancelSaver Pro</h3>
                <div class="cs-pro-features">
                    <span class="cs-pro-feature">📊 Advanced Analytics</span>
                    <span class="cs-pro-feature">🔍 Exit Survey</span>
                    <span class="cs-pro-feature">⚠️ Churn Risk Alerts</span>
                    <span class="cs-pro-feature">🔗 Klaviyo Integration</span>
                    <span class="cs-pro-feature">🎨 Custom Popup Themes</span>
                </div>
                <p>Upgrade to Pro and get deeper insights, churn prediction, and more powerful retention tools.</p>
                <a href="https://cancelsaver.com/pro" target="_blank" class="cs-pro-btn">⭐ Upgrade to Pro — $29/month</a>
            </div>

        </div>
        <?php
    }

    public static function settings() {
        if ( isset( $_GET['settings-updated'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>✅ Settings saved successfully.</p></div>';
        }
        ?>
        <div class="wrap cs-wrap">

            <!-- Header -->
            <div class="cs-page-header">
                <div class="cs-page-header-left">
                    <div class="cs-logo">🛡️</div>
                    <div>
                        <h1>CancelSaver</h1>
                        <div class="cs-version">v<?php echo CANCELSAVER_VERSION; ?> Free</div>
                    </div>
                </div>
            </div>

            <?php self::nav( 'cancelsaver-settings' ); ?>

            <form method="post" action="options.php">
                <?php settings_fields( 'cancelsaver_options' ); ?>

                <!-- General -->
                <div class="cs-settings-section">
                    <div class="cs-settings-head">
                        <span class="cs-head-icon">⚙️</span>
                        <h3>General</h3>
                    </div>
                    <div class="cs-settings-body">
                        <div class="cs-field">
                            <div class="cs-field-label">
                                <strong>Enable CancelSaver</strong>
                                <span>Show popup when subscribers click cancel</span>
                            </div>
                            <div class="cs-field-control">
                                <label class="cs-toggle">
                                    <input type="checkbox" name="cancelsaver_enabled" value="1"
                                        <?php checked( get_option( 'cancelsaver_enabled' ), '1' ); ?>>
                                    <span class="cs-toggle-label">Active</span>
                                </label>
                            </div>
                        </div>
                        <div class="cs-field">
                            <div class="cs-field-label">
                                <strong>Detected Plugin</strong>
                                <span>Auto-detected subscription plugin</span>
                            </div>
                            <div class="cs-field-control">
                                <span class="cs-plugin-badge">
                                    <span class="cs-plugin-badge-dot"></span>
                                    <?php echo esc_html( CancelSaver_Compat::get_plugin_name() ); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Popup text -->
                <div class="cs-settings-section">
                    <div class="cs-settings-head">
                        <span class="cs-head-icon">✏️</span>
                        <h3>Popup Text</h3>
                    </div>
                    <div class="cs-settings-body">
                        <div class="cs-field">
                            <div class="cs-field-label">
                                <strong>Headline</strong>
                                <span>Main title shown in popup</span>
                            </div>
                            <div class="cs-field-control">
                                <input type="text" name="cancelsaver_headline" style="width:100%"
                                    value="<?php echo esc_attr( get_option( 'cancelsaver_headline' ) ); ?>">
                            </div>
                        </div>
                        <div class="cs-field">
                            <div class="cs-field-label">
                                <strong>Subheadline</strong>
                                <span>Supporting message below headline</span>
                            </div>
                            <div class="cs-field-control">
                                <input type="text" name="cancelsaver_subheadline" style="width:100%"
                                    value="<?php echo esc_attr( get_option( 'cancelsaver_subheadline' ) ); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Retention offers -->
                <div class="cs-settings-section">
                    <div class="cs-settings-head">
                        <span class="cs-head-icon">🎯</span>
                        <h3>Retention Offers</h3>
                    </div>
                    <div class="cs-settings-body">
                        <div class="cs-field">
                            <div class="cs-field-label">
                                <strong>⏸ Pause Subscription</strong>
                                <span>Let customers pause for 1 or 3 months</span>
                            </div>
                            <div class="cs-field-control">
                                <label class="cs-toggle">
                                    <input type="checkbox" name="cancelsaver_offer_pause" value="1"
                                        <?php checked( get_option( 'cancelsaver_offer_pause' ), '1' ); ?>>
                                    <span class="cs-toggle-label">Enabled</span>
                                </label>
                            </div>
                        </div>
                        <div class="cs-field">
                            <div class="cs-field-label">
                                <strong>⏭ Skip Next Payment</strong>
                                <span>Skip one billing cycle</span>
                            </div>
                            <div class="cs-field-control">
                                <label class="cs-toggle">
                                    <input type="checkbox" name="cancelsaver_offer_skip" value="1"
                                        <?php checked( get_option( 'cancelsaver_offer_skip' ), '1' ); ?>>
                                    <span class="cs-toggle-label">Enabled</span>
                                </label>
                            </div>
                        </div>
                        <div class="cs-field">
                            <div class="cs-field-label">
                                <strong>🎁 Discount Offer</strong>
                                <span>Offer a discount to stay</span>
                            </div>
                            <div class="cs-field-control">
                                <label class="cs-toggle">
                                    <input type="checkbox" name="cancelsaver_offer_discount" value="1"
                                        <?php checked( get_option( 'cancelsaver_offer_discount' ), '1' ); ?>>
                                    <span class="cs-toggle-label">Enabled</span>
                                </label>
                            </div>
                        </div>
                        <div class="cs-field">
                            <div class="cs-field-label">
                                <strong>Discount Amount</strong>
                                <span>How much to offer</span>
                            </div>
                            <div class="cs-field-control">
                                <div class="cs-inline-fields">
                                    <input type="number" name="cancelsaver_discount_amount" min="1" max="100"
                                        style="width:80px"
                                        value="<?php echo esc_attr( get_option( 'cancelsaver_discount_amount', 20 ) ); ?>">
                                    <select name="cancelsaver_discount_type">
                                        <option value="percent" <?php selected( get_option( 'cancelsaver_discount_type' ), 'percent' ); ?>>% off</option>
                                        <option value="fixed"   <?php selected( get_option( 'cancelsaver_discount_type' ), 'fixed' ); ?>>Fixed amount</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Win-back email -->
                <div class="cs-settings-section">
                    <div class="cs-settings-head">
                        <span class="cs-head-icon">📧</span>
                        <h3>Win-Back Email <span class="cs-badge">New</span></h3>
                    </div>
                    <div class="cs-settings-body">
                        <div class="cs-field">
                            <div class="cs-field-label">
                                <strong>Enable Win-Back</strong>
                                <span>Auto-email subscribers who cancel anyway</span>
                            </div>
                            <div class="cs-field-control">
                                <label class="cs-toggle">
                                    <input type="checkbox" name="cancelsaver_winback_enabled" value="1"
                                        <?php checked( get_option( 'cancelsaver_winback_enabled' ), '1' ); ?>>
                                    <span class="cs-toggle-label">Enabled</span>
                                </label>
                            </div>
                        </div>
                        <div class="cs-field">
                            <div class="cs-field-label">
                                <strong>Email Subject</strong>
                                <span>Subject line for win-back email</span>
                            </div>
                            <div class="cs-field-control">
                                <input type="text" name="cancelsaver_winback_subject" style="width:100%"
                                    value="<?php echo esc_attr( get_option( 'cancelsaver_winback_subject' ) ); ?>">
                            </div>
                        </div>
                        <div class="cs-field">
                            <div class="cs-field-label">
                                <strong>Send Delay</strong>
                                <span>Days after cancellation</span>
                            </div>
                            <div class="cs-field-control">
                                <div class="cs-inline-fields">
                                    <input type="number" name="cancelsaver_winback_delay" min="0" max="30"
                                        style="width:70px"
                                        value="<?php echo esc_attr( get_option( 'cancelsaver_winback_delay', 1 ) ); ?>">
                                    <span style="color:#888; font-size:13px">day(s) after cancellation</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="cs-save-btn">💾 Save Settings</button>

            </form>
        </div>
        <?php
    }
}
