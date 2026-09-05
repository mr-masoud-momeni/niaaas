<?php
defined('ABSPATH') || exit;

class Nias_Login_User_Meta {
    private static $instance;

    public static function get_instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_assets'], 5);
        add_action('wp_footer', [$this, 'render_modal']);
        add_action('wp_ajax_nias-login-meta_save', [$this, 'handle_save']);
    }

    private function is_enabled() {
        $enabled = get_option('nias-force-user-meta');
        return (bool)$enabled;
    }

    private function get_roles_option() {
        $roles = get_option('nias-login-meta_roles');
        if (is_string($roles)) {
            $decoded = json_decode($roles, true);
            if (is_array($decoded)) {
                $roles = $decoded;
            }
        }
        if (!is_array($roles)) {
            $roles = [];
        }
        return array_values(array_filter(array_map('sanitize_text_field', $roles)));
    }

    private function get_fields_option() {
        $fields = get_option('nias-login-meta_fields');
        if (is_string($fields)) {
            $decoded = json_decode($fields, true);
            if (is_array($decoded)) {
                $fields = $decoded;
            }
        }
        if (!is_array($fields)) {
            $fields = [];
        }
        $normalized = [];
        foreach ($fields as $f) {
            if (!is_array($f)) continue;
            $f['required'] = array_key_exists('required', $f) ? (bool) $f['required'] : true;
            $normalized[] = $f;
        }
        return $normalized;
    }

    private function user_matches_roles($user_id) {
        $roles = $this->get_roles_option();
        if (empty($roles)) {
            return true;
        }
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        $user_roles = (array)$user->roles;
        return count(array_intersect($roles, $user_roles)) > 0;
    }

    private function field_value_empty($user_id, $field) {
        $key = isset($field['key']) ? sanitize_key($field['key']) : '';
        if (!$key) {
            return false;
        }
        $value = get_user_meta($user_id, $key, true);
        if (is_array($value)) {
            return empty(array_filter($value, function($v) { return $v !== '' && $v !== null; }));
        }
        return $value === '' || $value === null;
    }

    private function has_missing_required_meta($user_id) {
        $fields = $this->get_fields_option();
        if (empty($fields)) {
            return false;
        }
        foreach ($fields as $f) {
            $required = isset($f['required']) ? (bool)$f['required'] : true;
            if (!$required) {
                continue;
            }
            if ($this->field_value_empty($user_id, $f)) {
                return true;
            }
        }
        return false;
    }

    public function should_show_modal() {
        if (!is_user_logged_in()) {
            return false;
        }
        if (!$this->is_enabled()) {
            return false;
        }
        $user_id = get_current_user_id();
        if (!$this->user_matches_roles($user_id)) {
            return false;
        }
        return $this->has_missing_required_meta($user_id);
    }

    public function maybe_enqueue_assets() {
        if (!$this->should_show_modal()) {
            return;
        }
        wp_enqueue_script('jquery');
        [$um_css_url, $um_css_ver] = nias_login_asset('user-meta/assets/user-meta.css');
        wp_enqueue_style('nias-login-meta-style', $um_css_url, [], $um_css_ver);

        [$um_js_url, $um_js_ver] = nias_login_asset('user-meta/assets/user-meta.js');
        wp_enqueue_script('nias-login-meta-script', $um_js_url, ['jquery'], $um_js_ver, true);
        $data = [
            'fields' => $this->get_fields_option(),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nias-login-meta'),
        ];
        wp_add_inline_script('nias-login-meta-script', 'window.niasLoginMeta=' . wp_json_encode($data) . ';', 'before');
    }

    public function render_modal() {
        if (!$this->should_show_modal()) {
            return;
        }
        // بدون المنتور، قالب المنتوری رندر نمی‌شود و مودال پیش‌فرض جایش را می‌گیرد
        $elementor_design_metamodal = get_option('nias-elementor-meta-shortcode');
        if (nias_elementor_design_active($elementor_design_metamodal)) {
            ?>

                <?php // شناسه‌ی قالب یا شورت‌کد — بدون نیاز به المنتور پرو
                echo nias_elementor_render_template($elementor_design_metamodal); ?>

             <?php
        }else{
        $fields = $this->get_fields_option();
        echo '<div class="nias-login-meta-overlay"><div class="nias-login-meta-modal"><h3>تکمیل اطلاعات کاربری</h3><form class="nias-login-meta-form">';
        foreach ($fields as $f) {
            $key = isset($f['key']) ? sanitize_key($f['key']) : '';
            $label = isset($f['label']) ? esc_html($f['label']) : esc_html($key);
            $type = isset($f['type']) ? sanitize_text_field($f['type']) : 'text';
            $required = isset($f['required']) ? (bool)$f['required'] : true;
            $value = get_user_meta(get_current_user_id(), $key, true);
            $req_attr = $required ? 'required' : '';
            echo '<div class="nias-login-meta-field">';
            echo '<label>' . $label . '</label>';
            if ($type === 'select') {
                $options = isset($f['options']) && is_array($f['options']) ? $f['options'] : [];
                echo '<select name="' . esc_attr($key) . '" ' . $req_attr . '>';
                echo '<option value=""></option>';
                foreach ($options as $opt) {
                    $opt = is_array($opt) ? ($opt['value'] ?? '') : $opt;
                    $selected = ($value !== '' && $value == $opt) ? 'selected' : '';
                    echo '<option value="' . esc_attr($opt) . '" ' . $selected . '>' . esc_html($opt) . '</option>';
                }
                echo '</select>';
            } elseif ($type === 'textarea') {
                echo '<textarea name="' . esc_attr($key) . '" ' . $req_attr . '>' . esc_textarea($value) . '</textarea>';
            } else {
                $allowed_types = ['text', 'number', 'email', 'tel', 'date', 'url'];
                $safe_type = in_array($type, $allowed_types, true) ? $type : 'text';
                echo '<input type="' . $safe_type . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" ' . $req_attr . ' />';
            }
            echo '</div>';
        }
        echo '<button type="submit" class="nias-login-meta-submit">ذخیره</button>';
        echo '</form></div></div>';
                }
    }

    public function handle_save() {
        // flush any stray output (PHP notices/warnings) so headers can still be set
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'عدم دسترسی'], 403);
        }
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'nias-login-meta')) {
            wp_send_json_error(['message' => 'غیرمجاز'], 403);
        }
        if (!$this->is_enabled()) {
            wp_send_json_error(['message' => 'غیرفعال'], 400);
        }
        $user_id = get_current_user_id();
        $fields = $this->get_fields_option();
        $errors = [];
        foreach ($fields as $f) {
            $key = isset($f['key']) ? sanitize_key($f['key']) : '';
            if (!$key) {
                continue;
            }
            $type = isset($f['type']) ? sanitize_text_field($f['type']) : 'text';
            $required = isset($f['required']) ? (bool)$f['required'] : true;
            $raw = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : '';
            $val = '';
            if ($type === 'number') {
                $val = is_numeric($raw) ? $raw : '';
            } elseif ($type === 'email') {
                $sanitized = sanitize_email($raw);
                $val = is_email($sanitized) ? $sanitized : '';
            } elseif ($type === 'textarea') {
                $val = is_string($raw) ? sanitize_textarea_field($raw) : '';
            } elseif ($type === 'select') {
                $allowed_opts = isset($f['options']) && is_array($f['options'])
                    ? array_map('strval', $f['options']) : [];
                $sanitized = sanitize_text_field($raw);
                $val = in_array($sanitized, $allowed_opts, true) ? $sanitized : '';
            } else {
                $val = is_string($raw) ? sanitize_text_field($raw) : '';
            }
            if ($required && ($val === '' || $val === null)) {
                $errors[] = $key;
                continue;
            }
            if ($val !== '' && $val !== null) {
                update_user_meta($user_id, $key, $val);

                // نام/نام‌خانوادگی صورتحساب باید در فیلدهای هم‌معنی پروفایل و حمل‌ونقل هم ذخیره شود
                $mirror_map = [
                    'billing_first_name' => ['first_name', 'shipping_first_name'],
                    'billing_last_name'  => ['last_name', 'shipping_last_name'],
                ];
                if (isset($mirror_map[$key])) {
                    foreach ($mirror_map[$key] as $mirror_key) {
                        update_user_meta($user_id, $mirror_key, $val);
                    }
                }
            }
        }
        if (!empty($errors)) {
            wp_send_json_error(['message' => 'لطفاً فیلدهای ضروری را تکمیل کنید', 'fields' => $errors], 400);
        }

        // آپدیت نام نمایشی بر اساس نام و نام خانوادگی ذخیره‌شده
        nias_maybe_update_display_name($user_id);

        wp_send_json_success(['message' => 'ذخیره شد', 'reload' => true]);
    }
}

