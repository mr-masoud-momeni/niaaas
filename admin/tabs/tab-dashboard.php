<?php
defined('ABSPATH') || exit;

global $wpdb;

$tbl_login    = $wpdb->prefix . 'nias_sms_login';
$tbl_blocked  = $wpdb->prefix . 'nias_blockedip_sms';
$tbl_security = $wpdb->prefix . 'nias_security_log';

$tbl_login_exists    = (bool) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tbl_login));
$tbl_blocked_exists  = (bool) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tbl_blocked));
$tbl_security_exists = (bool) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tbl_security));

/* ------------------------------------------------------------------ */
/*  بازه گزارش — بر اساس تنظیم پاکسازی                                */
/* ------------------------------------------------------------------ */
$cleanup_days   = (int) get_option('nias_login_log_cleanup_days', 30);
// اگر 0 باشد یعنی پاکسازی کامل — بازه گزارش را 1 روز در نظر می‌گیریم
$report_days    = $cleanup_days > 0 ? $cleanup_days : 1;
// بازه واقعی نمودار: حداکثر 30 روز (اگر داده بیشتری هست)
$chart_days     = min($report_days, 30);

/* ------------------------------------------------------------------ */
/*  کارت‌های خلاصه                                                     */
/* ------------------------------------------------------------------ */
$total_logins    = $tbl_login_exists ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tbl_login}") : 0;
$verified_logins = $tbl_login_exists ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tbl_login} WHERE verified = 1") : 0;
$new_users       = $tbl_login_exists ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tbl_login} WHERE user_status = 'new' AND verified = 1") : 0;
$blocked_ips     = $tbl_blocked_exists ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tbl_blocked}") : 0;
$today_logins    = $tbl_login_exists ? (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tbl_login} WHERE DATE(created_at) = %s", current_time('Y-m-d')
)) : 0;
$success_rate    = $total_logins > 0 ? round(($verified_logins / $total_logins) * 100) : 0;

/* ------------------------------------------------------------------ */
/*  نمودار روزانه — محدود به بازه گزارش                                */
/* ------------------------------------------------------------------ */
$daily_rows = $tbl_login_exists ? $wpdb->get_results(
    $wpdb->prepare(
        "SELECT DATE(created_at) AS day,
                COUNT(*) AS total,
                SUM(verified) AS success
         FROM {$tbl_login}
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
         GROUP BY DATE(created_at)
         ORDER BY day ASC",
        $chart_days
    )
) : [];

$chart_labels  = [];
$chart_total   = [];
$chart_success = [];

$date_map = [];
foreach ($daily_rows as $row) {
    $date_map[$row->day] = $row;
}
for ($i = $chart_days - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days", strtotime(current_time('Y-m-d'))));
    $chart_labels[]  = date('m/d', strtotime($d));
    $chart_total[]   = isset($date_map[$d]) ? (int) $date_map[$d]->total   : 0;
    $chart_success[] = isset($date_map[$d]) ? (int) $date_map[$d]->success : 0;
}

/* ------------------------------------------------------------------ */
/*  نمودار دونات — کاربر جدید vs موجود                                 */
/* ------------------------------------------------------------------ */
$existing_users = $tbl_login_exists ? (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$tbl_login} WHERE user_status = 'existing' AND verified = 1"
) : 0;

/* ------------------------------------------------------------------ */
/*  نمودار میله‌ای — ساعت‌های پرترافیک (۷ روز اخیر یا کمتر)           */
/* ------------------------------------------------------------------ */
$hourly_interval = min(7, $report_days);
$hourly_rows = $tbl_login_exists ? $wpdb->get_results(
    $wpdb->prepare(
        "SELECT HOUR(created_at) AS hr, COUNT(*) AS cnt
         FROM {$tbl_login}
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
         GROUP BY HOUR(created_at)
         ORDER BY hr ASC",
        $hourly_interval
    )
) : [];
$hourly_data = array_fill(0, 24, 0);
foreach ($hourly_rows as $row) {
    $hourly_data[(int)$row->hr] = (int)$row->cnt;
}

/* ------------------------------------------------------------------ */
/*  آخرین رویدادهای امنیتی                                             */
/* ------------------------------------------------------------------ */
$security_events = $tbl_security_exists ? $wpdb->get_results(
    "SELECT event_type, identifier, ip_address, severity, created_at
     FROM {$tbl_security}
     ORDER BY created_at DESC
     LIMIT 8"
) : [];

