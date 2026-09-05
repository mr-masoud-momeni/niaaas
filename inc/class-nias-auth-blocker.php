<?php
defined('ABSPATH') || exit;

/**
 * Nias Auth Blocker
 *
 * When nias_replace_wp_login is enabled, every request to wp-login.php
 * (except logout / postpass / confirmaction) is intercepted at login_init
 * and replaced with a standalone page that renders the Nias login form.
 * No redirect to homepage happens — the form lives at wp-login.php itself.
 *
 * Unauthenticated wp-admin requests are also redirected to wp-login.php
 * (which now carries the Nias form) so the native ?redirect_to flow works.
 */
class Nias_Auth_Blocker {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if ( ! get_option( 'nias_replace_wp_login', 0 ) ) {
            return;
        }

        // Render the Nias form on wp-login.php before WP outputs anything.
        // login_init fires after WP is bootstrapped (so nonces, options, and
        // login_header() / login_footer() are all available) but before any
        // HTML is printed.  Logged-in users are already redirected to admin
        // by wp-login.php itself before login_init, so we don't need to check.
        add_action( 'login_init', [ $this, 'render_nias_on_wp_login' ] );

        // Keep extra guard for wp-admin — WP's own auth_redirect() handles
        // most cases but some admin files may not call it.
        add_action( 'init', [ $this, 'block_unauthenticated_wp_admin' ], 1 );