Nias_Login_User_Meta::get_instance();

/* -------------------------------------------------------------------------- */
/*                    آپدیت نام نمایشی هنگام ذخیره نام/نام خانوادگی            */
/* -------------------------------------------------------------------------- */

/**
 * آپدیت display_name کاربر بر اساس first_name و last_name فعلی
 *
 * @param int $user_id
 */
function nias_maybe_update_display_name($user_id)
{
    $first = trim((string) get_user_meta($user_id, 'first_name', true));
    $last  = trim((string) get_user_meta($user_id, 'last_name', true));

    // اگر هیچ‌کدام پر نشده، کاری نکن
    if ($first === '' && $last === '') {
        return;
    }

    $display_name = trim($first . ' ' . $last);

    // فقط اگر با مقدار فعلی فرق داشت آپدیت کن
    $user = get_userdata($user_id);
    if ($user && $user->display_name !== $display_name) {
        // از remove_action استفاده می‌کنیم تا loop بی‌نهایت نشه
        remove_action('updated_user_meta', 'nias_on_name_meta_updated', 10);
        wp_update_user(['ID' => $user_id, 'display_name' => $display_name]);
        add_action('updated_user_meta', 'nias_on_name_meta_updated', 10, 4);
    }
}

/**
 * Hook روی updated_user_meta — هر بار first_name یا last_name تغییر کرد
 *
 * @param int    $meta_id
 * @param int    $user_id
 * @param string $meta_key
 * @param mixed  $meta_value
 */
function nias_on_name_meta_updated($meta_id, $user_id, $meta_key, $meta_value)
{
    if (!in_array($meta_key, ['first_name', 'last_name'], true)) {
        return;
    }
    nias_maybe_update_display_name($user_id);
}
add_action('updated_user_meta', 'nias_on_name_meta_updated', 10, 4);

/**
 * Hook روی added_user_meta — برای اولین باری که متا ست می‌شه
 */
function nias_on_name_meta_added($meta_id, $user_id, $meta_key, $meta_value)
{
    if (!in_array($meta_key, ['first_name', 'last_name'], true)) {
        return;
    }
    nias_maybe_update_display_name($user_id);
}
add_action('added_user_meta', 'nias_on_name_meta_added', 10, 4);