/* ------------------------------------------------------------------ */
/*  آخرین ورودی‌های تأیید شده                                          */
/* ------------------------------------------------------------------ */
$recent_logins = $tbl_login_exists ? $wpdb->get_results(
    "SELECT phone, email, user_status, created_at, ip
     FROM {$tbl_login}
     WHERE verified = 1
     ORDER BY created_at DESC
     LIMIT 6"
) : [];

/* ------------------------------------------------------------------ */
/*  متن اطلاعیه بازه گزارش                                             */
/* ------------------------------------------------------------------ */
if ($cleanup_days === 0) {
    $notice_type = 'warning';
    $notice_text = sprintf(
        esc_html__('پاکسازی لاگ روی %s تنظیم شده — نمودارها فقط داده‌های موجود را نشان می‌دهند. برای گزارش‌گیری بهتر، مدت نگهداری را از تب امنیت تنظیم کنید.', 'nias-login-signup'),
        '<strong>' . esc_html__('حذف کامل', 'nias-login-signup') . '</strong>'
    );
} elseif ($cleanup_days < 7) {
    $notice_type = 'warning';
    $notice_text = sprintf(
        esc_html__('مدت نگهداری لاگ روی %1$s تنظیم شده — نمودارها حداکثر %2$d روز گذشته را نشان می‌دهند.', 'nias-login-signup'),
        '<strong>' . sprintf(esc_html__('%d روز', 'nias-login-signup'), $cleanup_days) . '</strong>',
        $cleanup_days
    );
} elseif ($cleanup_days < 30) {
    $notice_type = 'info';
    $notice_text = sprintf(
        esc_html__('مدت نگهداری لاگ روی %1$s تنظیم شده — نمودارها %2$d روز گذشته را نشان می‌دهند.', 'nias-login-signup'),
        '<strong>' . sprintf(esc_html__('%d روز', 'nias-login-signup'), $cleanup_days) . '</strong>',
        $cleanup_days
    );
} else {
    $notice_type = 'success';
    $notice_text = sprintf(
        esc_html__('مدت نگهداری لاگ روی %s تنظیم شده — نمودارها ۳۰ روز گذشته را نشان می‌دهند.', 'nias-login-signup'),
        '<strong>' . sprintf(esc_html__('%d روز', 'nias-login-signup'), $cleanup_days) . '</strong>'
    );
}
?>