        // Priority 5 fires before functions.php wp_logout hook (priority 10)
        add_action( 'wp_logout', [ $this, 'post_logout_redirect' ], 5 );
    }

    // ─── Nias form on wp-login.php ───────────────────────────────────────────────

    /**
     * Intercept the wp-login.php page at login_init (before HTML output) and
     * replace it with a standalone page that shows the Nias login form.
     *
     * login_header() / login_footer() are both available here.
     */
    public function render_nias_on_wp_login() {
        $action = isset( $_REQUEST['action'] ) ? sanitize_key( $_REQUEST['action'] ) : 'login';

        // Let WP handle these actions unchanged.
        if ( in_array( $action, [ 'logout', 'postpass', 'confirmaction' ], true ) ) {
            return;
        }

        $password_activate     = get_option( 'nias_password_activate', false );
        $password_otp_activate = get_option( 'nias_password_otp_activate', false );

        $nias_config = wp_json_encode( [
            'lockedPages'           => [],
            'clickLinks'            => [],
            'countdown_duration'    => (int) get_option( 'nias_countdown_duration', 120 ),
            'password_only_mode'    => (bool) ( $password_activate && ! $password_otp_activate ),
            'password_otp_activate' => (bool) $password_otp_activate,
            'ajax_url'              => esc_url( home_url( '/nias-login' ) ),
            'site_url'              => esc_url( home_url() ),
        ] );

        // این صفحه مستقل است و از wp_enqueue استفاده نمی‌کند، اما باید همان تاگل‌های
        // بخش «بهینه‌سازی» را رعایت کند
        [ $css_src,   $css_ver   ] = nias_login_asset( 'assets/css/style.css' );
        [ $js_src,    $js_ver    ] = nias_login_asset( 'assets/js/script.js' );
        [ $toast_src, $toast_ver ] = nias_login_asset( 'assets/js/nias-toast.js' );

        $css_url    = esc_url( add_query_arg( 'v', $css_ver,   $css_src   ) );
        $js_url     = esc_url( add_query_arg( 'v', $js_ver,    $js_src    ) );
        $toast_url  = esc_url( add_query_arg( 'v', $toast_ver, $toast_src ) );
        $jquery_url = esc_url( includes_url( 'js/jquery/jquery.min.js' ) );
        $site_title = get_bloginfo( 'name' );
        $home_url   = esc_url( home_url( '/' ) );

        nocache_headers();
        header( 'Content-Type: text/html; charset=utf-8' );

        ?><!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html( $site_title ); ?> &mdash; ورود</title>
<link rel="stylesheet" href="<?php echo $css_url; ?>">
<style>
/* ── Vazirmatn font ── */
@font-face {
    font-family: "Vazirmatn";
    src: url("<?php echo esc_url( NIAS_LOGIN_URL . 'assets/font/vazirmatn.woff2' ); ?>") format("woff2");
    font-weight: 100 900;
    font-style: normal;
    font-display: swap;
}

/* ── Page layout ── */
*, *::before, *::after { box-sizing: border-box; }
html, body { height: 100%; margin: 0; padding: 0; }
body {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #f0f0f1;
    font-family: "Vazirmatn", -apple-system, "Segoe UI", Tahoma, Arial, sans-serif;
    direction: rtl;
}
.nias-wplogin-wrap {
    width: 100%;
    max-width: 420px;
    padding: 16px;
}

/* ── Strip the full-screen overlay behaviour from .nias-modal-box ── */
/* These must come AFTER style.css to win specificity */
.nias-modal-box {
    position: static !important;
    height: auto !important;
    width: 100% !important;
    opacity: 1 !important;
    visibility: visible !important;
    background-color: transparent !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    z-index: auto !important;
    display: block !important;
    transition: none !important;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(0,0,0,.12);
}

/* .nias-main-modal normally starts at scale(0) and needs .open to animate in */
.nias-modal-box .nias-main-modal {
    transform: scale(1) !important;
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
}

/* Hide the close button — no overlay to dismiss */
.nias-close-modal { display: none !important; }

/* Back-to-site link */
.nias-wplogin-sitelink {
    display: block;
    text-align: center;
    margin-top: 14px;
    color: #646970;
    text-decoration: none;
    font-size: 13px;
}
.nias-wplogin-sitelink:hover { color: #135e96; }
</style>
</head>
<body class="login wp-core-ui">
<div class="nias-wplogin-wrap">
<?php include NIAS_LOGIN_VIEW . 'nias_login_modal.php'; ?>
    <a class="nias-wplogin-sitelink" href="<?php echo $home_url; ?>">&larr; <?php echo esc_html( $site_title ); ?></a>
</div>
<script src="<?php echo $jquery_url; ?>"></script>
<script>window.nias = <?php echo $nias_config; ?>;</script>
<script src="<?php echo $toast_url; ?>"></script>
<script src="<?php echo $js_url; ?>"></script>
</body>
</html><?php
        exit;
    }

    // ─── wp-admin guard ──────────────────────────────────────────────────────────

    /**
     * Block unauthenticated access to wp-admin.
     * AJAX, cron, REST, and CLI requests are excluded.
     * Redirects to wp-login.php (which now shows the Nias form), preserving
     * the originally-requested URL as redirect_to so the JS can use it after
     * a successful login.
     */
    public function block_unauthenticated_wp_admin() {
        if ( ! is_admin() ) {
            return;
        }

        if ( $this->is_non_interactive_request() ) {
            return;
        }

        if ( is_user_logged_in() ) {
            return;
        }

        $current = $this->current_request_url();
        $target  = empty( $current ) ? wp_login_url() : wp_login_url( $current );
        wp_safe_redirect( $target, 302 );
        exit;
    }

    // ─── Logout ──────────────────────────────────────────────────────────────────

    /**
     * After WP processes the logout, redirect to the homepage.
     * Returns early (without redirecting) when a custom logout URL is
     * configured so that the functions.php hook (priority 10) can handle it.
     */
    public function post_logout_redirect() {
        if ( ! empty( get_option( 'nias_logout_links' ) ) ) {
            return;
        }
        wp_safe_redirect( home_url( '/' ) );
        exit;
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────────

    /**
     * Build and validate the full URL of the current request so it can be
     * used as a post-login redirect destination.
     */
    private function current_request_url() {
        $uri    = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
        $host   = isset( $_SERVER['HTTP_HOST'] )   ? wp_unslash( $_SERVER['HTTP_HOST'] )   : '';
        if ( ! $host ) {
            return '';
        }
        $scheme = is_ssl() ? 'https' : 'http';
        return wp_validate_redirect( $scheme . '://' . $host . $uri, '' );
    }

    /**
     * Returns true for non-interactive or headless requests that should
     * never be blocked by the auth wall.
     */
    private function is_non_interactive_request() {
        return ( defined( 'DOING_AJAX' )   && DOING_AJAX   )
            || ( defined( 'DOING_CRON' )   && DOING_CRON   )
            || ( defined( 'WP_CLI' )       && WP_CLI       )
            || ( defined( 'REST_REQUEST' ) && REST_REQUEST );
    }
}
