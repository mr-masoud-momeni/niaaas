<?php

class Elementor_nias_fastsell_widget extends \Elementor\Widget_Base
{
	public function get_name()         { return 'nias_fastsell_widget'; }
	public function get_title()        { return esc_html__('خرید سریع نیاس', 'nias-login-signup'); }
	public function get_icon()         { return 'eicon-cart-medium'; }
	public function get_categories()   { return ['nias-login']; }
	public function get_keywords()     { return ['خرید سریع', 'سبد خرید', 'فروش', 'Nias', 'fastsell']; }
	public function get_custom_help_url() { return 'https://Nias.ir'; }

	public function get_style_depends()
	{
		[$url, $ver] = nias_login_asset('fastsell/assets/easysale.css');
		wp_enqueue_style('nias-fastsell-style', $url, [], $ver);
		return ['nias-fastsell-style'];
	}

	public function get_script_depends()
	{
		[$url, $ver] = nias_login_asset('fastsell/assets/easysale.js');
		wp_enqueue_script('nias-fastsell-script', $url, ['jquery'], $ver, true);
		return ['nias-fastsell-script'];
	}

	protected function register_controls()
	{
		// Reusable selector strings — each targets both editor preview ({{WRAPPER}}) and live modal (#nias-login-fastsell-modal)
		$modal_sel       = '{{WRAPPER}} .nias-login-fastsell-modal-content, #nias-login-fastsell-modal .nias-login-fastsell-modal-content';
		$btn_sel         = '{{WRAPPER}} .nias-login-fastsell-btn-next, {{WRAPPER}} .nias-login-fastsell-btn-back, {{WRAPPER}} .nias-login-fastsell-btn-submit, {{WRAPPER}} #nias-login-fastsell-apply-coupon, #nias-login-fastsell-modal .nias-login-fastsell-btn-next, #nias-login-fastsell-modal .nias-login-fastsell-btn-back, #nias-login-fastsell-modal .nias-login-fastsell-btn-submit, #nias-login-fastsell-modal #nias-login-fastsell-apply-coupon';
		$btn_hover_sel   = '{{WRAPPER}} .nias-login-fastsell-btn-next:hover, {{WRAPPER}} .nias-login-fastsell-btn-back:hover, {{WRAPPER}} .nias-login-fastsell-btn-submit:hover, {{WRAPPER}} #nias-login-fastsell-apply-coupon:hover, #nias-login-fastsell-modal .nias-login-fastsell-btn-next:hover, #nias-login-fastsell-modal .nias-login-fastsell-btn-back:hover, #nias-login-fastsell-modal .nias-login-fastsell-btn-submit:hover, #nias-login-fastsell-modal #nias-login-fastsell-apply-coupon:hover';
		$step_sel        = '{{WRAPPER}} .nias-login-fastsell-step, #nias-login-fastsell-modal .nias-login-fastsell-step';
		$step_active_sel = '{{WRAPPER}} .nias-login-fastsell-step.active, #nias-login-fastsell-modal .nias-login-fastsell-step.active';
		$step_num_sel    = '{{WRAPPER}} .nias-login-fastsell-step-number, #nias-login-fastsell-modal .nias-login-fastsell-step-number';
		$field_base      = '{{WRAPPER}} .nias-login-fastsell-modal-content input, {{WRAPPER}} .nias-login-fastsell-modal-content select, {{WRAPPER}} .nias-login-fastsell-modal-content textarea, #nias-login-fastsell-modal input, #nias-login-fastsell-modal select, #nias-login-fastsell-modal textarea';
		$co_field_sel    = '{{WRAPPER}} .nias-login-fastsell-checkout-fields input, {{WRAPPER}} .nias-login-fastsell-checkout-fields select, {{WRAPPER}} .nias-login-fastsell-checkout-fields textarea, #nias-login-fastsell-modal .nias-login-fastsell-checkout-fields input, #nias-login-fastsell-modal .nias-login-fastsell-checkout-fields select, #nias-login-fastsell-modal .nias-login-fastsell-checkout-fields textarea';
		$close_sel       = '{{WRAPPER}} button.nias-login-fastsell-modal-close, #nias-login-fastsell-modal button.nias-login-fastsell-modal-close';

		/* ====================================================================
		 * CONTENT TAB
		 * ==================================================================== */

		// ── تنظیمات عمومی ──────────────────────────────────────────────────
		$this->start_controls_section('nias_fastsell_general', [
			'label' => esc_html__('تنظیمات عمومی', 'nias-login-signup'),
			'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
		]);

		$this->add_control('nias_fastsell_info', [
			'type'        => \Elementor\Controls_Manager::NOTICE,
			'notice_type' => 'info',
			'dismissible' => false,
			'heading'     => esc_html__('نکته', 'nias-login-signup'),
			'content'     => wp_kses_post(__('ویجت بصورت مودال نمایش داده میشود و فقط در المنتور به این صورت ثابت است همچنین پیشنهاد میشود فاصله داخلی کانتینرتان را جهت نمایش بهتر 0 کنید', 'nias-login-signup')),
		]);

		$this->add_control('nias_fastsell_close_icon', [
			'label'   => esc_html__('آیکون بستن', 'nias-login-signup'),
			'type'    => \Elementor\Controls_Manager::ICONS,
			'default' => ['value' => 'fas fa-times', 'library' => 'fa-solid'],
		]);

		$this->add_control('nias_fastsell_stepnumber', [
			'label'                => esc_html__('نمایش شمارنده مرحله', 'nias-login-signup'),
			'type'                 => \Elementor\Controls_Manager::SWITCHER,
			'label_on'             => esc_html__('بله', 'nias-login-signup'),
			'label_off'            => esc_html__('خیر', 'nias-login-signup'),
			'return_value'         => 'yes',
			'default'              => 'yes',
			'selectors'            => [$step_num_sel => 'display: {{VALUE}};'],
			'selectors_dictionary' => ['yes' => 'flex', '' => 'none'],
		]);

		$this->add_control('nias_fastsell_close_outside', [
			'label'        => esc_html__('بستن با کلیک خارج از مودال', 'nias-login-signup'),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'label_on'     => esc_html__('بله', 'nias-login-signup'),
			'label_off'    => esc_html__('خیر', 'nias-login-signup'),
			'return_value' => 'yes',
			'default'      => 'yes',
		]);

		$this->end_controls_section();

		// ── مرحله سبد خرید ──────────────────────────────────────────────────
		$this->start_controls_section('nias_fastsell_cart_step', [
			'label' => esc_html__('مرحله سبد خرید', 'nias-login-signup'),
			'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
		]);

		$this->add_control('nias_fastsell_cart_title', [
			'label'   => esc_html__('عنوان مرحله سبد', 'nias-login-signup'),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => esc_html__('سبد خرید', 'nias-login-signup'),
			'dynamic' => ['active' => true],
		]);

		$this->add_control('nias_fastsell_coupon_placeholder', [
			'label'   => esc_html__('متن placeholder کد تخفیف', 'nias-login-signup'),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => esc_html__('کد تخفیف', 'nias-login-signup'),
			'dynamic' => ['active' => true],
		]);

		$this->add_control('nias_fastsell_coupon_button', [
			'label'   => esc_html__('متن دکمه اعمال کد تخفیف', 'nias-login-signup'),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => esc_html__('اعمال', 'nias-login-signup'),
			'dynamic' => ['active' => true],
		]);

		$this->add_control('nias_fastsell_next_button', [
			'label'   => esc_html__('متن دکمه مرحله بعد', 'nias-login-signup'),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => esc_html__('مرحله بعد', 'nias-login-signup'),
			'dynamic' => ['active' => true],
		]);

		$this->end_controls_section();

		// ── مرحله تسویه حساب ────────────────────────────────────────────────
		$this->start_controls_section('nias_fastsell_checkout_step', [
			'label' => esc_html__('مرحله تسویه حساب', 'nias-login-signup'),
			'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
		]);

		$this->add_control('nias_fastsell_checkout_title', [
			'label'   => esc_html__('عنوان مرحله تسویه', 'nias-login-signup'),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => esc_html__('تسویه حساب', 'nias-login-signup'),
			'dynamic' => ['active' => true],
		]);

		$this->add_control('nias_fastsell_billing_title', [
			'label'   => esc_html__('عنوان اطلاعات صورتحساب', 'nias-login-signup'),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => esc_html__('اطلاعات صورتحساب', 'nias-login-signup'),
			'dynamic' => ['active' => true],
		]);

		$this->add_control('nias_fastsell_shipping_title', [
			'label'   => esc_html__('عنوان روش حمل و نقل', 'nias-login-signup'),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => esc_html__('روش حمل و نقل', 'nias-login-signup'),
			'dynamic' => ['active' => true],
		]);

		$this->add_control('nias_fastsell_payment_title', [
			'label'   => esc_html__('عنوان روش پرداخت', 'nias-login-signup'),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => esc_html__('روش پرداخت', 'nias-login-signup'),
			'dynamic' => ['active' => true],
		]);

		$this->add_control('nias_fastsell_back_button', [
			'label'   => esc_html__('متن دکمه بازگشت', 'nias-login-signup'),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => esc_html__('بازگشت', 'nias-login-signup'),
			'dynamic' => ['active' => true],
		]);

		$this->add_control('nias_fastsell_submit_button', [
			'label'   => esc_html__('متن دکمه پرداخت', 'nias-login-signup'),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => esc_html__('پرداخت', 'nias-login-signup'),
			'dynamic' => ['active' => true],
		]);

		$this->end_controls_section();

		/* ====================================================================
		 * STYLE TAB
		 * ==================================================================== */

		// ── استایل مودال ────────────────────────────────────────────────────
		$this->start_controls_section('nias_fastsell_style_modal', [
			'label' => esc_html__('استایل مودال', 'nias-login-signup'),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		]);

		$this->add_control('nias_fastsell_styleinfo', [
			'type'        => \Elementor\Controls_Manager::NOTICE,
			'notice_type' => 'info',
			'dismissible' => false,
			'heading'     => esc_html__('نکته', 'nias-login-signup'),
			'content'     => wp_kses_post(__('استایل‌ها هم در پیش‌نمایش المنتور و هم روی مودال واقعی اعمال می‌شوند', 'nias-login-signup')),
		]);

		$this->add_group_control(\Elementor\Group_Control_Background::get_type(), [
			'name'     => 'nias_fastsell_modal_background',
			'types'    => ['classic', 'gradient'],
			'selector' => $modal_sel,
		]);

		$this->add_responsive_control('nias_fastsell_modal_width', [
			'label'      => esc_html__('عرض مودال', 'nias-login-signup'),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => ['px', '%', 'vw'],
			'range'      => [
				'px' => ['min' => 300, 'max' => 1200, 'step' => 10],
				'%'  => ['min' => 10,  'max' => 100],
			],
			'default'   => ['unit' => 'px', 'size' => 800],
			'selectors' => [$modal_sel => 'max-width: {{SIZE}}{{UNIT}};'],
		]);

		$this->add_responsive_control('nias_fastsell_modal_padding', [
			'label'      => esc_html__('فاصله داخلی', 'nias-login-signup'),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => ['px', '%', 'em'],
			'selectors'  => [$modal_sel => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
		]);

		$this->add_responsive_control('nias_fastsell_modal_border_radius', [
			'label'      => esc_html__('گردی گوشه‌ها', 'nias-login-signup'),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => ['px', '%'],
			'selectors'  => [$modal_sel => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
		]);

		$this->add_control('nias_fastsell_modal_close_heading', [
			'label'     => esc_html__('دکمه بستن', 'nias-login-signup'),
			'type'      => \Elementor\Controls_Manager::HEADING,
			'separator' => 'before',
		]);

		$this->add_control('nias_fastsell_close_btn_bg', [
			'label'     => esc_html__('رنگ پس‌زمینه', 'nias-login-signup'),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [$close_sel => 'background-color: {{VALUE}};'],
		]);

		$this->add_control('nias_fastsell_close_btn_color', [
			'label'     => esc_html__('رنگ آیکون', 'nias-login-signup'),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [$close_sel => 'color: {{VALUE}};'],
		]);

		$this->add_responsive_control('nias_fastsell_close_btn_size', [
			'label'      => esc_html__('اندازه دکمه', 'nias-login-signup'),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => ['px'],
			'range'      => ['px' => ['min' => 20, 'max' => 80]],
			'selectors'  => [$close_sel => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};'],
		]);

		$this->end_controls_section();

		// ── استایل پس‌زمینه تیره ────────────────────────────────────────────
		$this->start_controls_section('nias_fastsell_style_overlay', [
			'label' => esc_html__('استایل پس‌زمینه تیره', 'nias-login-signup'),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		]);

		$this->add_control('nias_fastsell_overlay_bg', [
			'label'     => esc_html__('رنگ پس‌زمینه', 'nias-login-signup'),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => ['#nias-login-fastsell-modal .nias-login-fastsell-modal-overlay' => 'background: {{VALUE}};'],
		]);

		$this->end_controls_section();

		// ── استایل استپ‌ها ──────────────────────────────────────────────────
		$this->start_controls_section('nias_fastsell_style_steps', [
			'label' => esc_html__('استایل استپ‌ها', 'nias-login-signup'),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		]);

		$this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'nias_fastsell_step_typography',
			'selector' => '{{WRAPPER}} .nias-login-fastsell-step-title, #nias-login-fastsell-modal .nias-login-fastsell-step-title',
		]);

		$this->start_controls_tabs('nias_fastsell_step_tabs');

		$this->start_controls_tab('nias_fastsell_step_normal_tab', [
			'label' => esc_html__('غیرفعال', 'nias-login-signup'),
		]);

		$this->add_control('nias_fastsell_step_bg', [
			'label'     => esc_html__('رنگ پس‌زمینه', 'nias-login-signup'),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [$step_sel => 'background: {{VALUE}};'],
		]);

		$this->add_control('nias_fastsell_step_color', [
			'label'     => esc_html__('رنگ متن', 'nias-login-signup'),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [$step_sel => 'color: {{VALUE}};'],
		]);

		$this->end_controls_tab();

		$this->start_controls_tab('nias_fastsell_step_active_tab', [
			'label' => esc_html__('فعال', 'nias-login-signup'),
		]);

		$this->add_control('nias_fastsell_step_active_bg', [
			'label'     => esc_html__('رنگ پس‌زمینه', 'nias-login-signup'),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [$step_active_sel => 'background: {{VALUE}};'],
		]);

		$this->add_control('nias_fastsell_step_active_color', [
			'label'     => esc_html__('رنگ متن و آیکون', 'nias-login-signup'),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [
				$step_active_sel => 'color: {{VALUE}};',
				'{{WRAPPER}} .nias-login-fastsell-step.active path, #nias-login-fastsell-modal .nias-login-fastsell-step.active path' => 'stroke: {{VALUE}};',
			],
		]);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control('nias_fastsell_step_border_radius', [
			'label'      => esc_html__('گردی گوشه‌ها', 'nias-login-signup'),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => ['px', '%'],
			'separator'  => 'before',
			'selectors'  => [$step_sel => 'border-radius: {{SIZE}}{{UNIT}};'],
		]);

		$this->add_control('nias_fastsell_step_number_heading', [
			'label'     => esc_html__('شمارنده مرحله', 'nias-login-signup'),
			'type'      => \Elementor\Controls_Manager::HEADING,
			'separator' => 'before',
		]);

		$this->add_control('nias_fastsell_step_number_bg', [
			'label'     => esc_html__('رنگ پس‌زمینه', 'nias-login-signup'),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [$step_num_sel => 'background: {{VALUE}};'],
		]);

		$this->add_control('nias_fastsell_step_number_color', [
			'label'     => esc_html__('رنگ عدد', 'nias-login-signup'),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [$step_num_sel => 'color: {{VALUE}};'],
		]);

		$this->end_controls_section();

		// ── استایل سبد خرید ─────────────────────────────────────────────────
		$this->start_controls_section('nias_fastsell_style_cart', [
			'label' => esc_html__('استایل سبد خرید', 'nias-login-signup'),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		]);

		$this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'nias_fastsell_cart_item_typography',
			'label'    => esc_html__('تایپوگرافی نام محصول', 'nias-login-signup'),
			'selector' => '{{WRAPPER}} .nias-login-fastsell-cart-item h4, #nias-login-fastsell-modal .nias-login-fastsell-cart-item h4',
		]);

		$this->add_control('nias_fastsell_cart_price_color', [
			'label'     => esc_html__('رنگ قیمت', 'nias-login-signup'),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .nias-login-fastsell-item-price, #nias-login-fastsell-modal .nias-login-fastsell-item-price' => 'color: {{VALUE}};',
				'{{WRAPPER}} .nias-fastsell-grand-total-val, #nias-login-fastsell-modal .nias-fastsell-grand-total-val'   => 'color: {{VALUE}};',
			],
		]);

		$this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
			'name'     => 'nias_fastsell_cart_item_border',
			'label'    => esc_html__('حاشیه آیتم سبد', 'nias-login-signup'),
			'selector' => '{{WRAPPER}} .nias-login-fastsell-cart-item, #nias-login-fastsell-modal .nias-login-fastsell-cart-item',
		]);

		$this->add_responsive_control('nias_fastsell_cart_item_radius', [
			'label'      => esc_html__('گردی گوشه‌های آیتم', 'nias-login-signup'),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => ['px', '%'],
			'selectors'  => [
				'{{WRAPPER}} .nias-login-fastsell-cart-item, #nias-login-fastsell-modal .nias-login-fastsell-cart-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		]);

		$this->add_control('nias_fastsell_remove_btn_color', [
			'label'     => esc_html__('رنگ دکمه حذف', 'nias-login-signup'),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'separator' => 'before',
			'selectors' => [
				'{{WRAPPER}} button.nias-login-fastsell-remove-item, #nias-login-fastsell-modal button.nias-login-fastsell-remove-item' => 'background-color: {{VALUE}};',
			],
		]);

		$this->end_controls_section();

		// ── استایل تسویه حساب ───────────────────────────────────────────────
		$this->start_controls_section('nias_fastsell_style_checkout', [
			'label' => esc_html__('استایل تسویه حساب', 'nias-login-signup'),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		]);

		$this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'nias_fastsell_checkout_label_typography',
			'label'    => esc_html__('تایپوگرافی برچسب‌ها', 'nias-login-signup'),
			'selector' => '{{WRAPPER}} .nias-login-fastsell-checkout-fields label, #nias-login-fastsell-modal .nias-login-fastsell-checkout-fields label',
		]);

		$this->add_control('nias_fastsell_checkout_label_color', [
			'label'     => esc_html__('رنگ برچسب‌ها', 'nias-login-signup'),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .nias-login-fastsell-checkout-fields label, #nias-login-fastsell-modal .nias-login-fastsell-checkout-fields label' => 'color: {{VALUE}};',
			],
		]);

		$this->add_control('nias_fastsell_co_field_color', [
			'label'     => esc_html__('رنگ متن فیلدها', 'nias-login-signup'),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [$co_field_sel => 'color: {{VALUE}};'],
		]);

		$this->add_control('nias_fastsell_co_field_bg', [
			'label'     => esc_html__('رنگ پس‌زمینه فیلدها', 'nias-login-signup'),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [$co_field_sel => 'background-color: {{VALUE}};'],
		]);

		$this->add_control('nias_fastsell_co_field_border', [
			'label'     => esc_html__('رنگ حاشیه فیلدها', 'nias-login-signup'),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [$co_field_sel => 'border-color: {{VALUE}};'],
		]);

		$this->add_control('nias_fastsell_co_field_focus', [
			'label'     => esc_html__('رنگ حاشیه هنگام فوکوس', 'nias-login-signup'),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .nias-login-fastsell-checkout-fields input:focus, {{WRAPPER}} .nias-login-fastsell-checkout-fields select:focus, {{WRAPPER}} .nias-login-fastsell-checkout-fields textarea:focus, #nias-login-fastsell-modal .nias-login-fastsell-checkout-fields input:focus, #nias-login-fastsell-modal .nias-login-fastsell-checkout-fields select:focus, #nias-login-fastsell-modal .nias-login-fastsell-checkout-fields textarea:focus' => 'border-color: {{VALUE}};',
			],
		]);

		$this->add_responsive_control('nias_fastsell_co_field_radius', [
			'label'      => esc_html__('گردی گوشه فیلدها', 'nias-login-signup'),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => ['px', '%'],
			'selectors'  => [$co_field_sel => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
		]);

		$this->end_controls_section();

		// ── استایل دکمه‌ها ──────────────────────────────────────────────────
		$this->start_controls_section('nias_fastsell_style_buttons', [
			'label' => esc_html__('استایل دکمه‌ها', 'nias-login-signup'),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		]);

		$this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'nias_fastsell_button_typography',
			'selector' => $btn_sel,
		]);

		$this->start_controls_tabs('nias_fastsell_button_tabs');

		$this->start_controls_tab('nias_fastsell_button_normal', [
			'label' => esc_html__('عادی', 'nias-login-signup'),
		]);

		$this->add_control('nias_fastsell_button_color', [
			'label'     => esc_html__('رنگ متن', 'nias-login-signup'),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [$btn_sel => 'color: {{VALUE}};'],
		]);

		$this->add_control('nias_fastsell_button_background', [
			'label'     => esc_html__('رنگ پس‌زمینه', 'nias-login-signup'),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [$btn_sel => 'background-color: {{VALUE}};'],
		]);

		$this->end_controls_tab();

		$this->start_controls_tab('nias_fastsell_button_hover', [
			'label' => esc_html__('هاور', 'nias-login-signup'),
		]);

		$this->add_control('nias_fastsell_button_hover_color', [
			'label'     => esc_html__('رنگ متن', 'nias-login-signup'),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [$btn_hover_sel => 'color: {{VALUE}};'],
		]);

		$this->add_control('nias_fastsell_button_hover_background', [
			'label'     => esc_html__('رنگ پس‌زمینه', 'nias-login-signup'),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [$btn_hover_sel => 'background-color: {{VALUE}};'],
		]);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
			'name'     => 'nias_fastsell_button_border',
			'selector' => $btn_sel,
		]);

		$this->add_responsive_control('nias_fastsell_button_border_radius', [
			'label'      => esc_html__('گردی گوشه‌ها', 'nias-login-signup'),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => ['px', '%'],
			'selectors'  => [$btn_sel => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
		]);

		$this->add_responsive_control('nias_fastsell_button_padding', [
			'label'      => esc_html__('فاصله داخلی', 'nias-login-signup'),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => ['px', '%', 'em'],
			'selectors'  => [$btn_sel => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
		]);

		$this->end_controls_section();

		// ── استایل فیلدهای عمومی ────────────────────────────────────────────
		$this->start_controls_section('nias_fastsell_style_fields', [
			'label' => esc_html__('استایل فیلدهای عمومی', 'nias-login-signup'),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		]);

		$this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'nias_fastsell_field_typography',
			'selector' => $field_base,
		]);

		$this->add_control('nias_fastsell_field_color', [
			'label'     => esc_html__('رنگ متن', 'nias-login-signup'),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [$field_base => 'color: {{VALUE}};'],
		]);

		$this->add_control('nias_fastsell_field_background', [
			'label'     => esc_html__('رنگ پس‌زمینه', 'nias-login-signup'),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [$field_base => 'background-color: {{VALUE}};'],
		]);

		$this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
			'name'     => 'nias_fastsell_field_border',
			'selector' => $field_base,
		]);

		$this->add_responsive_control('nias_fastsell_field_border_radius', [
			'label'      => esc_html__('گردی گوشه‌ها', 'nias-login-signup'),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => ['px', '%'],
			'selectors'  => [$field_base => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
		]);

		$this->end_controls_section();
	}

	protected function render()
	{
		$settings = $this->get_settings_for_display();

		// Always inject settings for the live modal (easysale.js reads window.niasFastsellElementorSettings)
		$fastsell_settings = [
			'cart_title'         => $settings['nias_fastsell_cart_title'],
			'checkout_title'     => $settings['nias_fastsell_checkout_title'],
			'billing_title'      => $settings['nias_fastsell_billing_title'],
			'shipping_title'     => $settings['nias_fastsell_shipping_title'],
			'payment_title'      => $settings['nias_fastsell_payment_title'],
			'coupon_placeholder' => $settings['nias_fastsell_coupon_placeholder'],
			'coupon_button'      => $settings['nias_fastsell_coupon_button'],
			'next_button'        => $settings['nias_fastsell_next_button'],
			'back_button'        => $settings['nias_fastsell_back_button'],
			'submit_button'      => $settings['nias_fastsell_submit_button'],
			'close_outside'      => 'yes' === ($settings['nias_fastsell_close_outside'] ?? 'yes'),
			'stepnumber'         => 'yes' === $settings['nias_fastsell_stepnumber'],
		];
		echo '<script>window.niasFastsellElementorSettings=' . wp_json_encode($fastsell_settings) . ';</script>';

		$is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode()
		          || \Elementor\Plugin::$instance->preview->is_preview_mode();

		$show_numbers = 'yes' === $settings['nias_fastsell_stepnumber'];
		$uid          = esc_attr($this->get_id());

		// In editor: wrap in modal-content so style controls have something to preview.
		// In production: modal_template() in easysale.php already wraps the shortcode output in
		// #nias-login-fastsell-modal > overlay + .nias-login-fastsell-modal-content, so we only
		// output the inner HTML here.
		if ($is_editor) {
			echo '<div class="nias-login-fastsell-modal-content" style="position:relative;overflow:visible;display:block;visibility:visible;opacity:1;pointer-events:auto;">';
		}
		?>


			<button class="nias-login-fastsell-modal-close" style="pointer-events:none;">
				<div class="elementor-icon">
					<?php \Elementor\Icons_Manager::render_icon($settings['nias_fastsell_close_icon'], ['aria-hidden' => 'true']); ?>
				</div>
			</button>

			<div class="nias-login-fastsell-modal-content-close">
				<svg xmlns="http://www.w3.org/2000/svg" width="1478" height="41" viewBox="0 0 1478 41" fill="none">
					<g clip-path="url(#nfw_<?php echo $uid; ?>)">
						<path d="M783 9c0-4.97056 4.029-9 9-9h686V78H783V9z" fill="#fff"/>
						<path d="M695 9c0-4.97056-4.029-9-9-9H0V78H695V9z" fill="#fff"/>
						<path d="M783 4.43478h4.5V-.065218C787.5-4.10792 784.539-6.8524 781.971-8.49877 779.197-10.2779 775.481-11.7108 771.291-12.8475 762.845-15.1383 751.43-16.5 739-16.5S715.155-15.1383 706.709-12.8475C702.519-11.7108 698.803-10.2779 696.029-8.49877 693.461-6.8524 690.5-4.10792 690.5-.065218V4.43478H695C701.879 4.43478 705.465 8.30265 710.969 14.2381 711.21 14.4984 711.455 14.7627 711.704 15.0308 717.501 21.2683 724.892 28.5 739 28.5c14.087.0 21.619-7.2065 27.496-13.4478C766.797 14.7323 767.092 14.4181 767.381 14.1096 772.921 8.20777 776.462 4.43478 783 4.43478z" stroke="#fff" stroke-width="9"/>
						<path d="M694 5l38.5 24H755L784 5l8 24V41H686l8-36z" fill="#fff"/>
					</g>
					<defs>
						<clipPath id="nfw_<?php echo $uid; ?>">
							<rect width="1478" height="41" fill="#fff"/>
						</clipPath>
					</defs>
				</svg>
			</div>

			<div class="nias-login-fastsell-steps">
				<div class="nias-login-fastsell-step active" data-step="1">
					<svg width="24" height="24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M8.5 14.25c0 1.92 1.58 3.5 3.5 3.5s3.5-1.58 3.5-3.5M8.81 2L5.19 5.63M15.19 2l3.62 3.63"
							stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M2 7.85c0-1.85.99-2 2.22-2h15.56c1.23 0 2.22.15 2.22 2 0 2.15-.99 2-2.22 2H4.22C2.99 9.85 2 10 2 7.85z"
							stroke="#292D32" stroke-width="1.5"/>
						<path d="M3.5 10l1.41 8.64C5.23 20.58 6 22 8.86 22h6.03c3.11 0 3.57-1.36 3.93-3.24L20.5 10"
							stroke="#292D32" stroke-width="1.5" stroke-linecap="round"/>
					</svg>
					<?php if ($show_numbers) : ?>
					<span class="nias-login-fastsell-step-number">1</span>
					<?php endif; ?>
					<span class="nias-login-fastsell-step-title" data-default="سبد خرید">
						<?php echo esc_html($settings['nias_fastsell_cart_title']); ?>
					</span>
				</div>
				<div class="nias-login-fastsell-step" data-step="2">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M18.04 13.55C17.62 13.96 17.38 14.55 17.44 15.18C17.53 16.26 18.52 17.05 19.6 17.05H21.5V18.24C21.5 20.31 19.81 22 17.74 22H6.26C4.19 22 2.5 20.31 2.5 18.24V11.51C2.5 9.44001 4.19 7.75 6.26 7.75H17.74C19.81 7.75 21.5 9.44001 21.5 11.51V12.95H19.48C18.92 12.95 18.41 13.17 18.04 13.55Z"
							stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M2.5 12.4101V7.8401C2.5 6.6501 3.23 5.59006 4.34 5.17006L12.28 2.17006C13.52 1.70006 14.85 2.62009 14.85 3.95009V7.75008"
							stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M22.5588 13.9702V16.0302C22.5588 16.5802 22.1188 17.0302 21.5588 17.0502H19.5988C18.5188 17.0502 17.5288 16.2602 17.4388 15.1802C17.3788 14.5502 17.6188 13.9602 18.0388 13.5502C18.4088 13.1702 18.9188 12.9502 19.4788 12.9502H21.5588C22.1188 12.9702 22.5588 13.4202 22.5588 13.9702Z"
							stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M7 12H14" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					<?php if ($show_numbers) : ?>
					<span class="nias-login-fastsell-step-number">2</span>
					<?php endif; ?>
					<span class="nias-login-fastsell-step-title" data-default="تسویه حساب">
						<?php echo esc_html($settings['nias_fastsell_checkout_title']); ?>
					</span>
				</div>
			</div>

			<?php
			// ناحیه اسکرول: بدون این ظرف، محتوای مودال هیچ اسکرولی ندارد و چرخش موس
			// مستقیم به صفحه پشت مودال می‌رود. تب‌ها عمداً بیرون می‌مانند تا هنگام
			// اسکرول ثابت بمانند — همان رفتار قالب پیش‌فرض (templates/modal.php)
			?>
			<div class="nias-fastsell-scroll-area">

			<div class="nias-login-fastsell-step-content" id="nias-login-fastsell-step-1">
				<div class="nias-login-fastsell-cart-container"></div>
				<div class="nias-login-fastsell-coupon">
					<input type="text" id="nias-login-fastsell-coupon-code"
					       data-default-placeholder="کد تخفیف"
					       placeholder="<?php echo esc_attr($settings['nias_fastsell_coupon_placeholder']); ?>">
					<button id="nias-login-fastsell-apply-coupon" data-default-text="اعمال">
						<?php echo esc_html($settings['nias_fastsell_coupon_button']); ?>
					</button>
				</div>
				<button class="nias-login-fastsell-btn-next" data-default-text="مرحله بعد">
					<?php echo esc_html($settings['nias_fastsell_next_button']); ?>
				</button>
			</div>

			<div class="nias-login-fastsell-step-content" id="nias-login-fastsell-step-2" style="display:none;">
				<div id="nias-login-fastsell-checkout-container"></div>
				<?php if (get_option('nias_fastsell_show_order_summary', 1)) : ?>
				<div id="nias-fastsell-checkout-order-summary"></div>
				<?php endif; ?>
				<div class="nias-login-fastsell-actions">
					<button type="button" class="nias-login-fastsell-btn-back" data-default-text="بازگشت">
						<?php echo esc_html($settings['nias_fastsell_back_button']); ?>
					</button>
					<button type="button" class="nias-login-fastsell-btn-submit" data-default-text="پرداخت">
						<?php echo esc_html($settings['nias_fastsell_submit_button']); ?>
					</button>
				</div>
			</div>

			</div><?php // پایان .nias-fastsell-scroll-area ?>

			<div class="nias-login-fastsell-loading" style="display:none;">
				<div class="nias-login-fastsell-spinner"></div>
			</div>
		<?php if ($is_editor) : ?>
		</div>
		<?php endif; ?>
		<?php
	}
}
