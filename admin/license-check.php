<?php
defined('ABSPATH') || exit;

function nias_license_is_valid(): bool
{
    $key    = get_option('nlmw_nias-login-plugin_license_key', '');
    $status = get_option('nlmw_nias-login-plugin_license_status', 'inactive');
    return !empty($key) && $status === 'active';
}

function nias_render_license_notice(): void
{
    ?>
    <div style="max-width:900px;margin:30px auto;padding:24px;border-radius:18px;
background:linear-gradient(135deg,#0f172a,#111827 60%,#1e293b);
box-shadow:0 15px 40px rgba(0,0,0,0.35);
border:1px solid rgba(255,255,255,0.08);
color:#f1f5f9;
font-family:Tahoma, sans-serif;
position:relative;
overflow:hidden;">

        <div style="position:absolute;top:-60px;right:-60px;width:200px;height:200px;
    background:radial-gradient(circle,rgba(59,130,246,0.4),transparent 60%);
    filter:blur(10px);"></div>

        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;">

            <div style="display:flex;align-items:flex-start;gap:15px;flex:1;min-width:250px;">
                <div style="width:48px;height:48px;border-radius:14px;
            background:rgba(234,179,8,0.15);
            display:flex;align-items:center;justify-content:center;
            border:1px solid rgba(234,179,8,0.35);font-size:22px;">
                    🔒
                </div>
                <div>
                    <h3 style="margin:0 0 8px 0;font-size:16px;font-weight:bold;color:#ffffff;">
                        لایسنس فعال نشده است
                    </h3>
                    <p style="margin:0;font-size:14px;color:#cbd5e1;line-height:1.7;">
                        جهت فعال‌سازی لایسنس روی دکمه مقابل کلیک کنید.
                    </p>
                </div>
            </div>

            <div>
                <a href="?page=nias-login-plugin-license"
                   style="display:inline-block;padding:10px 20px;border-radius:10px;
               text-decoration:none;font-size:14px;font-weight:bold;
               background:linear-gradient(135deg,#3b82f6,#6366f1);
               color:#ffffff;box-shadow:0 6px 18px rgba(59,130,246,0.4);
               transition:all 0.3s ease;">
                    فعال‌سازی لایسنس
                </a>
            </div>

        </div>
    </div>
    <?php
}