<section id="nsdashboard" class="nias-login-panel active nias-login-tabcontent" data-tab="nsdashboard">

    <div class="nias-login-page-head">
        <div class="nias-login-page-head__icon"><svg viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.8"/><rect x="14" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.8"/><rect x="3" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.8"/><rect x="14" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.8"/></svg></div>
        <div><h1 class="nias-login-page-head__title"><?php esc_html_e('پیشخوان', 'nias-login-signup'); ?></h1><p class="nias-login-page-head__desc"><?php esc_html_e('نمای کلی از ورودها، کاربران و رویدادهای امنیتی.', 'nias-login-signup'); ?></p></div>
    </div>

    <!-- ---------------------------------------------------------------- -->
    <!--  اطلاعیه بازه گزارش                                              -->
    <!-- ---------------------------------------------------------------- -->
    <div class="nias-dash-notice nias-dash-notice--<?php echo $notice_type; ?>">
        <svg viewBox="0 0 24 24" fill="none" width="18" height="18" style="flex-shrink:0">
            <?php if ($notice_type === 'warning'): ?>
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <line x1="12" y1="9" x2="12" y2="13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <line x1="12" y1="17" x2="12.01" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <?php elseif ($notice_type === 'success'): ?>
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <polyline points="22 4 12 14.01 9 11.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <?php else: ?>
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                <line x1="12" y1="8" x2="12" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <line x1="12" y1="16" x2="12.01" y2="16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <?php endif; ?>
        </svg>
        <span><?php echo $notice_text; ?></span>
        <a href="?page=niasloginsignup" onclick="event.preventDefault(); niasSetTab('nssecurity');"
           style="margin-right:auto;font-size:12px;opacity:0.7;text-decoration:underline;cursor:pointer;white-space:nowrap;">
            <?php esc_html_e('تغییر تنظیمات', 'nias-login-signup'); ?>
        </a>
    </div>

    <!-- ---------------------------------------------------------------- -->
    <!--  کارت‌های خلاصه                                                  -->
    <!-- ---------------------------------------------------------------- -->
    <div class="nias-dash-cards">

        <div class="nias-dash-card nias-dash-card--blue">
            <div class="nias-dash-card__icon">
                <svg viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </div>
            <div class="nias-dash-card__body">
                <span class="nias-dash-card__value"><?php echo number_format($total_logins); ?></span>
                <span class="nias-dash-card__label"><?php esc_html_e('کل درخواست‌ها', 'nias-login-signup'); ?></span>
            </div>
        </div>

        <div class="nias-dash-card nias-dash-card--green">
            <div class="nias-dash-card__icon">
                <svg viewBox="0 0 24 24" fill="none"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><polyline points="22 4 12 14.01 9 11.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="nias-dash-card__body">
                <span class="nias-dash-card__value"><?php echo number_format($verified_logins); ?></span>
                <span class="nias-dash-card__label"><?php esc_html_e('ورود موفق', 'nias-login-signup'); ?></span>
            </div>
            <div class="nias-dash-card__badge"><?php echo $success_rate; ?>%</div>
        </div>

        <div class="nias-dash-card nias-dash-card--purple">
            <div class="nias-dash-card__icon">
                <svg viewBox="0 0 24 24" fill="none"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/><line x1="19" y1="8" x2="19" y2="14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="22" y1="11" x2="16" y2="11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </div>
            <div class="nias-dash-card__body">
                <span class="nias-dash-card__value"><?php echo number_format($new_users); ?></span>
                <span class="nias-dash-card__label"><?php esc_html_e('کاربر جدید', 'nias-login-signup'); ?></span>
            </div>
        </div>

        <div class="nias-dash-card nias-dash-card--orange">
            <div class="nias-dash-card__icon">
                <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><line x1="12" y1="8" x2="12" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="12" y1="16" x2="12.01" y2="16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </div>
            <div class="nias-dash-card__body">
                <span class="nias-dash-card__value"><?php echo number_format($today_logins); ?></span>
                <span class="nias-dash-card__label"><?php esc_html_e('امروز', 'nias-login-signup'); ?></span>
            </div>
        </div>

        <div class="nias-dash-card nias-dash-card--red">
            <div class="nias-dash-card__icon">
                <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </div>
            <div class="nias-dash-card__body">
                <span class="nias-dash-card__value"><?php echo number_format($blocked_ips); ?></span>
                <span class="nias-dash-card__label"><?php esc_html_e('IP مسدود', 'nias-login-signup'); ?></span>
            </div>
        </div>

    </div>

    <!-- ---------------------------------------------------------------- -->
    <!--  نمودارها — ردیف اول                                             -->
    <!-- ---------------------------------------------------------------- -->
    <div class="nias-dash-row">

        <!-- نمودار خطی ۳۰ روز -->
        <div class="nias-dash-chart-box nias-dash-chart-box--wide">
            <div class="nias-dash-chart-box__header">
                <span class="nias-dash-chart-box__title"><?php printf(esc_html__('ورودی‌های %d روز اخیر', 'nias-login-signup'), (int) $chart_days); ?></span>
                <div class="nias-dash-legend">
                    <span class="nias-dash-legend__dot" style="background:#4f8ef7"></span><span><?php esc_html_e('کل', 'nias-login-signup'); ?></span>
                    <span class="nias-dash-legend__dot" style="background:#34d399"></span><span><?php esc_html_e('موفق', 'nias-login-signup'); ?></span>
                </div>
            </div>
            <canvas id="nias-chart-daily" height="90"></canvas>
        </div>

        <!-- نمودار دونات -->
        <div class="nias-dash-chart-box nias-dash-chart-box--narrow">
            <div class="nias-dash-chart-box__header">
                <span class="nias-dash-chart-box__title"><?php esc_html_e('نوع کاربران', 'nias-login-signup'); ?></span>
            </div>
            <div class="nias-dash-donut-wrap">
                <canvas id="nias-chart-donut" height="180"></canvas>
                <div class="nias-dash-donut-center">
                    <span><?php echo number_format($new_users + $existing_users); ?></span>
                    <small><?php esc_html_e('ورود تأیید شده', 'nias-login-signup'); ?></small>
                </div>
            </div>
            <div class="nias-dash-legend" style="justify-content:center;margin-top:12px;">
                <span class="nias-dash-legend__dot" style="background:#818cf8"></span><span><?php esc_html_e('کاربر جدید', 'nias-login-signup'); ?></span>
                <span class="nias-dash-legend__dot" style="background:#34d399"></span><span><?php esc_html_e('کاربر موجود', 'nias-login-signup'); ?></span>
            </div>
        </div>

    </div>

    <!-- ---------------------------------------------------------------- -->
    <!--  نمودارها — ردیف دوم                                             -->
    <!-- ---------------------------------------------------------------- -->
    <div class="nias-dash-row">

        <!-- نمودار میله‌ای ساعتی -->
        <div class="nias-dash-chart-box nias-dash-chart-box--half">
            <div class="nias-dash-chart-box__header">
                <span class="nias-dash-chart-box__title"><?php printf(esc_html__('ساعت‌های پرترافیک (%d روز اخیر)', 'nias-login-signup'), (int) $hourly_interval); ?></span>
            </div>
            <canvas id="nias-chart-hourly" height="120"></canvas>
        </div>

        <!-- آخرین ورودی‌ها -->
        <div class="nias-dash-chart-box nias-dash-chart-box--half">
            <div class="nias-dash-chart-box__header">
                <span class="nias-dash-chart-box__title"><?php esc_html_e('آخرین ورودی‌های موفق', 'nias-login-signup'); ?></span>
            </div>
            <div class="nias-dash-table-wrap">
                <?php if (empty($recent_logins)): ?>
                    <p style="color:#555;text-align:center;padding:20px 0;"><?php esc_html_e('داده‌ای موجود نیست', 'nias-login-signup'); ?></p>
                <?php else: ?>
                <table class="nias-dash-table">
                    <thead><tr><th><?php esc_html_e('کاربر', 'nias-login-signup'); ?></th><th><?php esc_html_e('نوع', 'nias-login-signup'); ?></th><th>IP</th><th><?php esc_html_e('زمان', 'nias-login-signup'); ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($recent_logins as $row): ?>
                        <tr>
                            <td><?php echo esc_html($row->phone ?: $row->email ?: '—'); ?></td>
                            <td>
                                <span class="nias-dash-badge nias-dash-badge--<?php echo $row->user_status === 'new' ? 'purple' : 'green'; ?>">
                                    <?php echo $row->user_status === 'new' ? esc_html__('جدید', 'nias-login-signup') : esc_html__('موجود', 'nias-login-signup'); ?>
                                </span>
                            </td>
                            <td dir="ltr"><?php echo esc_html($row->ip); ?></td>
                            <td><?php echo esc_html(date_i18n('H:i - m/d', strtotime($row->created_at))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- ---------------------------------------------------------------- -->
    <!--  رویدادهای امنیتی                                                -->
    <!-- ---------------------------------------------------------------- -->
    <?php if (!empty($security_events)): ?>
    <div class="nias-dash-row">
        <div class="nias-dash-chart-box" style="width:100%;">
            <div class="nias-dash-chart-box__header">
                <span class="nias-dash-chart-box__title"><?php esc_html_e('آخرین رویدادهای امنیتی', 'nias-login-signup'); ?></span>
            </div>
            <div class="nias-dash-table-wrap">
                <table class="nias-dash-table">
                    <thead><tr><th><?php esc_html_e('نوع رویداد', 'nias-login-signup'); ?></th><th><?php esc_html_e('شناسه', 'nias-login-signup'); ?></th><th>IP</th><th><?php esc_html_e('شدت', 'nias-login-signup'); ?></th><th><?php esc_html_e('زمان', 'nias-login-signup'); ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($security_events as $ev): ?>
                        <tr>
                            <td><?php echo esc_html($ev->event_type); ?></td>
                            <td><?php echo esc_html($ev->identifier ?: '—'); ?></td>
                            <td dir="ltr"><?php echo esc_html($ev->ip_address); ?></td>
                            <td>
                                <span class="nias-dash-badge nias-dash-badge--<?php
                                    switch ($ev->severity) {
                                        case 'critical':
                                        case 'high':
                                            echo 'red';
                                            break;
                                        case 'warning':
                                        case 'medium':
                                            echo 'orange';
                                            break;
                                        default:
                                            echo 'blue';
                                    }
                                ?>">
                                    <?php echo esc_html($ev->severity); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(date_i18n('H:i - m/d', strtotime($ev->created_at))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

</section><!-- /#nsdashboard -->

<!-- ---------------------------------------------------------------- -->
<!--  Chart.js + رندر نمودارها                                        -->
<!-- ---------------------------------------------------------------- -->
<script>
(function() {
    // داده‌های PHP → JS
    var dailyLabels  = <?php echo json_encode($chart_labels); ?>;
    var dailyTotal   = <?php echo json_encode($chart_total); ?>;
    var dailySuccess = <?php echo json_encode($chart_success); ?>;
    var hourlyData   = <?php echo json_encode(array_values($hourly_data)); ?>;
    var donutData    = [<?php echo (int)$new_users; ?>, <?php echo (int)$existing_users; ?>];

    // بارگذاری Chart.js از CDN فقط یک بار
    function loadChartJs(cb) {
        if (window.Chart) { cb(); return; }
        var s = document.createElement('script');
       s.src = '<?php echo plugins_url("chart.umd.min.js", dirname(__FILE__)); ?>';
        s.onload = cb;
        document.head.appendChild(s);
    }

    function initCharts() {
        if (window.__niasDashInited) { return; }
        window.__niasDashInited = true;
        Chart.defaults.color = '#888';
        Chart.defaults.font.family = 'Vazirmatn, Tahoma, sans-serif';

        var gridColor  = 'rgba(255,255,255,0.06)';
        var tooltipBg  = '#1e2235';

        // ---- نمودار خطی روزانه ----
        var ctxDaily = document.getElementById('nias-chart-daily');
        if (ctxDaily) {
            new Chart(ctxDaily, {
                type: 'line',
                data: {
                    labels: dailyLabels,
                    datasets: [
                        {
                            label: 'کل درخواست',
                            data: dailyTotal,
                            borderColor: '#4f8ef7',
                            backgroundColor: 'rgba(79,142,247,0.12)',
                            borderWidth: 2,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            fill: true,
                            tension: 0.4,
                        },
                        {
                            label: 'ورود موفق',
                            data: dailySuccess,
                            borderColor: '#34d399',
                            backgroundColor: 'rgba(52,211,153,0.10)',
                            borderWidth: 2,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            fill: true,
                            tension: 0.4,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: tooltipBg,
                            borderColor: 'rgba(255,255,255,0.1)',
                            borderWidth: 1,
                            padding: 10,
                        }
                    },
                    scales: {
                        x: { grid: { color: gridColor }, ticks: { maxTicksLimit: 10 } },
                        y: { grid: { color: gridColor }, beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            });
        }

        // ---- نمودار دونات ----
        var ctxDonut = document.getElementById('nias-chart-donut');
        if (ctxDonut) {
            new Chart(ctxDonut, {
                type: 'doughnut',
                data: {
                    labels: ['کاربر جدید', 'کاربر موجود'],
                    datasets: [{
                        data: donutData,
                        backgroundColor: ['#818cf8', '#34d399'],
                        borderColor: '#12152a',
                        borderWidth: 3,
                        hoverOffset: 6,
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '72%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: tooltipBg,
                            borderColor: 'rgba(255,255,255,0.1)',
                            borderWidth: 1,
                            padding: 10,
                        }
                    }
                }
            });
        }

        // ---- نمودار میله‌ای ساعتی ----
        var ctxHourly = document.getElementById('nias-chart-hourly');
        if (ctxHourly) {
            var hourLabels = [];
            for (var h = 0; h < 24; h++) { hourLabels.push(h + ':00'); }

            new Chart(ctxHourly, {
                type: 'bar',
                data: {
                    labels: hourLabels,
                    datasets: [{
                        label: 'تعداد درخواست',
                        data: hourlyData,
                        backgroundColor: 'rgba(79,142,247,0.7)',
                        borderColor: '#4f8ef7',
                        borderWidth: 1,
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: tooltipBg,
                            borderColor: 'rgba(255,255,255,0.1)',
                            borderWidth: 1,
                            padding: 10,
                        }
                    },
                    scales: {
                        x: { grid: { color: gridColor } },
                        y: { grid: { color: gridColor }, beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            });
        }
    }

    // وقتی تب داشبورد باز می‌شود نمودارها را رندر کن
    document.addEventListener('click', function(e) {
        var dashBtn = e.target.closest('[onclick*="nsdashboard"]');
        if (dashBtn) {
            setTimeout(function() { loadChartJs(initCharts); }, 80);
        }
    });

    // اگر تب از قبل باز بود
    if (document.getElementById('nsdashboard') &&
        document.getElementById('nsdashboard').style.display !== 'none') {
        loadChartJs(initCharts);
    }
})();
</script>
