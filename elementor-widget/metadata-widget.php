<?php

class Elementor_nias_metadata_widget extends \Elementor\Widget_Base
{

	public function get_name()
	{
		return 'nias_metadata_widget';
	}

	public function get_title()
	{
		return esc_html__('متادیتای کاربر نیاس', 'nias-login-signup');
	}

	public function get_icon()
	{
		return 'eicon-form-horizontal';
	}

	public function get_categories()
	{
		return ['nias-login'];
	}

	public function get_keywords()
	{
		return ['متادیتا', 'فرم', 'کاربر', 'Nias', 'metadata'];
	}

	public function get_custom_help_url()
	{
		return 'https://Nias.ir';
	}

	protected function register_controls()
	{
		// تب محتوا - تنظیمات عمومی
		$this->start_controls_section(
			'nias_meta_general',
			[
				'label' => esc_html__('تنظیمات عمومی', 'nias-login-signup'),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);
				$this->add_control(
			'nias_info_moalmeta',
			[
				'type' => \Elementor\Controls_Manager::NOTICE,
				'notice_type' => 'info',
				'dismissible' => false,
				'heading' => esc_html__('نکته', 'nias-login-signup'),
				'content' => wp_kses_post(__(' این ویجت فقط برای پیش‌نمایش است. در حالت واقعی، فرم به‌صورت مودال نمایش داده می‌شود.', 'nias-login-signup')),
			]
		);

		$this->add_control(
			'nias_meta_force',
			[
				'label' => esc_html__('اجباری بودن تکمیل', 'nias-login-signup'),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__('بله', 'nias-login-signup'),
				'label_off' => esc_html__('خیر', 'nias-login-signup'),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->end_controls_section();

		// تب محتوا - نقش‌های مشمول
		$this->start_controls_section(
			'nias_meta_roles',
			[
				'label' => esc_html__('نقش‌های مشمول', 'nias-login-signup'),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$editable_roles = function_exists('get_editable_roles') ? get_editable_roles() : [];
		$roles_options = [];
		foreach ($editable_roles as $role_key => $role_data) {
			$roles_options[$role_key] = $role_data['name'];
		}

		$this->add_control(
			'nias_meta_selected_roles',
			[
				'label' => esc_html__('انتخاب نقش‌ها', 'nias-login-signup'),
				'type' => \Elementor\Controls_Manager::SELECT2,
				'multiple' => true,
				'options' => $roles_options,
				'default' => [],
				'label_block' => true,
				'description' => esc_html__('در صورت خالی بودن، برای همه نقش‌ها اعمال می‌شود', 'nias-login-signup'),
			]
		);

		$this->end_controls_section();

		// تب محتوا - فیلدها
		$this->start_controls_section(
			'nias_meta_fields',
			[
				'label' => esc_html__('فیلدهای متادیتا', 'nias-login-signup'),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'field_type',
			[
				'label' => esc_html__('نوع فیلد', 'nias-login-signup'),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'ready',
				'options' => [
					'ready' => esc_html__('فیلد آماده', 'nias-login-signup'),
					'custom' => esc_html__('فیلد دستی', 'nias-login-signup'),
				],
			]
		);

		$repeater->add_control(
			'ready_field',
			[
				'label' => esc_html__('انتخاب فیلد آماده', 'nias-login-signup'),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'first_name',
				'options' => [
					'first_name'           => esc_html__('نام (first_name)', 'nias-login-signup'),
					'last_name'            => esc_html__('نام خانوادگی (last_name)', 'nias-login-signup'),
					'description'          => esc_html__('بیوگرافی (description)', 'nias-login-signup'),
					'billing_first_name'   => esc_html__('نام صورتحساب (Woo)', 'nias-login-signup'),
					'billing_last_name'    => esc_html__('نام‌خانوادگی صورتحساب (Woo)', 'nias-login-signup'),
					'billing_company'      => esc_html__('شرکت (Woo)', 'nias-login-signup'),
					'billing_phone'        => esc_html__('تلفن صورتحساب (Woo)', 'nias-login-signup'),
					'billing_email'        => esc_html__('ایمیل صورتحساب (Woo)', 'nias-login-signup'),
					'billing_address_1'    => esc_html__('آدرس اول (Woo)', 'nias-login-signup'),
					'billing_address_2'    => esc_html__('آدرس دوم (Woo)', 'nias-login-signup'),
					'billing_city'         => esc_html__('شهر (Woo)', 'nias-login-signup'),
					'billing_state'        => esc_html__('استان (Woo)', 'nias-login-signup'),
					'billing_postcode'     => esc_html__('کدپستی (Woo)', 'nias-login-signup'),
					'billing_country'      => esc_html__('کشور (Woo)', 'nias-login-signup'),
					'shipping_first_name'  => esc_html__('نام ارسال (Woo)', 'nias-login-signup'),
					'shipping_last_name'   => esc_html__('نام خانوادگی ارسال (Woo)', 'nias-login-signup'),
					'shipping_company'     => esc_html__('شرکت ارسال (Woo)', 'nias-login-signup'),
					'shipping_address_1'   => esc_html__('آدرس ارسال (Woo)', 'nias-login-signup'),
					'shipping_city'        => esc_html__('شهر ارسال (Woo)', 'nias-login-signup'),
					'shipping_state'       => esc_html__('استان ارسال (Woo)', 'nias-login-signup'),
					'shipping_postcode'    => esc_html__('کدپستی ارسال (Woo)', 'nias-login-signup'),
				],
				'condition' => [
					'field_type' => 'ready',
				],
			]
		);

		$repeater->add_control(
			'custom_key',
			[
				'label' => esc_html__('کلید متا', 'nias-login-signup'),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => '',
				'placeholder' => esc_html__('meta_key', 'nias-login-signup'),
				'condition' => [
					'field_type' => 'custom',
				],
			]
		);

		$repeater->add_control(
			'custom_label',
			[
				'label' => esc_html__('لیبل فیلد', 'nias-login-signup'),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => '',
				'placeholder' => esc_html__('لیبل', 'nias-login-signup'),
				'condition' => [
					'field_type' => 'custom',
				],
			]
		);

		$repeater->add_control(
			'custom_field_type',
			[
				'label' => esc_html__('نوع فیلد HTML', 'nias-login-signup'),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'text',
				'options' => [
					'text'     => esc_html__('text', 'nias-login-signup'),
					'textarea' => esc_html__('textarea', 'nias-login-signup'),
					'email'    => esc_html__('email', 'nias-login-signup'),
					'tel'      => esc_html__('tel', 'nias-login-signup'),
					'number'   => esc_html__('number', 'nias-login-signup'),
					'date'     => esc_html__('date', 'nias-login-signup'),
					'select'   => esc_html__('select', 'nias-login-signup'),
				],
				'condition' => [
					'field_type' => 'custom',
				],
			]
		);

		$repeater->add_control(
			'custom_options',
			[
				'label' => esc_html__('گزینه‌ها (برای select)', 'nias-login-signup'),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => '',
				'placeholder' => esc_html__('گزینه۱|گزینه۲|گزینه۳', 'nias-login-signup'),
				'description' => esc_html__('گزینه‌ها را با | جدا کنید', 'nias-login-signup'),
				'condition' => [
					'field_type' => 'custom',
					'custom_field_type' => 'select',
				],
			]
		);

		$repeater->add_control(
			'custom_default_value',
			[
				'label' => esc_html__('مقدار پیشفرض فیلد', 'nias-login-signup'),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => '',
				'placeholder' => esc_html__('مقدار پیشفرض', 'nias-login-signup'),
				'description' => esc_html__('می‌توانید از برچسب‌های پویا استفاده کنید', 'nias-login-signup'),
				'dynamic' => [
					'active' => true,
				],
				'condition' => [
					'field_type' => 'custom',
				],
			]
		);

		$repeater->add_control(
			'field_required',
			[
				'label' => esc_html__('ضروری', 'nias-login-signup'),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__('بله', 'nias-login-signup'),
				'label_off' => esc_html__('خیر', 'nias-login-signup'),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->add_control(
			'nias_meta_fields_list',
			[
				'label' => esc_html__('لیست فیلدها', 'nias-login-signup'),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'default' => [
					[
						'field_type' => 'ready',
						'ready_field' => 'first_name',
						'field_required' => 'yes',
					],
					[
						'field_type' => 'ready',
						'ready_field' => 'last_name',
						'field_required' => 'yes',
					],
				],
				'title_field' => '{{{ field_type === "ready" ? ready_field : custom_key }}}',
			]
		);

		$this->end_controls_section();

		// تب استایل - فرم
		$this->start_controls_section(
			'nias_meta_style_form',
			[
				'label' => esc_html__('استایل فرم', 'nias-login-signup'),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'nias_meta_form_background',
				'types' => ['classic', 'gradient','video'],
				'selector' => '{{WRAPPER}} .nias-login-meta-modal',
			]
		);

		$this->add_responsive_control(
			'nias_meta_form_padding',
			[
				'label' => esc_html__('فاصله داخلی', 'nias-login-signup'),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'selectors' => [
					'{{WRAPPER}} .nias-login-meta-modal' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'nias_meta_form_border_radius',
			[
				'label' => esc_html__('گردی گوشه‌ها', 'nias-login-signup'),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors' => [
					'{{WRAPPER}} .nias-login-meta-modal' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// تب استایل - فیلدها
		$this->start_controls_section(
			'nias_meta_style_fields',
			[
				'label' => esc_html__('استایل فیلدها', 'nias-login-signup'),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);
		

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'nias_meta_field_typography',
				'selector' => '{{WRAPPER}} .nias-login-meta-modal input, {{WRAPPER}} .nias-login-meta-modal select',
			]
		);

		$this->add_control(
			'nias_meta_field_color',
			[
				'label' => esc_html__('رنگ متن', 'nias-login-signup'),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .nias-meta-form input, {{WRAPPER}} .nias-meta-form select' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'nias_meta_field_background',
			[
				'label' => esc_html__('رنگ پس‌زمینه', 'nias-login-signup'),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .nias-meta-form input, {{WRAPPER}} .nias-meta-form select' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name' => 'nias_meta_field_border',
				'selector' => '{{WRAPPER}} .nias-meta-form input, {{WRAPPER}} .nias-meta-form select',
			]
		);

		$this->add_responsive_control(
			'nias_meta_field_border_radius',
			[
				'label' => esc_html__('گردی گوشه‌ها', 'nias-login-signup'),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors' => [
					'{{WRAPPER}} .nias-meta-form input, {{WRAPPER}} .nias-meta-form select' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

						$this->add_control(
				'nias_meta_label',
				[
					'label' => esc_html__('استایل لیبل ها', 'nias-login-signup'),
					'type' => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				]
			);
				$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'nias_meta_label_typography',
				'selector' => '{{WRAPPER}} .nias-login-meta-modal label',
			]
		);

		$this->add_control(
			'nias_meta_label_color',
			[
				'label' => esc_html__('رنگ متن', 'nias-login-signup'),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .nias-meta-form label' => 'color: {{VALUE}};',
				],
			]
		);

		
						$this->add_control(
				'nias_meta_btn',
				[
					'label' => esc_html__('استایل دکمه', 'nias-login-signup'),
					'type' => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				]
			);

					$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'nias_meta_btn_typography',
				'selector' => '{{WRAPPER}} .nias-login-meta-modal .nias-login-meta-submit',
			]
		);

		$this->add_control(
			'nias_meta_btn_color',
			[
				'label' => esc_html__('رنگ متن', 'nias-login-signup'),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .nias-meta-form .nias-login-meta-submit' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'nias_meta_btn_background',
			[
				'label' => esc_html__('رنگ پس‌زمینه', 'nias-login-signup'),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .nias-meta-form .nias-login-meta-submit' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name' => 'nias_meta_btn_border',
				'selector' => '{{WRAPPER}} .nias-meta-form .nias-login-meta-submit',
			]
		);

		$this->add_responsive_control(
			'nias_meta_btn_border_radius',
			[
				'label' => esc_html__('گردی گوشه‌ها', 'nias-login-signup'),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors' => [
					'{{WRAPPER}} .nias-meta-form .nias-login-meta-submit' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render()
	{
		$settings = $this->get_settings_for_display();

		$ready_labels = [
			'first_name'          => 'نام',
			'last_name'           => 'نام خانوادگی',
			'description'         => 'بیوگرافی',
			'billing_first_name'  => 'نام صورتحساب',
			'billing_last_name'   => 'نام‌خانوادگی صورتحساب',
			'billing_company'     => 'شرکت',
			'billing_phone'       => 'تلفن صورتحساب',
			'billing_email'       => 'ایمیل صورتحساب',
			'billing_address_1'   => 'آدرس اول',
			'billing_address_2'   => 'آدرس دوم',
			'billing_city'        => 'شهر',
			'billing_state'       => 'استان',
			'billing_postcode'    => 'کدپستی',
			'billing_country'     => 'کشور',
			'shipping_first_name' => 'نام ارسال',
			'shipping_last_name'  => 'نام خانوادگی ارسال',
			'shipping_company'    => 'شرکت ارسال',
			'shipping_address_1'  => 'آدرس ارسال',
			'shipping_city'       => 'شهر ارسال',
			'shipping_state'      => 'استان ارسال',
			'shipping_postcode'   => 'کدپستی ارسال',
		];
		$ready_types = [
			'description'      => 'textarea',
			'billing_phone'    => 'tel',
			'billing_email'    => 'email',
			'billing_address_1'=> 'text',
			'billing_address_2'=> 'text',
		];

		// تبدیل تنظیمات به فرمت مورد نیاز
		$fields_arr = [];
		foreach ($settings['nias_meta_fields_list'] as $field) {
			if ($field['field_type'] === 'ready') {
				$key = $field['ready_field'];
				$fields_arr[] = [
					'key'      => $key,
					'label'    => $ready_labels[$key] ?? $key,
					'type'     => $ready_types[$key] ?? 'text',
					'required' => $field['field_required'] === 'yes',
				];
			} else {
				$fields_arr[] = [
					'key'           => $field['custom_key'],
					'label'         => $field['custom_label'] ?: $field['custom_key'],
					'type'          => $field['custom_field_type'],
					'required'      => $field['field_required'] === 'yes',
					'options'       => !empty($field['custom_options']) ? explode('|', $field['custom_options']) : [],
					'default_value' => $field['custom_default_value'] ?? '',
				];
			}
		}

		// ذخیره تنظیمات در options برای استفاده در سیستم
		update_option('nias-login-meta_fields', json_encode($fields_arr));
		update_option('nias-login-meta_roles', json_encode($settings['nias_meta_selected_roles']));
		update_option('nias-force-user-meta', $settings['nias_meta_force'] === 'yes' ? 1 : 0);

		// در حالت ویرایشگر المنتور، پیش‌نمایش ساده نمایش بده
		$is_editor_mode = isset(\Elementor\Plugin::$instance)
			&& (\Elementor\Plugin::$instance->editor->is_edit_mode()
				|| \Elementor\Plugin::$instance->preview->is_preview_mode());
		if ($is_editor_mode) {
			echo '<div style="border:2px dashed #1786FF;border-radius:10px;padding:16px 20px;direction:rtl;font-family:inherit;background:#f0f7ff;">';
			echo '<strong style="color:#1786FF;font-size:15px;">ویجت متادیتا کاربر نیاس</strong>';
			echo '<p style="margin:8px 0 6px;color:#555;font-size:13px;">' . count($fields_arr) . ' فیلد پیکربندی شده:</p>';
			echo '<ul style="margin:0;padding-right:18px;color:#333;font-size:13px;">';
			foreach ($fields_arr as $f) {
				echo '<li>' . esc_html($f['label']) . ' <span style="color:#999;">(' . esc_html($f['type']) . ($f['required'] ? ' – ضروری' : '') . ')</span></li>';
			}
			echo '</ul></div>';
			return;
		}
		?>
	<div class="nias-login-meta-overlay"><div class="nias-login-meta-modal">
		<div class="nias-meta-form">
			<h3 style="margin-top: 0;">تکمیل اطلاعات کاربری</h3>
			<form class="nias-login-meta-form">
				<?php foreach ($fields_arr as $field): ?>
					<div class="nias-login-meta-field" style="margin-bottom: 15px;">
						<label style="display: block; margin-bottom: 5px;">
							<?php echo esc_html($field['label']); ?>
							<?php if ($field['required']): ?><span style="color: red;">*</span><?php endif; ?>
						</label>
						<?php
						$req = $field['required'] ? 'required' : '';
						$def = esc_attr($field['default_value'] ?? '');
						if ($field['type'] === 'select' && !empty($field['options'])): ?>
							<select name="<?php echo esc_attr($field['key']); ?>" <?php echo $req; ?> style="width:100%;">
								<option value="">انتخاب کنید</option>
								<?php foreach ($field['options'] as $option): ?>
									<option value="<?php echo esc_attr($option); ?>" <?php selected($field['default_value'] ?? '', $option); ?>>
										<?php echo esc_html($option); ?>
									</option>
								<?php endforeach; ?>
							</select>
						<?php elseif ($field['type'] === 'textarea'): ?>
							<textarea name="<?php echo esc_attr($field['key']); ?>" <?php echo $req; ?> style="width:100%;min-height:80px;resize:vertical;"><?php echo esc_textarea($field['default_value'] ?? ''); ?></textarea>
						<?php else:
							$allowed_types = ['text', 'number', 'email', 'tel', 'date', 'url'];
							$safe_type = in_array($field['type'], $allowed_types, true) ? $field['type'] : 'text';
						?>
							<input type="<?php echo $safe_type; ?>" name="<?php echo esc_attr($field['key']); ?>" value="<?php echo $def; ?>" <?php echo $req; ?> style="width:100%;" />
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
				<button type="submit" class="nias-login-meta-submit">ذخیره</button>
			</form>
		</div>
	</div></div>
		<?php
	}
}
