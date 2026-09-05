<?php

defined ('ABSPATH') || exit;

/**
 * دسته‌ی اختصاصی افزونه در پنل المنتور.
 *
 * قبلاً هر سه ویجت در دسته‌ی «basic» بودند، یعنی درست کنار Heading و Image در
 * پرمصرف‌ترین بخش پنل — و کاربر ناخواسته مستقیم روی برگه رهایشان می‌کرد.
 *
 * توجه: دسته فقط تعیین می‌کند ویجت در کدام بخش پنل «فهرست» شود. قالب‌ها و
 * برگه‌های موجود ویجت را با نامش (مثلاً nias_fastsell_widget) در _elementor_data
 * ذخیره کرده‌اند، نه با دسته؛ پس هیچ طراحی قبلی‌ای با این تغییر نمی‌شکند.
 */
function nias_register_elementor_category( $elements_manager ) {
	$elements_manager->add_category(
		'nias-login',
		[
			'title' => esc_html__( 'نیاس لاگین', 'nias-login-signup' ),
			'icon'  => 'eicon-lock-user',
		]
	);
}
add_action( 'elementor/elements/categories_registered', 'nias_register_elementor_category' );

function register_niaslogin_widget( $widgets_manager ){

    require_once(NIAS_ELEMENTOR_WIDGET . 'login-widget.php');
    $widgets_manager->register( new \Elementor_niaslogin_widget() );
}
add_action( 'elementor/widgets/register', 'register_niaslogin_widget');

function register_nias_metadata_widget( $widgets_manager ){

    require_once(NIAS_ELEMENTOR_WIDGET . 'metadata-widget.php');
    $widgets_manager->register( new \Elementor_nias_metadata_widget() );
}
add_action( 'elementor/widgets/register', 'register_nias_metadata_widget');

function register_nias_fastsell_widget( $widgets_manager ){

    require_once(NIAS_ELEMENTOR_WIDGET . 'fastsell-widget.php');
    $widgets_manager->register( new \Elementor_nias_fastsell_widget() );
}
add_action( 'elementor/widgets/register', 'register_nias_fastsell_widget');








