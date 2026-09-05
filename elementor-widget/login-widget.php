<?php

class Elementor_niaslogin_widget extends \Elementor\Widget_Base
{

        public function get_name()
        {
                return 'nias_login_widget';
        }

        public function get_title()
        {
                return esc_html__('ورود / ثبت نام نیاس', 'nias-login-signup');
        }

        public function get_icon()
        {
                return 'eicon-upgrade-crown';
        }

        public function get_categories()
        {
                return ['nias-login'];
        }

        public function get_keywords()
        {
                return ['ورود / ثبت نام نیاس', 'Nias'];
        }

        public function get_custom_help_url()
        {
                return 'https://Nias.ir';
        }

        protected function get_upsale_data()
        {
                return ['nias.ir'];
        }

        public function get_style_depends()
        {
                [$url, $ver] = nias_login_asset('assets/css/style.css');
                wp_enqueue_style('nias-login-style', $url, [], $ver);

                return ['nias-login-style'];
        }

        public function get_script_depends()
        {
                // سیستم توست؛ script.js آن را به‌عنوان وابستگی دارد، پس ترتیب لود تضمین است
                [$url, $ver] = nias_login_asset('assets/js/nias-toast.js');
                wp_enqueue_script('nias-toast', $url, [], $ver, true);

                return ['nias-toast'];
        }

        /* -------------------------------------------------------------------------- */
        /*                               widget controls                              */
        /* -------------------------------------------------------------------------- */

        protected function register_controls()
        {
                $email_activate = get_option('nias_email_activate');
                $email_phone_activate = get_option('nias_email_phone_activate');
                $password_activate = get_option('nias_password_activate');
                $password_otp_activate = get_option('nias_password_otp_activate');

                $this->start_controls_section(
                        'nias_firstform',
                        [
                                'label' => esc_html__('فرم اول درج شماره', 'nias-login-signup'),
                                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                        ]
                );

                $this->add_control(
                        'nias_dontworry',
                        [
                                'type' => \Elementor\Controls_Manager::NOTICE,
                                'notice_type' => 'error',
                                'dismissible' => false,
                                'heading' => esc_html__('نگران نباشید', 'nias-login-signup'),
                                'content' => esc_html__('فرم ها فقط در حالت ویرایشگر بصورت یکجا نمایش داده میشوند تا به راحتی موارد را تغییر دهید', 'nias-login-signup'),
                        ]
                );
                $this->add_control(
                        'nias_learnaparat',
                        [
                                'type' => \Elementor\Controls_Manager::NOTICE,
                                'notice_type' => 'info',
                                'dismissible' => false,
                                'heading' => esc_html__('آموزش ها', 'nias-login-signup'),
                                'content' => wp_kses_post(__('حتماً <a style="text-decoration: underline;" href="https://www.aparat.com/playlist/24182337/" target="_blank">آموزش های تنظیم و استفاده از پلاگین را از آپارات مشاهده کنید</a>', 'nias-login-signup')),
                        ]
                );
                $this->add_control(
                        'nias_close_outbox',
                        [
                                'label' => esc_html__('با کلیک خارج از باکس بسته شود؟', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SWITCHER,
                                'label_on' => esc_html__('بله', 'nias-login-signup'),
                                'label_off' => esc_html__('خیر', 'nias-login-signup'),
                                'return_value' => 'yes',
                                'default' => 'yes',
                        ]
                );
                $this->add_control(
                        'nias_close_outnotice',
                        [
                                'type' => \Elementor\Controls_Manager::NOTICE,
                                'notice_type' => 'warning',
                                'dismissible' => false,
                                'heading' => esc_html__('نکته مهم در مورد بستن', 'nias-login-signup'),
                                'content' => esc_html__('در صورتی که از گزینه بالا استفاده کردید به المان هایی که درج میکنید z-index:9 بدهید', 'nias-login-signup'),
                        ]
                );

                $this->add_control(
                        'nias_close_button',
                        [
                                'label' => esc_html__('نمایش دکمه بستن', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SWITCHER,
                                'label_on' => esc_html__('بله', 'nias-login-signup'),
                                'label_off' => esc_html__('خیر', 'nias-login-signup'),
                                'return_value' => 'yes',
                                'default' => 'no',
                        ]
                );


                $this->add_control(
                        'nias_firstform_image',
                        [
                                'label' => esc_html__('تصویر ورود', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::MEDIA,
                                'default' => [
                                        'url' => \Elementor\Utils::get_placeholder_image_src(),
                                ],
                                'dynamic' => [
                                        'active' => true,
                                ],


                        ]
                );

                $this->add_control(
                        'nias_firstform_title',
                        [
                                'label' => esc_html__('عنوان', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::TEXT,
                                'default' => esc_html__('ورود / ثبت نام', 'nias-login-signup'),
                                'placeholder' => esc_html__('عنوان دلخواه را وارد کنید', 'nias-login-signup'),
                                'dynamic' => [
                                        'active' => true,
                                ],

                        ]
                );

                $this->add_control(
                        'nias_firstform_subtitle',
                        [
                                'label' => esc_html__('زیر عنوان', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::TEXT,
                                'default' => esc_html__('در صورت روشن بودن vpn خاموش کنید تا به مشکلی نخورید', 'nias-login-signup'),
                                'placeholder' => esc_html__('زیر عنوان دلخواه را وارد کنید', 'nias-login-signup'),
                                'dynamic' => [
                                        'active' => true,
                                ],

                        ]
                );

                $this->add_control(
                        'nias_firstform_label',
                        [
                                'label' => esc_html__('لیبل شماره موبایل/ایمیل', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::TEXT,
                                'default' => esc_html__('شماره همراه خود را وارد کنید', 'nias-login-signup'),
                                'placeholder' => esc_html__('متن ورود شماره موبایل', 'nias-login-signup'),
                                'dynamic' => [
                                        'active' => true,
                                ],

                        ]
                );
                if (!($email_activate) & !($email_phone_activate)) {
                        $this->add_control(
                                'nias_firstform_phonnumberimage',
                                [
                                        'label' => esc_html__('تصویر کنار فیلد شماره موبایل', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::MEDIA,
                                        'default' => [
                                                'url' => \Elementor\Utils::get_placeholder_image_src(),
                                        ],
                                        'dynamic' => [
                                                'active' => true,
                                        ],


                                ]
                        );

                        $this->add_control(
                                'nias_firstform_countrycode',
                                [
                                        'label' => esc_html__('کد کشور', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::TEXT,
                                        'default' => esc_html__('+98', 'nias-login-signup'),
                                        'dynamic' => [
                                                'active' => true,
                                        ],

                                ]
                        );

                        $this->add_control(
                                'nias_firstform_placeholder',
                                [
                                        'label' => esc_html__('متن پیشفرض شماره', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::TEXT,
                                        'default' => esc_html__('0 9 - - - - - - - - -', 'nias-login-signup'),
                                        'dynamic' => [
                                                'active' => true,
                                        ],

                                ]
                        );
                }


                $this->add_control(
                        'nias_firstform_button',
                        [
                                'label' => esc_html__('متن دکمه ارسال کد', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::TEXT,
                                'default' => esc_html__('ارسال کد تایید', 'nias-login-signup'),
                                'placeholder' => esc_html__('متن ارسال کد', 'nias-login-signup'),
                                'dynamic' => [
                                        'active' => true,
                                ],

                        ]
                );

                $this->add_control(
                        'nias_firstform_icon_load',
                        [
                                'label' => esc_html__('آیکون لود دکمه ارسال', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::ICONS,
                                'fa-regular' => [
                                        'circle',
                                        'dot-circle',
                                        'square-full',
                                ],
                        ]
                );

                $this->end_controls_section();


                /* -------------------------------------------------------------------------- */
                /*                           second tab form control                          */
                /* -------------------------------------------------------------------------- */

                $this->start_controls_section(
                        'nias_secondform',
                        [
                                'label' => esc_html__('فرم دوم تایید شماره/ایمیل', 'nias-login-signup'),
                                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                        ]
                );


                $this->add_control(
                        'nias_secondform_image',
                        [
                                'label' => esc_html__('تصویر بخش دوم', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::MEDIA,
                                'default' => [
                                        'url' => \Elementor\Utils::get_placeholder_image_src(),
                                ],
                                'dynamic' => [
                                        'active' => true,
                                ],


                        ]
                );
                if (!($password_activate)) {
                        $this->add_control(
                                'nias_secondform_title',
                                [
                                        'label' => esc_html__('عنوان ناحیه شماره', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::TEXT,
                                        'default' => esc_html__('تایید شماره همراه', 'nias-login-signup'),
                                        'placeholder' => esc_html__('عنوان دلخواه را وارد کنید', 'nias-login-signup'),
                                        'dynamic' => [
                                                'active' => true,
                                        ],

                                ]
                        );

                        $this->add_control(
                                'nias_secondform_subtitle',
                                [
                                        'label' => esc_html__('زیر عنوان ناحیه شماره', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::TEXT,
                                        'default' => esc_html__('', 'nias-login-signup'),
                                        'placeholder' => esc_html__('زیر عنوان دلخواه را وارد کنید', 'nias-login-signup'),
                                        'dynamic' => [
                                                'active' => true,
                                        ],

                                ]
                        );

                        $this->add_control(
                                'nias_secondform_label',
                                [
                                        'label' => esc_html__('لیبل کد تایید', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::TEXT,
                                        'default' => esc_html__('کد تایید را وارد کنید', 'nias-login-signup'),
                                        'placeholder' => esc_html__('متن کد تایید', 'nias-login-signup'),
                                        'dynamic' => [
                                                'active' => true,
                                        ],

                                ]
                        );
                }

                $this->add_control(
                        'nias_secondform_button',
                        [
                                'label' => esc_html__('متن تایید کد/پسورد', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::TEXT,
                                'default' => esc_html__('تایید کد', 'nias-login-signup'),
                                'placeholder' => esc_html__('متن تایید کد', 'nias-login-signup'),
                                'dynamic' => [
                                        'active' => true,
                                ],

                        ]
                );


                if (($password_otp_activate) || ($password_activate)) {
                        $this->add_control(
                                'nias_secondform_passwordtitle',
                                [
                                        'label' => esc_html__('تایتل پسورد', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::TEXT,
                                        'default' => esc_html__('پسورد را وارد کنید', 'nias-login-signup'),
                                        'placeholder' => esc_html__('عنوانی برای بخش پسورد', 'nias-login-signup'),
                                        'dynamic' => [
                                                'active' => true,
                                        ],

                                ]
                        );
                        $this->add_control(
                                'nias_secondform_passwordsubtitle',
                                [
                                        'label' => esc_html__('زیر تایتل پسورد', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::TEXT,
                                        'default' => esc_html__('چنانچه پسوردی از قبل دارید وارد کنید', 'nias-login-signup'),
                                        'placeholder' => esc_html__('زیر عنوانی برای بخش پسورد', 'nias-login-signup'),
                                        'dynamic' => [
                                                'active' => true,
                                        ],

                                ]
                        );
                        $this->add_control(
                                'nias_secondform_passwordlabel',
                                [
                                        'label' => esc_html__('لیبل رمز', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::TEXT,
                                        'default' => esc_html__('رمز عبور', 'nias-login-signup'),
                                        'placeholder' => esc_html__('متن رمز عبور', 'nias-login-signup'),
                                        'dynamic' => [
                                                'active' => true,
                                        ],

                                ]
                        );

                        $this->add_control(
                                'nias_secondform_passwordbtn',
                                [
                                        'label' => esc_html__('متن فراموشی رمز', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::TEXT,
                                        'default' => esc_html__('فراموشی رمز / ارسال رمز', 'nias-login-signup'),
                                        'placeholder' => esc_html__('متن فراموشی رمز', 'nias-login-signup'),
                                        'dynamic' => [
                                                'active' => true,
                                        ],

                                ]
                        );

                        $this->add_control(
                                'nias_secondform_passwordunderbtn',
                                [
                                        'label' => esc_html__('متن زیر دکمه رمز', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::TEXT,
                                        'default' => esc_html__('رمز عبور جدید برای شما ارسال خواهد شد', 'nias-login-signup'),
                                        'placeholder' => esc_html__('اطلاعاتی در مورد دکمه', 'nias-login-signup'),
                                        'dynamic' => [
                                                'active' => true,
                                        ],

                                ]
                        );

                }

                $this->add_control(
                        'nias_secondform_editbutton',
                        [
                                'label' => esc_html__('متن اصلاح شماره موبایل/ایمیل', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::TEXT,
                                'default' => esc_html__('اصلاح شماره', 'nias-login-signup'),
                                'placeholder' => esc_html__('متن اصلاح شماره/ایمیل', 'nias-login-signup'),
                                'dynamic' => [
                                        'active' => true,
                                ],

                        ]
                );

                $this->end_controls_section();


                /* -------------------------------------------------------------------------- */
                /*                        success section content control                      */
                /* -------------------------------------------------------------------------- */

                $this->start_controls_section(
                        'nias_successform',
                        [
                                'label' => esc_html__('بخش ورود موفقیت آمیز', 'nias-login-signup'),
                                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                        ]
                );

                $this->add_control(
                        'nias_success_notice',
                        [
                                'type' => \Elementor\Controls_Manager::NOTICE,
                                'notice_type' => 'info',
                                'dismissible' => false,
                                'heading' => esc_html__('بخش ورود موفق', 'nias-login-signup'),
                                'content' => esc_html__('این بخش پس از ورود موفق کاربر و قبل از بارگذاری مجدد صفحه نمایش داده می‌شود', 'nias-login-signup'),
                        ]
                );

                $this->add_control(
                        'nias_show_success',
                        [
                                'label' => esc_html__('نمایش بخش ورود موفقیت آمیز جهت ادیت', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SWITCHER,
                                'label_on' => esc_html__('بله', 'nias-login-signup'),
                                'label_off' => esc_html__('خیر', 'nias-login-signup'),
                                'return_value' => 'yes',
                                'default' => 'no',
                        ]
                );

                $this->add_control(
                        'nias_success_show_icon',
                        [
                                'label' => esc_html__('نمایش آیکون موفقیت', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SWITCHER,
                                'label_on' => esc_html__('بله', 'nias-login-signup'),
                                'label_off' => esc_html__('خیر', 'nias-login-signup'),
                                'return_value' => 'yes',
                                'default' => 'no',
                        ]
                );

                $this->add_control(
                        'nias_success_icon',
                        [
                                'label' => esc_html__('آیکون موفقیت', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::ICONS,
                                'default' => [
                                        'value' => 'fas fa-check-circle',
                                        'library' => 'fa-solid',
                                ],
                                'condition' => [
                                        'nias_success_show_icon' => 'yes',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_secondform_successtext',
                        [
                                'label' => esc_html__('متن ورود موفقیت آمیز', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::TEXT,
                                'default' => esc_html__('ورود با موفقیت انجام شد ', 'nias-login-signup'),
                                'dynamic' => [
                                        'active' => true,
                                ],

                        ]
                );

                $this->add_control(
                        'nias_success_show_loader',
                        [
                                'label' => esc_html__('نمایش لودر و متن زیر آن', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SWITCHER,
                                'label_on' => esc_html__('بله', 'nias-login-signup'),
                                'label_off' => esc_html__('خیر', 'nias-login-signup'),
                                'return_value' => 'yes',
                                'default' => 'yes',
                        ]
                );

                $this->add_control(
                        'nias_secondform_successloadertext',
                        [
                                'label' => esc_html__('متن زیر لودر', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::TEXT,
                                'default' => esc_html__('صفحه مجدداً بارگذاری میشود، ممنون از صبوری شما', 'nias-login-signup'),
                                'dynamic' => [
                                        'active' => true,
                                ],
                                'condition' => [
                                        'nias_success_show_loader' => 'yes',
                                ],

                        ]
                );

                $this->add_control(
                        'nias_secondform_successloader',
                        [
                                'label' => esc_html__('آیکون لود موفقیت امیز', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::ICONS,
                                'fa-regular' => [
                                        'circle',
                                        'dot-circle',
                                        'square-full',
                                ],
                                'condition' => [
                                        'nias_success_show_loader' => 'yes',
                                ],
                        ]
                );

                $this->end_controls_section();



                /* -------------------------------------------------------------------------- */
                /*                             style tab of widget                            */
                /* -------------------------------------------------------------------------- */



                $this->start_controls_section(
                        'nias_login_style',
                        [
                                'label' => esc_html__('استایل باکس و بک گراند', 'nias-login-signup'),
                                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                        ]
                );

                $this->add_group_control(
                        \Elementor\Group_Control_Background::get_type(),
                        [
                                'name' => 'nias_style_form_background',
                                'types' => ['classic', 'gradient', 'video'],
                                'selector' => '{{WRAPPER}} .nias-main-modal',
                        ]
                );

                $this->add_responsive_control(
                        'nias_login_style_width',
                        [
                                'label' => esc_html__('عرض باکس لاگین', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SLIDER,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'range' => [
                                        'px' => [
                                                'min' => 0,
                                                'max' => 1000,
                                                'step' => 1,
                                        ],
                                        '%' => [
                                                'min' => 0,
                                                'max' => 100,
                                        ],
                                ],
                                'default' => [
                                        'unit' => 'px',
                                        'size' => 360,
                                ],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal.nias-elementor' => 'width: {{SIZE}}{{UNIT}}!important;',
                                ],
                        ]
                );
                $this->add_responsive_control(
                        'nias_login_style_border_radius',
                        [
                                'label' => esc_html__('گردی گوشه ها', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'default' => [
                                        'top' => 10,
                                        'right' => 10,
                                        'bottom' => 10,
                                        'left' => 10,
                                        'unit' => 'px',
                                        'isLinked' => true,
                                ],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal.nias-elementor' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );
                $this->add_responsive_control(
                        'nias_login_style_padding',
                        [
                                'label' => esc_html__('فاصله داخلی', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'default' => [
                                        'top' => 10,
                                        'right' => 10,
                                        'bottom' => 10,
                                        'left' => 10,
                                        'unit' => 'px',
                                        'isLinked' => true,
                                ],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal.nias-elementor' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );
                $this->add_responsive_control(
                        'nias_login_style_margin',
                        [
                                'label' => esc_html__('فاصله خارجی', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'default' => [
                                        'top' => 10,
                                        'right' => 10,
                                        'bottom' => 10,
                                        'left' => 10,
                                        'unit' => 'px',
                                        'isLinked' => true,
                                ],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal.nias-elementor' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_group_control(
                        \Elementor\Group_Control_Border::get_type(),
                        [
                                'name' => 'main_border_niaslogin',
                                'selector' => '{{WRAPPER}} .nias-main-modal',
                        ]
                );

                $this->add_control(
                        'nias_closeformlogin',
                        [
                                'label' => esc_html__('استایل دکمه بستن', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::HEADING,
                                'separator' => 'before',
                        ]
                );
                $this->add_responsive_control(
                        'nias_closeformlogin_style_border_radius',
                        [
                                'label' => esc_html__('گردی گوشه ها', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-close-modal' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );
                $this->add_group_control(
                        \Elementor\Group_Control_Border::get_type(),
                        [
                                'name' => 'nias_closeformlogin_border',
                                'selector' => '{{WRAPPER}} .nias-close-modal',
                        ]
                );

                $this->add_control(
                        'nias_closeformlogin_background',
                        [
                                'label' => esc_html__('بک گراند دکمه بستن', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-close-modal' => 'background: {{VALUE}};',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_closeformlogin_color',
                        [
                                'label' => esc_html__('رنگ متن دکمه بستن', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-close-modal' => 'color: {{VALUE}};',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_closeformlogin_iconcolor',
                        [
                                'label' => esc_html__('رنگ آیکون بستن', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-close-modal svg path' => 'stroke: {{VALUE}} !important;',
                                ],
                        ]
                );

                $this->add_group_control(
                        \Elementor\Group_Control_Typography::get_type(),
                        [
                                'name' => 'nias_closeformlogin_typography',
                                'label' => esc_html__('تایپوگرافی دکمه بستن', 'nias-login-signup'),
                                'selector' => '{{WRAPPER}} .nias-close-modal',
                        ]
                );

                $this->add_responsive_control(
                        'nias_closeformlogin_padding',
                        [
                                'label' => esc_html__('فاصله داخلی دکمه بستن', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-close-modal' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_closeformlogin_hoverbackground',
                        [
                                'label' => esc_html__('بک گراند هاور بستن', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-close-modal:hover' => 'background: {{VALUE}} !important;',
                                ],
                        ]
                );
                // موقعیت
                $this->add_control(
                        'nias_closeformlogin_position',
                        [
                                'label' => esc_html__('موقعیت', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SELECT,
                                'default' => 'fixed',
                                'options' => [
                                        'relative' => esc_html__('نسبی', 'nias-login-signup'),
                                        'absolute' => esc_html__('مطلق', 'nias-login-signup'),
                                        'fixed' => esc_html__('ثابت', 'nias-login-signup'),
                                        'sticky' => esc_html__('چسبنده', 'nias-login-signup'),
                                ],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-close-modal' => 'position: {{VALUE}};',
                                ],
                        ]
                );

                // فاصله از بالا
                $this->add_responsive_control(
                        'nias_closeformlogin_position_top',
                        [
                                'label' => esc_html__('فاصله از بالا', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SLIDER,
                                'size_units' => ['px', '%', 'em', 'rem', 'vh', 'vw'],
                                'range' => [
                                        'px' => [
                                                'min' => -1000,
                                                'max' => 1000,
                                        ],
                                ],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-close-modal' => 'top: {{SIZE}}{{UNIT}};',
                                ],
                                'condition' => [
                                        'nias_closeformlogin_position' => ['absolute', 'fixed', 'relative'],
                                ],
                        ]
                );

                // فاصله از راست
                $this->add_responsive_control(
                        'nias_closeformlogin_position_right',
                        [
                                'label' => esc_html__('فاصله از راست', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SLIDER,
                                'size_units' => ['px', '%', 'em', 'rem', 'vh', 'vw'],
                                'range' => [
                                        'px' => [
                                                'min' => -1000,
                                                'max' => 1000,
                                        ],
                                ],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-close-modal' => 'right: {{SIZE}}{{UNIT}};',
                                ],
                                'condition' => [
                                        'nias_closeformlogin_position' => ['absolute', 'fixed', 'relative'],
                                ],
                        ]
                );

                // فاصله از پایین
                $this->add_responsive_control(
                        'nias_closeformlogin_position_bottom',
                        [
                                'label' => esc_html__('فاصله از پایین', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SLIDER,
                                'size_units' => ['px', '%', 'em', 'rem', 'vh', 'vw'],
                                'range' => [
                                        'px' => [
                                                'min' => -1000,
                                                'max' => 1000,
                                        ],
                                ],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-close-modal' => 'bottom: {{SIZE}}{{UNIT}};',
                                ],
                                'condition' => [
                                        'nias_closeformlogin_position' => ['absolute', 'fixed', 'relative'],
                                ],
                        ]
                );

                // فاصله از چپ
                $this->add_responsive_control(
                        'nias_closeformlogin_position_left',
                        [
                                'label' => esc_html__('فاصله از چپ', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SLIDER,
                                'size_units' => ['px', '%', 'em', 'rem', 'vh', 'vw'],
                                'range' => [
                                        'px' => [
                                                'min' => -1000,
                                                'max' => 1000,
                                        ],
                                ],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-close-modal' => 'left: {{SIZE}}{{UNIT}};',
                                ],
                                'condition' => [
                                        'nias_closeformlogin_position' => ['absolute', 'fixed', 'relative'],
                                ],
                        ]
                );

                $this->end_controls_section();

                $this->start_controls_section(
                        'nias_login_firstform_style',
                        [
                                'label' => esc_html__('استایل فرم اول', 'nias-login-signup'),
                                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                        ]
                );



                $this->add_responsive_control(
                        'nias_firstform_logo_width',
                        [
                                'label' => esc_html__('عرض لوگوی فرم اول', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SLIDER,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'range' => [
                                        'px' => [
                                                'min' => 0,
                                                'max' => 1000,
                                                'step' => 1,
                                        ],
                                        '%' => [
                                                'min' => 0,
                                                'max' => 100,
                                        ],
                                ],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-login-form .nias-login-form-logo img' => 'width: {{SIZE}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_firstform_titlestyles',
                        [
                                'label' => esc_html__('استایل عنوان', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::HEADING,
                                'separator' => 'before',
                        ]
                );

                $this->add_group_control(
                        \Elementor\Group_Control_Typography::get_type(),
                        [
                                'name' => 'firstform_title_typography',
                                'selector' => '{{WRAPPER}} .nias-modal-title.first-form',
                        ]
                );
                $this->add_control(
                        'firstform_title_typography_align',
                        [
                                'label' => esc_html__('چیدمان', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::CHOOSE,
                                'options' => [
                                        'right' => [
                                                'title' => esc_html__('راست چین', 'nias-login-signup'),
                                                'icon' => 'eicon-text-align-right',
                                        ],

                                        'center' => [
                                                'title' => esc_html__('وسط چین', 'nias-login-signup'),
                                                'icon' => 'eicon-text-align-center',
                                        ],

                                        'left' => [
                                                'title' => esc_html__('چپ چین', 'nias-login-signup'),
                                                'icon' => 'eicon-text-align-left',
                                        ],

                                ],
                                'default' => 'center',
                                'toggle' => true,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-modal-title.first-form' => 'text-align: {{VALUE}};',
                                ],
                        ]
                );
                $this->add_control(
                        'firstform_title_color',
                        [
                                'label' => esc_html__('رنگ عنوان', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-modal-title.first-form' => 'color: {{VALUE}}',
                                ],
                        ]
                );
                $this->add_responsive_control(
                        'firstform_title_margin',
                        [
                                'label' => esc_html__('فاصله از اطراف', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'default' => [
                                        'top' => 5,
                                        'right' => 0,
                                        'bottom' => 5,
                                        'left' => 0,
                                        'unit' => 'px',
                                        'isLinked' => false,
                                ],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-modal-title.first-form' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_firstform_subtitlestyles',
                        [
                                'label' => esc_html__('استایل زیر عنوان', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::HEADING,
                                'separator' => 'before',
                        ]
                );


                $this->add_group_control(
                        \Elementor\Group_Control_Typography::get_type(),
                        [
                                'name' => 'firstform_subtitle_typography',
                                'selector' => '{{WRAPPER}} .nias-subtitle',
                        ]
                );
                $this->add_control(
                        'firstform_subtitle_typography_align',
                        [
                                'label' => esc_html__('چیدمان زیر عنوان', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::CHOOSE,
                                'options' => [
                                        'right' => [
                                                'title' => esc_html__('راست چین', 'nias-login-signup'),
                                                'icon' => 'eicon-text-align-right',
                                        ],

                                        'center' => [
                                                'title' => esc_html__('وسط چین', 'nias-login-signup'),
                                                'icon' => 'eicon-text-align-center',
                                        ],

                                        'left' => [
                                                'title' => esc_html__('چپ چین', 'nias-login-signup'),
                                                'icon' => 'eicon-text-align-left',
                                        ],

                                ],
                                'default' => 'center',
                                'toggle' => true,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-subtitle' => 'text-align: {{VALUE}};',
                                ],
                        ]
                );
                $this->add_control(
                        'firstform_subtitle_color',
                        [
                                'label' => esc_html__('رنگ زیر عنوان', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-subtitle' => 'color: {{VALUE}}',
                                ],
                        ]
                );



                $this->add_control(
                        'nias_firstform_labelstyles',
                        [
                                'label' => esc_html__('استایل لیبل فیلد', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::HEADING,
                                'separator' => 'before',
                        ]
                );


                $this->add_group_control(
                        \Elementor\Group_Control_Typography::get_type(),
                        [
                                'name' => 'firstform_numberlabel_typography',
                                'selector' => '{{WRAPPER}} .nias-login-field label',
                        ]
                );
                if (!($email_activate) & !($email_phone_activate)) {

                        $this->add_control(
                                'firstform_numberlabel_typography_align',
                                [
                                        'label' => esc_html__('چیدمان لیبل شماره', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::CHOOSE,
                                        'options' => [
                                                'right' => [
                                                        'title' => esc_html__('راست چین', 'nias-login-signup'),
                                                        'icon' => 'eicon-text-align-right',
                                                ],

                                                'center' => [
                                                        'title' => esc_html__('وسط چین', 'nias-login-signup'),
                                                        'icon' => 'eicon-text-align-center',
                                                ],

                                                'left' => [
                                                        'title' => esc_html__('چپ چین', 'nias-login-signup'),
                                                        'icon' => 'eicon-text-align-left',
                                                ],

                                        ],
                                        'default' => 'center',
                                        'toggle' => true,
                                        'selectors' => [
                                                '{{WRAPPER}} .nias-login-field label' => 'text-align: {{VALUE}}!important;',
                                        ],
                                ]
                        );
                }
                $this->add_control(
                        'firstform_label_color',
                        [
                                'label' => esc_html__('رنگ لیبل شماره', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-login-field label' => 'color: {{VALUE}}',
                                ],
                        ]
                );

                if (($email_activate) || ($email_phone_activate) || ($password_activate) || ($password_otp_activate)) {

                        $this->add_control(
                                'nias_firstform_nslabelstyles',
                                [
                                        'label' => esc_html__('استایل لیبل شناور', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::HEADING,
                                        'separator' => 'before',
                                ]
                        );
                        $this->add_control(
                                'nias_notice_labels',
                                [
                                        'type' => \Elementor\Controls_Manager::NOTICE,
                                        'notice_type' => 'warning',
                                        'dismissible' => false,
                                        'heading' => esc_html__('استایل لیبل ها', 'nias-login-signup'),
                                        'content' => esc_html__('استایل لیبل ها در فرم اول و دوم یکسان است ', 'nias-login-signup'),
                                ]
                        );

                        $this->add_control(
                                'firstform_nslabel_color',
                                [
                                        'label' => esc_html__('رنگ لیبل', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::COLOR,
                                        'default' => '#8d8d8d',
                                        'selectors' => [
                                                '{{WRAPPER}} .nias-main-modal .nsinput-field .ns-radio-label' => 'color: {{VALUE}}',
                                        ],
                                ]
                        );

                        $this->add_control(
                                'firstform_nslabel_background',
                                [
                                        'label' => esc_html__('بک‌گراند لیبل', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::COLOR,
                                        'default' => 'transparent',
                                        'selectors' => [
                                                '{{WRAPPER}} .nias-main-modal .nsinput-field .ns-radio-label' => 'background-color: {{VALUE}}',
                                        ],
                                ]
                        );

                        $this->add_responsive_control(
                                'firstform_nslabel_right',
                                [
                                        'label' => esc_html__('فاصله از راست', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::SLIDER,
                                        'size_units' => ['px'],
                                        'range' => ['px' => ['min' => 0, 'max' => 200, 'step' => 1]],
                                        'default' => ['unit' => 'px', 'size' => 9],
                                        'selectors' => [
                                                '{{WRAPPER}} .nias-main-modal .nsinput-field .ns-radio-label' => 'right: {{SIZE}}{{UNIT}};',
                                        ],
                                ]
                        );

                        $this->add_responsive_control(
                                'firstform_nslabel_top',
                                [
                                        'label' => esc_html__('فاصله از بالا', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::SLIDER,
                                        'size_units' => ['px'],
                                        'range' => ['px' => ['min' => 0, 'max' => 200, 'step' => 1]],
                                        'default' => ['unit' => 'px', 'size' => 0],
                                        'selectors' => [
                                                '{{WRAPPER}} .nias-main-modal .nsinput-field .ns-radio-label' => 'top: {{SIZE}}{{UNIT}};',
                                        ],
                                ]
                        );


                        $this->add_responsive_control(
                                'firstform_nslabel_translate_y',
                                [
                                        'label' => esc_html__('ترنسفورم Y لیبل', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::SLIDER,
                                        'size_units' => ['px'],
                                        'range' => ['px' => ['min' => -100, 'max' => 100, 'step' => 1]],
                                        'default' => ['unit' => 'px', 'size' => 7],
                                        'selectors' => [
                                                '{{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field label.ns-radio-label' => '--nias-label-ty: {{SIZE}}{{UNIT}};',
                                        ],
                                ]
                        );

                        $this->add_responsive_control(
                                'firstform_nslabel_translate_x',
                                [
                                        'label' => esc_html__('ترنسفورم X لیبل', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::SLIDER,
                                        'size_units' => ['px'],
                                        'range' => ['px' => ['min' => -100, 'max' => 100, 'step' => 1]],
                                        'default' => ['unit' => 'px', 'size' => 0],
                                        'selectors' => [
                                                '{{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field label.ns-radio-label' => '--nias-label-tx: {{SIZE}}{{UNIT}}',
                                        ],
                                ]
                        );

                        $this->add_control(
                                'firstform_nslabel_scale',
                                [
                                        'label' => esc_html__('اسکیل لیبل', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::SLIDER,
                                        'size_units' => ['px'],
                                        'range' => ['px' => ['min' => 0.5, 'max' => 2, 'step' => 0.01]],
                                        'default' => ['unit' => 'px', 'size' => 1],
                                        'selectors' => [
                                                '{{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field label.ns-radio-label' => '--nias-label-sc: {{SIZE}}',
                                        ],
                                ]
                        );

                        $this->add_responsive_control(
                                'firstform_nslabel_padding',
                                [
                                        'label' => esc_html__('فاصله داخلی لیبل', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                        'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                        'selectors' => [
                                                '{{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field label.ns-radio-label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                                        ],
                                ]
                        );

                        $this->add_group_control(
                                \Elementor\Group_Control_Border::get_type(),
                                [
                                        'name' => 'firstform_nslabel_border',
                                        'selector' => '{{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field label.ns-radio-label',
                                ]
                        );

                        $this->add_responsive_control(
                                'firstform_nslabel_borderradius',
                                [
                                        'label' => esc_html__('گردی گوشه‌های لیبل', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                        'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                        'selectors' => [
                                                '{{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field label.ns-radio-label' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                                        ],
                                ]
                        );

                        $this->add_control(
                                'nias_firstform_nslabelstyles_focus',
                                [
                                        'label' => esc_html__('استایل لیبل در فوکوس/اعتبار', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::HEADING,
                                        'separator' => 'before',
                                ]
                        );

                        $this->add_control(
                                'firstform_nslabel_focus_background',
                                [
                                        'label' => esc_html__('بک‌گراند فوکوس لیبل', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::COLOR,
                                        'default' => '#ffffff',
                                        'selectors' => [
                                                '{{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="text"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="password"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="tel"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="email"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="text"]:valid ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="password"]:valid ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="tel"]:valid ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="email"]:not(:placeholder-shown) ~ label.ns-radio-label' => 'background-color: {{VALUE}} !important;',
                                        ],
                                ]
                        );

                        $this->add_control(
                                'firstform_nslabel_focus_color',
                                [
                                        'label' => esc_html__('رنگ متن فوکوس لیبل', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::COLOR,
                                        'default' => '#0034de',
                                        'selectors' => [
                                                '{{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="text"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="password"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="tel"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="email"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="text"]:valid ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="password"]:valid ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="tel"]:valid ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="email"]:not(:placeholder-shown) ~ label.ns-radio-label' => 'color: {{VALUE}} !important;',
                                        ],
                                ]
                        );

                        $this->add_responsive_control(
                                'firstform_nslabel_focus_padding',
                                [
                                        'label' => esc_html__('فاصله داخلی فوکوس لیبل', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                        'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                        'default' => ['top' => 0, 'right' => 5, 'bottom' => 0, 'left' => 5, 'unit' => 'px', 'isLinked' => false],
                                        'selectors' => [
                                                '{{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="text"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="password"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="tel"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="email"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="text"]:valid ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="password"]:valid ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="tel"]:valid ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="email"]:not(:placeholder-shown) ~ label.ns-radio-label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                                        ],
                                ]
                        );

                        $this->add_responsive_control(
                                'firstform_nslabel_focus_borderradius',
                                [
                                        'label' => esc_html__('گردی گوشه‌های فوکوس لیبل', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                        'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                        'default' => ['top' => 100, 'right' => 100, 'bottom' => 100, 'left' => 100, 'unit' => 'px', 'isLinked' => true],
                                        'selectors' => [
                                                '{{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="text"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="password"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="tel"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="email"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="text"]:valid ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="password"]:valid ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="tel"]:valid ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="email"]:not(:placeholder-shown) ~ label.ns-radio-label' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                                        ],
                                ]
                        );

                        $this->add_control(
                                'firstform_nslabel_focus_letterspacing',
                                [
                                        'label' => esc_html__('فاصله حروف فوکوس', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::SLIDER,
                                        'size_units' => ['px'],
                                        'range' => ['px' => ['min' => 0, 'max' => 5, 'step' => 0.1]],
                                        'default' => ['unit' => 'px', 'size' => 1],
                                        'selectors' => [
                                                '{{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="text"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="password"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="tel"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="email"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="text"]:valid ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="password"]:valid ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="tel"]:valid ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="email"]:not(:placeholder-shown) ~ label.ns-radio-label' => 'letter-spacing: {{SIZE}}{{UNIT}} !important;',
                                        ],
                                ]
                        );



                        $this->add_responsive_control(
                                'firstform_nslabel_focus_translate_y',
                                [
                                        'label' => esc_html__('ترنسفورم Y فوکوس', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::SLIDER,
                                        'size_units' => ['px'],
                                        'range' => ['px' => ['min' => -100, 'max' => 100, 'step' => 1]],
                                        'default' => ['unit' => 'px', 'size' => -14],
                                        'selectors' => [
                                                '{{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="text"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="password"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="tel"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="email"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="text"]:valid ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="password"]:valid ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="tel"]:valid ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="email"]:not(:placeholder-shown) ~ label.ns-radio-label' => '--nias-label-ty-f: {{SIZE}}{{UNIT}};',
                                        ],
                                ]
                        );

                        $this->add_responsive_control(
                                'firstform_nslabel_focus_translate_x',
                                [
                                        'label' => esc_html__('ترنسفورم X فوکوس', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::SLIDER,
                                        'size_units' => ['px'],
                                        'range' => ['px' => ['min' => -100, 'max' => 100, 'step' => 1]],
                                        'default' => ['unit' => 'px', 'size' => -5],
                                        'selectors' => [
                                                '{{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="text"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="password"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="tel"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="email"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="text"]:valid ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="password"]:valid ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="tel"]:valid ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="email"]:not(:placeholder-shown) ~ label.ns-radio-label' => '--nias-label-tx-f: {{SIZE}}{{UNIT}} !important;',
                                        ],
                                ]
                        );

                        $this->add_control(
                                'firstform_nslabel_focus_scale',
                                [
                                        'label' => esc_html__('اسکیل فوکوس لیبل', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::SLIDER,
                                        'size_units' => ['px'],
                                        'range' => ['px' => ['min' => 0.5, 'max' => 2, 'step' => 0.01]],
                                        'default' => ['unit' => 'px', 'size' => 0.7],
                                        'selectors' => [
                                                '{{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="text"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="password"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="tel"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="email"]:focus ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="text"]:valid ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="password"]:valid ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="tel"]:valid ~ label.ns-radio-label, {{WRAPPER}} .nias-main-modal.nias-elementor .nsinput-field input[type="email"]:not(:placeholder-shown) ~ label.ns-radio-label' => '--nias-label-sc-f: {{SIZE}} !important;',
                                        ],
                                ]
                        );
                }


                $this->add_control(
                        'nias_firstform_input',
                        [
                                'label' => esc_html__('استایل ناحیه شماره موبایل', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::HEADING,
                                'separator' => 'before',
                        ]
                );

                $this->add_group_control(
                        \Elementor\Group_Control_Background::get_type(),
                        [
                                'name' => 'nias_firstform_backgroundinput',
                                'types' => ['classic', 'gradient', 'video'],
                                'selector' => '{{WRAPPER}} .niasphoneinput , {{WRAPPER}} #nias_email_input , {{WRAPPER}} #nias_email_phone_input',
                        ]
                );
                $this->add_control(
                        'nias_firstform_colorinput',
                        [
                                'label' => esc_html__('رنگ متن داخل ناحیه', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .niasphoneinput , {{WRAPPER}} #nias_email_input , {{WRAPPER}} #nias_email_phone_input' => 'color: {{VALUE}}',
                                ],
                        ]
                );

                $this->add_responsive_control(
                        'nias_firstform_input_borderradius',
                        [
                                'label' => esc_html__('گردی گوشه ها', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'default' => [
                                        'top' => 10,
                                        'right' => 10,
                                        'bottom' => 10,
                                        'left' => 10,
                                        'unit' => 'px',
                                        'isLinked' => true,
                                ],
                                'selectors' => [
                                        '{{WRAPPER}} .niasphoneinput , {{WRAPPER}} #nias_email_input , {{WRAPPER}} #nias_email_phone_input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );
                $this->add_group_control(
                        \Elementor\Group_Control_Border::get_type(),
                        [
                                'name' => 'main_border_nias_firstform_input',
                                'selector' => '{{WRAPPER}} .niasphoneinput , {{WRAPPER}} #nias_email_input , {{WRAPPER}} #nias_email_phone_input',
                        ]
                );

                $this->add_responsive_control(
                        'nias_firstform_input_paddinginside',
                        [
                                'label' => esc_html__('انداره داخلی فیلد درونی', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'selectors' => [
                                        '{{WRAPPER}} .niasphoneinput , {{WRAPPER}} #nias_email_input , {{WRAPPER}} #nias_email_phone_input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );

                /* ---------- اندازه و ارتفاع فیلدها (راه‌حل ارتفاع پایدار) ---------- */
                $this->add_control(
                        'nias_input_size_heading',
                        [
                                'label' => esc_html__('اندازه و ارتفاع فیلدها', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::HEADING,
                                'separator' => 'before',
                        ]
                );

                $this->add_control(
                        'nias_input_size_notice',
                        [
                                'type' => \Elementor\Controls_Manager::NOTICE,
                                'notice_type' => 'info',
                                'dismissible' => false,
                                'content' => esc_html__('برای جلوگیری از خراب شدن ارتفاع فیلدها در برخی قالب‌ها، از حداقل ارتفاع و فاصله داخلی زیر استفاده کنید.', 'nias-login-signup'),
                        ]
                );

                $this->add_responsive_control(
                        'nias_input_min_height',
                        [
                                'label' => esc_html__('حداقل ارتفاع فیلدها', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SLIDER,
                                'size_units' => ['px', 'em', 'rem'],
                                'range' => ['px' => ['min' => 0, 'max' => 120, 'step' => 1]],
                                'default' => ['unit' => 'px', 'size' => 52],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal .nsinput-field input, {{WRAPPER}} .niasphoneinput, {{WRAPPER}} #nias_email_input, {{WRAPPER}} #nias_email_phone_input' => '--nias-input-min-height: {{SIZE}}{{UNIT}}; min-height: {{SIZE}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_responsive_control(
                        'nias_input_padding_box',
                        [
                                'label' => esc_html__('فاصله داخلی فیلدها', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', 'em', 'rem'],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal .nsinput-field input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_responsive_control(
                        'nias_input_line_height',
                        [
                                'label' => esc_html__('ارتفاع خط فیلدها', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SLIDER,
                                'size_units' => ['px', 'em', ''],
                                'range' => [
                                        'px' => ['min' => 10, 'max' => 60, 'step' => 1],
                                        'em' => ['min' => 1, 'max' => 3, 'step' => 0.1],
                                        ''   => ['min' => 1, 'max' => 3, 'step' => 0.1],
                                ],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal .nsinput-field input' => 'line-height: {{SIZE}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_firstform_inputfocus',
                        [
                                'label' => esc_html__('استایل ناحیه شماره موبایل در حالت انتخاب', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::HEADING,
                                'separator' => 'before',
                        ]
                );
                $this->add_group_control(
                        \Elementor\Group_Control_Background::get_type(),
                        [
                                'name' => 'nias_firstform_backgroundinputfocus',
                                'types' => ['classic', 'gradient', 'video'],
                                'selector' => ' {{WRAPPER}} .niasphoneinput:focus , {{WRAPPER}} #nias_email_input:focus , {{WRAPPER}} #nias_email_phone_input:focus',
                        ]
                );
                $this->add_control(
                        'nias_firstform_colorinputfocus',
                        [
                                'label' => esc_html__('رنگ متن داخل ناحیه', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} {{WRAPPER}} .niasphoneinput:focus , {{WRAPPER}} #nias_email_input:focus , {{WRAPPER}} #nias_email_phone_input:focus' => 'color: {{VALUE}}',
                                ],
                        ]
                );

                $this->add_group_control(
                        \Elementor\Group_Control_Border::get_type(),
                        [
                                'name' => 'main_border_nias_firstform_inputfocus',
                                'selector' => '{{WRAPPER}} .nias-phone-box input[type=text]:focus',
                        ]
                );



                $this->add_control(
                        'nias_firstform_buttonstyle',
                        [
                                'label' => esc_html__('استایل دکمه ارسال', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::HEADING,
                                'separator' => 'before',
                        ]
                );


                $this->add_group_control(
                        \Elementor\Group_Control_Background::get_type(),
                        [
                                'name' => 'nias_firstform_backgroundbutton',
                                'types' => ['classic', 'gradient', 'video'],
                                'selector' => '{{WRAPPER}}  .nias-main-modal #nias-login button',
                        ]
                );
                $this->add_control(
                        'nias_firstform_colorbutton',
                        [
                                'label' => esc_html__('رنگ متن دکمه', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal #nias-login button' => 'color: {{VALUE}}',
                                ],
                        ]
                );

                $this->add_responsive_control(
                        'nias_firstform_button_borderradius',
                        [
                                'label' => esc_html__('گردی گوشه ها', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'default' => [
                                        'top' => 10,
                                        'right' => 10,
                                        'bottom' => 10,
                                        'left' => 10,
                                        'unit' => 'px',
                                        'isLinked' => true,
                                ],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal #nias-login button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );
                $this->add_group_control(
                        \Elementor\Group_Control_Border::get_type(),
                        [
                                'name' => 'main_border_nias_firstform_button',
                                'selector' => '{{WRAPPER}} .nias-main-modal #nias-login button',
                        ]
                );

                $this->add_responsive_control(
                        'nias_firstform_input_buttonpadding',
                        [
                                'label' => esc_html__('انداره داخلی دکمه', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal #nias-login button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_group_control(
                        \Elementor\Group_Control_Typography::get_type(),
                        [
                                'name' => 'nias_firstform_button_typography',
                                'label' => esc_html__('تایپوگرافی دکمه', 'nias-login-signup'),
                                'selector' => '{{WRAPPER}} .nias-main-modal #nias-login button',
                        ]
                );

                $this->add_responsive_control(
                        'nias_firstform_button_width',
                        [
                                'label' => esc_html__('عرض دکمه', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SLIDER,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'range' => [
                                        'px' => ['min' => 0, 'max' => 600, 'step' => 1],
                                        '%' => ['min' => 0, 'max' => 100],
                                ],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal #nias-login button' => 'width: {{SIZE}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_responsive_control(
                        'nias_firstform_button_margin',
                        [
                                'label' => esc_html__('فاصله خارجی دکمه', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal #nias-login button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_group_control(
                        \Elementor\Group_Control_Box_Shadow::get_type(),
                        [
                                'name' => 'nias_firstform_button_boxshadow',
                                'label' => esc_html__('سایه دکمه', 'nias-login-signup'),
                                'selector' => '{{WRAPPER}} .nias-main-modal #nias-login button',
                        ]
                );

                $this->add_control(
                        'nias_firstform_buttonhoverstyle',
                        [
                                'label' => esc_html__('استایل هاور دکمه ارسال', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::HEADING,
                                'separator' => 'before',
                        ]
                );

                $this->add_control(
                        'nias_firstform_button_hoverbackground',
                        [
                                'label' => esc_html__('بک گراند هاور', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal #nias-login button:hover' => 'background: {{VALUE}} !important;',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_firstform_button_hovercolor',
                        [
                                'label' => esc_html__('رنگ متن هاور', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal #nias-login button:hover' => 'color: {{VALUE}} !important;',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_firstform_button_hoverborder',
                        [
                                'label' => esc_html__('رنگ بوردر هاور', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal #nias-login button:hover' => 'border-color: {{VALUE}} !important;',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_firstform_button_transition',
                        [
                                'label' => esc_html__('سرعت انیمیشن هاور (ثانیه)', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SLIDER,
                                'size_units' => ['px'],
                                'range' => ['px' => ['min' => 0, 'max' => 3, 'step' => 0.1]],
                                'default' => ['unit' => 'px', 'size' => 0.3],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal #nias-login button' => 'transition: all {{SIZE}}s ease;',
                                ],
                        ]
                );



                $this->add_control(
                        'nias_firstform_svgloaderstyle',
                        [
                                'label' => esc_html__('استایل انیمیشن لودر', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::HEADING,
                                'separator' => 'before',
                        ]
                );

                $this->add_responsive_control(
                        'nias_firstform_svgloadersize',
                        [
                                'label' => esc_html__('اندازه لودر', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SLIDER,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'range' => [
                                        'px' => [
                                                'min' => 0,
                                                'max' => 1000,
                                                'step' => 1,
                                        ],
                                        '%' => [
                                                'min' => 0,
                                                'max' => 100,
                                        ],
                                ],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal #nias-login button svg' => 'width: {{SIZE}}{{UNIT}}!important;',
                                        '{{WRAPPER}} .nias-main-modal #nias-login button svg' => 'height: {{SIZE}}{{UNIT}}!important;'
                                ],
                        ]
                );


                $this->end_controls_section();





                /* -------------------------------------------------------------------------- */
                /*                              style tab part 2                              */
                /* -------------------------------------------------------------------------- */

                $this->start_controls_section(
                        'nias_login_secondtform_style',
                        [
                                'label' => esc_html__('استایل فرم دوم', 'nias-login-signup'),
                                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                        ]
                );



                $this->add_responsive_control(
                        'nias_secondtform_logo_width',
                        [
                                'label' => esc_html__('عرض لوگوی فرم دوم', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SLIDER,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'range' => [
                                        'px' => [
                                                'min' => 0,
                                                'max' => 1000,
                                                'step' => 1,
                                        ],
                                        '%' => [
                                                'min' => 0,
                                                'max' => 100,
                                        ],
                                ],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-login-form-logo-code img' => 'width: {{SIZE}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_secondtform_titlestyles',
                        [
                                'label' => esc_html__('استایل عنوان', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::HEADING,
                                'separator' => 'before',
                        ]
                );

                $this->add_group_control(
                        \Elementor\Group_Control_Typography::get_type(),
                        [
                                'name' => 'secondtform_title_typography',
                                'selector' => '{{WRAPPER}} .nias-modal-title.second-form',
                        ]
                );
                $this->add_control(
                        'secondtform_title_typography_align',
                        [
                                'label' => esc_html__('چیدمان', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::CHOOSE,
                                'options' => [
                                        'right' => [
                                                'title' => esc_html__('راست چین', 'nias-login-signup'),
                                                'icon' => 'eicon-text-align-right',
                                        ],

                                        'center' => [
                                                'title' => esc_html__('وسط چین', 'nias-login-signup'),
                                                'icon' => 'eicon-text-align-center',
                                        ],

                                        'left' => [
                                                'title' => esc_html__('چپ چین', 'nias-login-signup'),
                                                'icon' => 'eicon-text-align-left',
                                        ],

                                ],
                                'default' => 'center',
                                'toggle' => true,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-modal-title.second-form' => 'text-align: {{VALUE}};',
                                ],
                        ]
                );
                $this->add_control(
                        'secondtform_title_color',
                        [
                                'label' => esc_html__('رنگ عنوان', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-modal-title.second-form' => 'color: {{VALUE}}',
                                ],
                        ]
                );
                $this->add_responsive_control(
                        'secondtform_title_margin',
                        [
                                'label' => esc_html__('فاصله از اطراف', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'default' => [
                                        'top' => 5,
                                        'right' => 0,
                                        'bottom' => 5,
                                        'left' => 0,
                                        'unit' => 'px',
                                        'isLinked' => false,
                                ],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-modal-title.second-form' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_secondtform_subtitlestyles',
                        [
                                'label' => esc_html__('استایل زیر عنوان', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::HEADING,
                                'separator' => 'before',
                        ]
                );


                $this->add_group_control(
                        \Elementor\Group_Control_Typography::get_type(),
                        [
                                'name' => 'secondtform_subtitle_typography',
                                'selector' => '{{WRAPPER}} .nias-login-subtitle',
                        ]
                );
                $this->add_control(
                        'secondtform_subtitle_typography_align',
                        [
                                'label' => esc_html__('چیدمان زیر عنوان', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::CHOOSE,
                                'options' => [
                                        'right' => [
                                                'title' => esc_html__('راست چین', 'nias-login-signup'),
                                                'icon' => 'eicon-text-align-right',
                                        ],

                                        'center' => [
                                                'title' => esc_html__('وسط چین', 'nias-login-signup'),
                                                'icon' => 'eicon-text-align-center',
                                        ],

                                        'left' => [
                                                'title' => esc_html__('چپ چین', 'nias-login-signup'),
                                                'icon' => 'eicon-text-align-left',
                                        ],

                                ],
                                'default' => 'center',
                                'toggle' => true,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-login-subtitle' => 'text-align: {{VALUE}};',
                                ],
                        ]
                );
                $this->add_control(
                        'secondtform_subtitle_color',
                        [
                                'label' => esc_html__('رنگ زیر عنوان', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-login-subtitle' => 'color: {{VALUE}}',
                                ],
                        ]
                );



                $this->add_control(
                        'nias_secondtform_labelstyles',
                        [
                                'label' => esc_html__('استایل لیبل فیلد', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::HEADING,
                                'separator' => 'before',
                        ]
                );


                $this->add_group_control(
                        \Elementor\Group_Control_Typography::get_type(),
                        [
                                'name' => 'secondtform_numberlabel_typography',
                                'selector' => '{{WRAPPER}} .nias-login-result',
                        ]
                );
                $this->add_control(
                        'secondtform_numberlabel_typography_align',
                        [
                                'label' => esc_html__('چیدمان لیبل کد', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::CHOOSE,
                                'options' => [
                                        'right' => [
                                                'title' => esc_html__('راست چین', 'nias-login-signup'),
                                                'icon' => 'eicon-text-align-right',
                                        ],

                                        'center' => [
                                                'title' => esc_html__('وسط چین', 'nias-login-signup'),
                                                'icon' => 'eicon-text-align-center',
                                        ],

                                        'left' => [
                                                'title' => esc_html__('چپ چین', 'nias-login-signup'),
                                                'icon' => 'eicon-text-align-left',
                                        ],

                                ],
                                'default' => 'center',
                                'toggle' => true,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-login-result' => 'text-align: {{VALUE}}!important;',
                                ],
                        ]
                );
                $this->add_control(
                        'secondtform_label_color',
                        [
                                'label' => esc_html__('رنگ لیبل کد', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-login-result' => 'color: {{VALUE}}',
                                ],
                        ]
                );

                if (($password_otp_activate) || ($password_activate)) {

                        $this->add_control(
                                'nias_secondform_inputpass',
                                [
                                        'label' => esc_html__('استایل ناحیه پسورد', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::HEADING,
                                        'separator' => 'before',
                                ]
                        );

                        $this->add_group_control(
                                \Elementor\Group_Control_Background::get_type(),
                                [
                                        'name' => 'nias_secondform_inputpass_back',
                                        'types' => ['classic', 'gradient', 'video'],
                                        'selector' => '{{WRAPPER}} #ns_password',
                                ]
                        );
                        $this->add_control(
                                'nias_secondform_inputpass_color',
                                [
                                        'label' => esc_html__('رنگ متن داخل ناحیه', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::COLOR,
                                        'selectors' => [
                                                '{{WRAPPER}} #ns_password' => 'color: {{VALUE}}',
                                        ],
                                ]
                        );

                        $this->add_responsive_control(
                                'nias_secondform_inputpass_borderradius',
                                [
                                        'label' => esc_html__('گردی گوشه ها', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                        'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                        'default' => [
                                                'top' => 10,
                                                'right' => 10,
                                                'bottom' => 10,
                                                'left' => 10,
                                                'unit' => 'px',
                                                'isLinked' => true,
                                        ],
                                        'selectors' => [
                                                '{{WRAPPER}} #ns_password' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                        ],
                                ]
                        );
                        $this->add_group_control(
                                \Elementor\Group_Control_Border::get_type(),
                                [
                                        'name' => 'nias_secondform_inputpass_mainborder',
                                        'selector' => '{{WRAPPER}} #ns_password',
                                ]
                        );

                        $this->add_responsive_control(
                                'nias_secondform_inputpass_padding',
                                [
                                        'label' => esc_html__('انداره داخلی فیلد درونی', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                        'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                        'selectors' => [
                                                '{{WRAPPER}} #ns_password' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                        ],
                                ]
                        );


                        $this->add_control(
                                'nias_secondform_inputpass_focus',
                                [
                                        'label' => esc_html__('استایل ناحیه شماره موبایل در حالت انتخاب', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::HEADING,
                                        'separator' => 'before',
                                ]
                        );
                        $this->add_group_control(
                                \Elementor\Group_Control_Background::get_type(),
                                [
                                        'name' => 'nias_secondform_inputpass_focusback',
                                        'types' => ['classic', 'gradient', 'video'],
                                        'selector' => ' {{WRAPPER}} #ns_password:focus',
                                ]
                        );
                        $this->add_control(
                                'nias_secondform_inputpass_focuscolor',
                                [
                                        'label' => esc_html__('رنگ متن داخل ناحیه', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::COLOR,
                                        'selectors' => [
                                                '{{WRAPPER}} #ns_password:focus' => 'color: {{VALUE}}',
                                        ],
                                ]
                        );

                        $this->add_group_control(
                                \Elementor\Group_Control_Border::get_type(),
                                [
                                        'name' => 'nias_secondform_inputpass_focusborder',
                                        'label' => esc_html__('بوردر در فوکوس', 'nias-login-signup'),
                                        'selector' => '{{WRAPPER}} #ns_password:focus',
                                ]
                        );

                        $this->add_control(
                                'nias_secondform_btnpass_heading',
                                [
                                        'label' => esc_html__('استایل دکمه فراموشی رمز', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::HEADING,
                                        'separator' => 'before',
                                ]
                        );

                        $this->add_group_control(
                                \Elementor\Group_Control_Background::get_type(),
                                [
                                        'name' => 'nias_secondform_btnpass_background',
                                        'types' => ['classic', 'gradient', 'video'],
                                        'selector' => '{{WRAPPER}} #nias-forgot-password',
                                ]
                        );
                        $this->add_control(
                                'nias_secondform_btnpass_color',
                                [
                                        'label' => esc_html__('رنگ متن داخل دکمه', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::COLOR,
                                        'selectors' => [
                                                '{{WRAPPER}} #nias-forgot-password' => 'color: {{VALUE}}',
                                        ],
                                ]
                        );

                        $this->add_responsive_control(
                                'nias_secondform_btnpassborderradius',
                                [
                                        'label' => esc_html__('گردی گوشه های دکمه', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                        'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                        'default' => [
                                                'top' => 10,
                                                'right' => 10,
                                                'bottom' => 10,
                                                'left' => 10,
                                                'unit' => 'px',
                                                'isLinked' => true,
                                        ],
                                        'selectors' => [
                                                '{{WRAPPER}} #nias-forgot-password' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                        ],
                                ]
                        );
                        $this->add_group_control(
                                \Elementor\Group_Control_Border::get_type(),
                                [
                                        'name' => 'nias_secondform_btnpass_border',
                                        'selector' => '{{WRAPPER}} #nias-forgot-password',
                                ]
                        );

                        $this->add_responsive_control(
                                'nias_secondform_btnpass_padding',
                                [
                                        'label' => esc_html__('انداره فاصله داخلی دکمه', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                        'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                        'selectors' => [
                                                '{{WRAPPER}} #nias-forgot-password' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                        ],
                                ]
                        );

                        $this->add_group_control(
                                \Elementor\Group_Control_Typography::get_type(),
                                [
                                        'name' => 'nias_secondform_btnpass_typography',
                                        'label' => esc_html__('تایپوگرافی دکمه', 'nias-login-signup'),
                                        'selector' => '{{WRAPPER}} #nias-forgot-password',
                                ]
                        );

                        $this->add_responsive_control(
                                'nias_secondform_btnpass_margin',
                                [
                                        'label' => esc_html__('فاصله خارجی دکمه', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                        'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                        'selectors' => [
                                                '{{WRAPPER}} #nias-forgot-password' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                        ],
                                ]
                        );

                        $this->add_control(
                                'nias_secondform_btnpass_hoverbackground',
                                [
                                        'label' => esc_html__('بک گراند هاور', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::COLOR,
                                        'selectors' => [
                                                '{{WRAPPER}} #nias-forgot-password:hover' => 'background: {{VALUE}} !important;',
                                        ],
                                ]
                        );

                        $this->add_control(
                                'nias_secondform_btnpass_hovercolor',
                                [
                                        'label' => esc_html__('رنگ متن هاور', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::COLOR,
                                        'selectors' => [
                                                '{{WRAPPER}} #nias-forgot-password:hover' => 'color: {{VALUE}} !important;',
                                        ],
                                ]
                        );

                }

                if (!($password_activate)) {


                        $this->add_control(
                                'nias_secondtform_input',
                                [
                                        'label' => esc_html__('استایل ناحیه کد تایید', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::HEADING,
                                        'separator' => 'before',
                                ]
                        );

                        $this->add_group_control(
                                \Elementor\Group_Control_Background::get_type(),
                                [
                                        'name' => 'nias_secondtform_backgroundinput',
                                        'types' => ['classic', 'gradient', 'video'],
                                        'selector' => '{{WRAPPER}} .nias-code-box input[type="text"],
                                {{WRAPPER}} .nias-code-box input[type="password"],
                                {{WRAPPER}} .nias-code-box input[type="tel"],
                                {{WRAPPER}} .nias-code-box input[type="email"] ',
                                ]
                        );
                        $this->add_control(
                                'nias_secondtform_colorinput',
                                [
                                        'label' => esc_html__('رنگ متن داخل ناحیه', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::COLOR,
                                        'selectors' => [
                                                '{{WRAPPER}} .nias-code-box input[type="text"],
                                        {{WRAPPER}} .nias-code-box input[type="password"],
                                        {{WRAPPER}} .nias-code-box input[type="tel"],
                                        {{WRAPPER}} .nias-code-box input[type="email"] ' => 'color: {{VALUE}}',
                                        ],
                                ]
                        );

                        $this->add_responsive_control(
                                'nias_secondtform_input_borderradius',
                                [
                                        'label' => esc_html__('گردی گوشه ها', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                        'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                        'default' => [
                                                'top' => 10,
                                                'right' => 10,
                                                'bottom' => 10,
                                                'left' => 10,
                                                'unit' => 'px',
                                                'isLinked' => true,
                                        ],
                                        'selectors' => [
                                                '{{WRAPPER}} .nias-code-box input[type="text"],
                                        {{WRAPPER}} .nias-code-box input[type="tel"],
                                        {{WRAPPER}} .nias-code-box input[type="password"],
                                        {{WRAPPER}} .nias-code-box input[type="email"] ' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                        ],
                                ]
                        );
                        $this->add_group_control(
                                \Elementor\Group_Control_Border::get_type(),
                                [
                                        'name' => 'main_border_nias_secondtform_input',
                                        'selector' => '{{WRAPPER}} .nias-code-box input[type="text"],
                                {{WRAPPER}} .nias-code-box input[type="tel"],
                                {{WRAPPER}} .nias-code-box input[type="password"],
                                {{WRAPPER}} .nias-code-box input[type="email"] ',
                                ]
                        );

                        $this->add_responsive_control(
                                'nias_secondtform_input_paddinginside',
                                [
                                        'label' => esc_html__('انداره داخلی فیلد', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                        'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                        'selectors' => [
                                                '{{WRAPPER}} .nias-code-box input[type="text"],
                                        {{WRAPPER}} .nias-code-box input[type="tel"],
                                        {{WRAPPER}} .nias-code-box input[type="password"],
                                        {{WRAPPER}} .nias-code-box input[type="email"] ' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                        ],
                                ]
                        );


                        $this->add_control(
                                'nias_secondtform_inputfocus',
                                [
                                        'label' => esc_html__('استایل ناحیه کد تایید در حالت انتخاب', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::HEADING,
                                        'separator' => 'before',
                                ]
                        );
                        $this->add_group_control(
                                \Elementor\Group_Control_Background::get_type(),
                                [
                                        'name' => 'nias_secondtform_backgroundinputfocus',
                                        'types' => ['classic', 'gradient', 'video'],
                                        'selector' => '{{WRAPPER}} .nias-code-box input[type="text"]:focus,
                                {{WRAPPER}} .nias-code-box input[type="tel"]:focus,
                                {{WRAPPER}} .nias-code-box input[type="password"]:focus,
                                {{WRAPPER}} .nias-code-box input[type="email"]:focus',
                                ]
                        );
                        $this->add_control(
                                'nias_secondtform_colorinputfocus',
                                [
                                        'label' => esc_html__('رنگ متن داخل ناحیه', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::COLOR,
                                        'selectors' => [
                                                '{{WRAPPER}} .nias-code-box input[type="text"]:focus,
                                        {{WRAPPER}} .nias-code-box input[type="tel"]:focus,
                                        {{WRAPPER}} .nias-code-box input[type="password"]:focus,
                                        {{WRAPPER}} .nias-code-box input[type="email"]:focus' => 'color: {{VALUE}}',
                                        ],
                                ]
                        );

                        $this->add_responsive_control(
                                'nias_secondtform_inputfocus_borderradius',
                                [
                                        'label' => esc_html__('گردی گوشه ها', 'nias-login-signup'),
                                        'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                        'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                        'default' => [
                                                'top' => 10,
                                                'right' => 10,
                                                'bottom' => 10,
                                                'left' => 10,
                                                'unit' => 'px',
                                                'isLinked' => true,
                                        ],
                                        'selectors' => [
                                                '{{WRAPPER}} .nias-code-box input[type="text"]:focus,
                                        {{WRAPPER}} .nias-code-box input[type="password"]:focus,
                                        {{WRAPPER}} .nias-code-box input[type="tel"]:focus,
                                        {{WRAPPER}} .nias-code-box input[type="email"]:focus' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                        ],
                                ]
                        );
                        $this->add_group_control(
                                \Elementor\Group_Control_Border::get_type(),
                                [
                                        'name' => 'main_border_nias_secondtform_inputfocus',
                                        'selector' => '{{WRAPPER}} .nias-code-box input[type="text"]:focus,
                                {{WRAPPER}} .nias-code-box input[type="tel"]:focus,
                                {{WRAPPER}} .nias-code-box input[type="password"]:focus,
                                {{WRAPPER}} .nias-code-box input[type="email"]:focus',
                                ]
                        );
                }

                $this->add_control(
                        'nias_secondtform_buttonstyle',
                        [
                                'label' => esc_html__('استایل دکمه تایید', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::HEADING,
                                'separator' => 'before',
                        ]
                );


                $this->add_group_control(
                        \Elementor\Group_Control_Background::get_type(),
                        [
                                'name' => 'nias_secondtform_backgroundbutton',
                                'types' => ['classic', 'gradient', 'video'],
                                'selector' => '{{WRAPPER}}  .nias-main-modal .nias-confirm-box button.nias-confirm-code',
                        ]
                );
                $this->add_control(
                        'nias_secondtform_colorbutton',
                        [
                                'label' => esc_html__('رنگ متن دکمه', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal .nias-confirm-box button.nias-confirm-code' => 'color: {{VALUE}}',
                                ],
                        ]
                );

                $this->add_responsive_control(
                        'nias_secondtform_button_borderradius',
                        [
                                'label' => esc_html__('گردی گوشه ها', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'default' => [
                                        'top' => 10,
                                        'right' => 10,
                                        'bottom' => 10,
                                        'left' => 10,
                                        'unit' => 'px',
                                        'isLinked' => true,
                                ],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal .nias-confirm-box button.nias-confirm-code' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );
                $this->add_group_control(
                        \Elementor\Group_Control_Border::get_type(),
                        [
                                'name' => 'main_border_nias_secondtform_button',
                                'selector' => '{{WRAPPER}} .nias-main-modal .nias-confirm-box button.nias-confirm-code',
                        ]
                );

                $this->add_responsive_control(
                        'nias_secondtform_input_buttonpadding',
                        [
                                'label' => esc_html__('انداره داخلی دکمه', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal .nias-confirm-box button.nias-confirm-code' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_group_control(
                        \Elementor\Group_Control_Typography::get_type(),
                        [
                                'name' => 'nias_secondtform_button_typography',
                                'label' => esc_html__('تایپوگرافی دکمه', 'nias-login-signup'),
                                'selector' => '{{WRAPPER}} .nias-main-modal .nias-confirm-box button.nias-confirm-code',
                        ]
                );

                $this->add_responsive_control(
                        'nias_secondtform_button_width',
                        [
                                'label' => esc_html__('عرض دکمه', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SLIDER,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'range' => [
                                        'px' => ['min' => 0, 'max' => 600, 'step' => 1],
                                        '%' => ['min' => 0, 'max' => 100],
                                ],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal .nias-confirm-box button.nias-confirm-code' => 'width: {{SIZE}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_responsive_control(
                        'nias_secondtform_button_margin',
                        [
                                'label' => esc_html__('فاصله خارجی دکمه', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal .nias-confirm-box button.nias-confirm-code' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_group_control(
                        \Elementor\Group_Control_Box_Shadow::get_type(),
                        [
                                'name' => 'nias_secondtform_button_boxshadow',
                                'label' => esc_html__('سایه دکمه', 'nias-login-signup'),
                                'selector' => '{{WRAPPER}} .nias-main-modal .nias-confirm-box button.nias-confirm-code',
                        ]
                );

                $this->add_control(
                        'nias_secondtform_buttonhoverstyle',
                        [
                                'label' => esc_html__('استایل هاور دکمه تایید', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::HEADING,
                                'separator' => 'before',
                        ]
                );

                $this->add_control(
                        'nias_secondtform_button_hoverbackground',
                        [
                                'label' => esc_html__('بک گراند هاور', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal .nias-confirm-box button.nias-confirm-code:hover' => 'background: {{VALUE}} !important;',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_secondtform_button_hovercolor',
                        [
                                'label' => esc_html__('رنگ متن هاور', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal .nias-confirm-box button.nias-confirm-code:hover' => 'color: {{VALUE}} !important;',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_secondtform_button_hoverborder',
                        [
                                'label' => esc_html__('رنگ بوردر هاور', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal .nias-confirm-box button.nias-confirm-code:hover' => 'border-color: {{VALUE}} !important;',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_secondtform_button_transition',
                        [
                                'label' => esc_html__('سرعت انیمیشن هاور (ثانیه)', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SLIDER,
                                'size_units' => ['px'],
                                'range' => ['px' => ['min' => 0, 'max' => 3, 'step' => 0.1]],
                                'default' => ['unit' => 'px', 'size' => 0.3],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal .nias-confirm-box button.nias-confirm-code' => 'transition: all {{SIZE}}s ease;',
                                ],
                        ]
                );


                $this->add_control(
                        'nias_secondtform_buttoneditnumber',
                        [
                                'label' => esc_html__('استایل دکمه اصلاح شماره', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::HEADING,
                                'separator' => 'before',
                        ]
                );

                $this->add_group_control(
                        \Elementor\Group_Control_Background::get_type(),
                        [
                                'name' => 'nias_secondtform_buttoneditnumberback',
                                'types' => ['classic', 'gradient', 'video'],
                                'selector' => '{{WRAPPER}}  .nias-main-modal #nias-change-number',
                        ]
                );

                $this->add_control(
                        'nias_secondtform_colorbuttoneditnumber',
                        [
                                'label' => esc_html__('رنگ متن دکمه', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal #nias-change-number' => 'color: {{VALUE}}',
                                ],
                        ]
                );

                $this->add_responsive_control(
                        'nias_secondtform_buttoneditnumber_borderradius',
                        [
                                'label' => esc_html__('گردی گوشه ها', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'default' => [
                                        'top' => 10,
                                        'right' => 10,
                                        'bottom' => 10,
                                        'left' => 10,
                                        'unit' => 'px',
                                        'isLinked' => true,
                                ],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal #nias-change-number' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );
                $this->add_group_control(
                        \Elementor\Group_Control_Border::get_type(),
                        [
                                'name' => 'main_border_nias_secondtform_buttoneditnumber',
                                'selector' => '{{WRAPPER}} .nias-main-modal #nias-change-number',
                        ]
                );

                $this->add_responsive_control(
                        'nias_secondtform_input_buttoneditnumberpadding',
                        [
                                'label' => esc_html__('انداره داخلی دکمه', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal #nias-change-number' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_group_control(
                        \Elementor\Group_Control_Typography::get_type(),
                        [
                                'name' => 'nias_secondtform_buttoneditnumber_typography',
                                'label' => esc_html__('تایپوگرافی دکمه', 'nias-login-signup'),
                                'selector' => '{{WRAPPER}} .nias-main-modal #nias-change-number',
                        ]
                );

                $this->add_responsive_control(
                        'nias_secondtform_buttoneditnumber_margin',
                        [
                                'label' => esc_html__('فاصله خارجی دکمه', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal #nias-change-number' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_secondtform_buttoneditnumber_hoverbackground',
                        [
                                'label' => esc_html__('بک گراند هاور', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal #nias-change-number:hover' => 'background: {{VALUE}} !important;',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_secondtform_buttoneditnumber_hovercolor',
                        [
                                'label' => esc_html__('رنگ متن هاور', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal #nias-change-number:hover' => 'color: {{VALUE}} !important;',
                                ],
                        ]
                );


                $this->add_control(
                        'nias_secondtform_resend',
                        [
                                'label' => esc_html__('استایل باکس ارسال مجدد', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::HEADING,
                                'separator' => 'before',
                        ]
                );

                $this->add_group_control(
                        \Elementor\Group_Control_Background::get_type(),
                        [
                                'name' => 'nias_secondtform_resendback',
                                'types' => ['classic', 'gradient', 'video'],
                                'selector' => '{{WRAPPER}}  .nias-main-modal .nias-resendbox',
                        ]
                );

                $this->add_control(
                        'nias_secondtform_colorbuttoneresend',
                        [
                                'label' => esc_html__('رنگ متن باکس', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal .nias-resendbox' => 'color: {{VALUE}}',
                                ],
                        ]
                );

                $this->add_responsive_control(
                        'nias_secondtform_resend_borderradius',
                        [
                                'label' => esc_html__('گردی گوشه ها', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'default' => [
                                        'top' => 10,
                                        'right' => 10,
                                        'bottom' => 10,
                                        'left' => 10,
                                        'unit' => 'px',
                                        'isLinked' => true,
                                ],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal .nias-resendbox' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );
                $this->add_group_control(
                        \Elementor\Group_Control_Border::get_type(),
                        [
                                'name' => 'main_border_nias_secondtform_resend',
                                'selector' => '{{WRAPPER}} .nias-main-modal .nias-resendbox',
                        ]
                );

                $this->add_responsive_control(
                        'nias_secondtform_input_resendpadding',
                        [
                                'label' => esc_html__('انداره داخلی باکس', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-main-modal .nias-resendbox' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );



                $this->add_control(
                        'nias_secondtform_resendactive',
                        [
                                'label' => esc_html__('استایل دکمه فعال ارسال مجدد', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::HEADING,
                                'separator' => 'before',
                        ]
                );
                $this->add_group_control(
                        \Elementor\Group_Control_Background::get_type(),
                        [
                                'name' => 'nias_secondtform_resendbackactive',
                                'types' => ['classic', 'gradient', 'video'],
                                'selector' => '{{WRAPPER}}  .nias-resend.active',
                        ]
                );

                $this->add_control(
                        'nias_secondtform_colorbuttoneresendactive',
                        [
                                'label' => esc_html__('رنگ متن باکس', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-resend.active' => 'color: {{VALUE}}',
                                ],
                        ]
                );




                $this->add_control(
                        'nias_secondtform_svgloaderstyle',
                        [
                                'label' => esc_html__('استایل انیمیشن لودر تایید', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::HEADING,
                                'separator' => 'before',
                        ]
                );

                $this->add_responsive_control(
                        'nias_secondtform_svgloadersize',
                        [
                                'label' => esc_html__('اندازه لودر', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SLIDER,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'range' => [
                                        'px' => [
                                                'min' => 0,
                                                'max' => 1000,
                                                'step' => 1,
                                        ],
                                        '%' => [
                                                'min' => 0,
                                                'max' => 100,
                                        ],
                                ],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-confirm-code svg' => 'width: {{SIZE}}{{UNIT}}!important;',
                                        '{{WRAPPER}} .nias-confirm-code svg' => 'height: {{SIZE}}{{UNIT}}!important;'
                                ],
                        ]
                );

                $this->add_control(
                        'nias_secondtform_counterstyle',
                        [
                                'label' => esc_html__('استایل تایمر', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::HEADING,
                                'separator' => 'before',
                        ]
                );

                $this->add_control(
                        'nias_secondtform_counter_background_default',
                        [
                                'label' => esc_html__('رنگ بک گراند دایره', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .stroke-neutral-light' => 'stroke: {{VALUE}}!important;',
                                        '{{WRAPPER}} .fill-neutral-light' => 'fill: {{VALUE}}!important;',
                                ],
                        ]
                );
                $this->add_control(
                        'nias_secondtform_counter_motion_line',
                        [
                                'label' => esc_html__('رنگ خط چرخشی', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .stroke-neutral-dark' => 'stroke: {{VALUE}}!important;',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_secondtform_counter_text',
                        [
                                'label' => esc_html__('رنگ عدد تایمر', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-countdown' => 'color: {{VALUE}}!important;',
                                ],
                        ]
                );
                $this->add_control(
                        'nias_secondtform_counter_sendagaintext',
                        [
                                'label' => esc_html__('رنگ ارسال مجدد', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        '{{WRAPPER}} .nias-resend' => 'color: {{VALUE}}',
                                ],
                        ]
                );

                $this->add_responsive_control(
                        'nias_secondtform_svg_counter_size',
                        [
                                'label' => esc_html__('اندازه لودر', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SLIDER,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'range' => [
                                        'px' => [
                                                'min' => 0,
                                                'max' => 1000,
                                                'step' => 1,
                                        ],
                                        '%' => [
                                                'min' => 0,
                                                'max' => 100,
                                        ],
                                ],
                                'selectors' => [
                                        '{{WRAPPER}} .nias-circle-counter' => 'width: {{SIZE}}{{UNIT}}!important;',
                                        '{{WRAPPER}} .nias-circle-counter' => 'height: {{SIZE}}{{UNIT}}!important;'
                                ],
                        ]
                );




                $this->end_controls_section();


                /* -------------------------------------------------------------------------- */
                /*                        success section style control                        */
                /* -------------------------------------------------------------------------- */

                $this->start_controls_section(
                        'nias_success_style',
                        [
                                'label' => esc_html__('استایل ورود موفقیت آمیز', 'nias-login-signup'),
                                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                        ]
                );

                $this->add_group_control(
                        \Elementor\Group_Control_Background::get_type(),
                        [
                                'name' => 'nias_success_background',
                                'types' => ['classic', 'gradient'],
                                'selector' => 'body .nias-success',
                        ]
                );

                $this->add_responsive_control(
                        'nias_success_padding',
                        [
                                'label' => esc_html__('فاصله داخلی باکس', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'selectors' => [
                                        'body .nias-success' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_responsive_control(
                        'nias_success_borderradius',
                        [
                                'label' => esc_html__('گردی گوشه ها', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'selectors' => [
                                        'body .nias-success' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_group_control(
                        \Elementor\Group_Control_Border::get_type(),
                        [
                                'name' => 'nias_success_border',
                                'selector' => 'body .nias-success',
                        ]
                );

                $this->add_group_control(
                        \Elementor\Group_Control_Box_Shadow::get_type(),
                        [
                                'name' => 'nias_success_boxshadow',
                                'selector' => 'body .nias-success',
                        ]
                );

                $this->add_responsive_control(
                        'nias_success_gap',
                        [
                                'label' => esc_html__('فاصله بین عناصر', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SLIDER,
                                'size_units' => ['px', 'em', 'rem'],
                                'range' => ['px' => ['min' => 0, 'max' => 100, 'step' => 1]],
                                'selectors' => [
                                        'body .nias-success' => 'gap: {{SIZE}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_responsive_control(
                        'nias_success_align',
                        [
                                'label' => esc_html__('چیدمان عناصر', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::CHOOSE,
                                'options' => [
                                        'flex-start' => [
                                                'title' => esc_html__('راست چین', 'nias-login-signup'),
                                                'icon' => 'eicon-align-start-h',
                                        ],
                                        'center' => [
                                                'title' => esc_html__('وسط چین', 'nias-login-signup'),
                                                'icon' => 'eicon-align-center-h',
                                        ],
                                        'flex-end' => [
                                                'title' => esc_html__('چپ چین', 'nias-login-signup'),
                                                'icon' => 'eicon-align-end-h',
                                        ],
                                ],
                                'default' => 'center',
                                'toggle' => true,
                                'selectors' => [
                                        'body .nias-success' => 'align-items: {{VALUE}}; text-align: center;',
                                ],
                        ]
                );

                $this->add_responsive_control(
                        'nias_success_content_maxwidth',
                        [
                                'label' => esc_html__('حداکثر عرض محتوا', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SLIDER,
                                'size_units' => ['px', '%', 'vw'],
                                'range' => [
                                        'px' => ['min' => 0, 'max' => 1000, 'step' => 1],
                                        '%' => ['min' => 0, 'max' => 100],
                                ],
                                'selectors' => [
                                        'body .nias-success > *' => 'max-width: {{SIZE}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_success_iconstyle',
                        [
                                'label' => esc_html__('آیکون موفقیت', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::HEADING,
                                'separator' => 'before',
                                'condition' => [
                                        'nias_success_show_icon' => 'yes',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_success_icon_color',
                        [
                                'label' => esc_html__('رنگ آیکون', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'default' => '#22c55e',
                                'selectors' => [
                                        'body .nias-success .nias-success-icon i' => 'color: {{VALUE}};',
                                        'body .nias-success .nias-success-icon svg' => 'fill: {{VALUE}};',
                                ],
                                'condition' => [
                                        'nias_success_show_icon' => 'yes',
                                ],
                        ]
                );

                $this->add_responsive_control(
                        'nias_success_icon_size',
                        [
                                'label' => esc_html__('اندازه آیکون', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SLIDER,
                                'size_units' => ['px', 'em', 'rem'],
                                'range' => ['px' => ['min' => 0, 'max' => 300, 'step' => 1]],
                                'default' => ['unit' => 'px', 'size' => 48],
                                'selectors' => [
                                        'body .nias-success .nias-success-icon i' => 'font-size: {{SIZE}}{{UNIT}};',
                                        'body .nias-success .nias-success-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                                ],
                                'condition' => [
                                        'nias_success_show_icon' => 'yes',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_success_titlestyle',
                        [
                                'label' => esc_html__('عنوان موفقیت', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::HEADING,
                                'separator' => 'before',
                        ]
                );

                $this->add_group_control(
                        \Elementor\Group_Control_Typography::get_type(),
                        [
                                'name' => 'nias_success_title_typography',
                                'selector' => 'body .nias-success .niaslogin-success-title',
                        ]
                );

                $this->add_control(
                        'nias_success_title_color',
                        [
                                'label' => esc_html__('رنگ عنوان', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        'body .nias-success .niaslogin-success-title' => 'color: {{VALUE}};',
                                ],
                        ]
                );

                $this->add_responsive_control(
                        'nias_success_title_margin',
                        [
                                'label' => esc_html__('فاصله عنوان از اطراف', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'selectors' => [
                                        'body .nias-success .niaslogin-success-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_success_title_bg',
                        [
                                'label' => esc_html__('رنگ پس‌زمینه عنوان', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        'body .nias-success .niaslogin-success-title' => 'background-color: {{VALUE}};',
                                ],
                        ]
                );

                $this->add_responsive_control(
                        'nias_success_title_padding',
                        [
                                'label' => esc_html__('فاصله داخلی عنوان', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'selectors' => [
                                        'body .nias-success .niaslogin-success-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_responsive_control(
                        'nias_success_title_radius',
                        [
                                'label' => esc_html__('گردی گوشه‌های عنوان', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
                                'selectors' => [
                                        'body .nias-success .niaslogin-success-title' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                                ],
                        ]
                );

                $this->add_group_control(
                        \Elementor\Group_Control_Border::get_type(),
                        [
                                'name' => 'nias_success_title_border',
                                'selector' => 'body .nias-success .niaslogin-success-title',
                        ]
                );

                $this->add_control(
                        'nias_success_subtitlestyle',
                        [
                                'label' => esc_html__('متن زیر لودر', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::HEADING,
                                'separator' => 'before',
                                'condition' => [
                                        'nias_success_show_loader' => 'yes',
                                ],
                        ]
                );

                $this->add_group_control(
                        \Elementor\Group_Control_Typography::get_type(),
                        [
                                'name' => 'nias_success_subtitle_typography',
                                'selector' => 'body .nias-success .niaslogin-success-subtitle',
                                'condition' => [
                                        'nias_success_show_loader' => 'yes',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_success_subtitle_color',
                        [
                                'label' => esc_html__('رنگ متن', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        'body .nias-success .niaslogin-success-subtitle' => 'color: {{VALUE}};',
                                ],
                                'condition' => [
                                        'nias_success_show_loader' => 'yes',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_success_loaderstyle',
                        [
                                'label' => esc_html__('لودر', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::HEADING,
                                'separator' => 'before',
                                'condition' => [
                                        'nias_success_show_loader' => 'yes',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_success_loader_color',
                        [
                                'label' => esc_html__('رنگ لودر', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::COLOR,
                                'selectors' => [
                                        'body .nias-success .niaslogin-success-subtitle .elementor-icon i' => 'color: {{VALUE}};',
                                        'body .nias-success .niaslogin-success-subtitle .elementor-icon svg' => 'fill: {{VALUE}};',
                                ],
                                'condition' => [
                                        'nias_success_show_loader' => 'yes',
                                ],
                        ]
                );

                $this->add_responsive_control(
                        'nias_success_loader_size',
                        [
                                'label' => esc_html__('اندازه لودر', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SLIDER,
                                'size_units' => ['px', 'em', 'rem'],
                                'range' => ['px' => ['min' => 0, 'max' => 200, 'step' => 1]],
                                'selectors' => [
                                        'body .nias-success .niaslogin-success-subtitle .elementor-icon i' => 'font-size: {{SIZE}}{{UNIT}};',
                                        'body .nias-success .niaslogin-success-subtitle .elementor-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                                ],
                                'condition' => [
                                        'nias_success_show_loader' => 'yes',
                                ],
                        ]
                );

                $this->add_control(
                        'nias_success_loader_spin',
                        [
                                'label' => esc_html__('چرخش لودر', 'nias-login-signup'),
                                'type' => \Elementor\Controls_Manager::SWITCHER,
                                'label_on' => esc_html__('بله', 'nias-login-signup'),
                                'label_off' => esc_html__('خیر', 'nias-login-signup'),
                                'return_value' => 'yes',
                                'default' => 'yes',
                                'selectors' => [
                                        'body .nias-success .niaslogin-success-subtitle .elementor-icon i,
                                         body .nias-success .niaslogin-success-subtitle .elementor-icon svg' => 'animation: nias-success-spin 1.2s linear infinite; display: inline-block;',
                                ],
                                'condition' => [
                                        'nias_success_show_loader' => 'yes',
                                ],
                        ]
                );

                $this->end_controls_section();
        }


















        /* -------------------------------------------------------------------------- */
        /*                           widget render html code                          */
        /* -------------------------------------------------------------------------- */

        protected function render()
        {
                $settings = $this->get_settings_for_display();

                $digits = get_option('nsdigitsquantity');
                $email_activate = get_option('nias_email_activate');
                $email_phone_activate = get_option('nias_email_phone_activate');
                $password_activate = get_option('nias_password_activate');
                $password_otp_activate = get_option('nias_password_otp_activate');
                $manual_password_activate = get_option('nias_manual_password_activate');

                // بلوک ساخت رمز عبور دستی برای کاربران جدید (با جاوااسکریپت فقط برای کاربر جدید نمایش داده می‌شود)
                $nias_manual_password_block = '';
                if ($manual_password_activate && ($password_activate || $password_otp_activate)) {
                        $nias_manual_password_block = '
                <div class="nias-manual-password" style="display:none;">
                        <span class="nias-modal-title second-form">یک رمز عبور برای حساب خود انتخاب کنید</span>
                        <div class="nsinput-field">
                                <input autocomplete="new-password" placeholder=" " type="password" name="nias_manual_new_password" id="nias_manual_new_password" />
                                <label class="ns-radio-label" for="nias_manual_new_password">رمز عبور جدید</label>
                        </div>
                        <div class="nias-pass-strength" aria-hidden="true">
                                <div class="nias-pass-strength__track"><span class="nias-pass-strength__bar"></span></div>
                                <span class="nias-pass-strength__text">قدرت رمز عبور</span>
                        </div>
                        <div class="nsinput-field">
                                <input autocomplete="new-password" placeholder=" " type="password" name="nias_manual_confirm_password" id="nias_manual_confirm_password" />
                                <label class="ns-radio-label" for="nias_manual_confirm_password">تکرار رمز عبور</label>
                        </div>
                        <span class="ns-text-small">حداقل ۸ کاراکتر، شامل حروف و اعداد</span>
                </div>';
                }
                ?>
                <style>
                        .nias-modal-box {
                                background-color: transparent;
                                backdrop-filter: none;
                                -webkit-backdrop-filter: none;
                        }

                        .nias-modal-box [data-elementor-post-type="elementor_library"] {
                                width: 100%;
                                height: 100%;
                        }

                        .nias-close-modal.container {
                                position: fixed;
                                height: 100vh;
                                width: 100vw;
                                top: 0;
                                right: 0;
                                display: block;
                                z-index: -9;
                        }

                        .elementor-editor-active .nias-main-modal,
                        .elementor-editor-active .nias-modal-box,
                        .elementor-editor-active .nias-login-code {
                                display: block !important;
                                width: auto !important;
                                height: auto !important;
                                transform: scale(1) !important;
                                opacity: 1 !important;
                        }

                        .elementor-editor-active .notif-in-editor-nias {
                                display: block !important;
                                text-align: center;
                                background-color: red;
                                color: white;
                        }

                        .elementor-editor-active .nias-main-modal .nsinput-field .ns-radio-label {
                                transform: translateY(var(--nias-label-ty, 6px)) translateX(var(--nias-label-tx, 0px)) scale(var(--nias-label-sc, 1));
                        }

                        .elementor-editor-active .nias-main-modal .nsinput-field input[type="text"]:focus~label,
                        .elementor-editor-active .nias-main-modal .nsinput-field input[type="password"]:focus~label,
                        .elementor-editor-active .nias-main-modal .nsinput-field input[type="tel"]:focus~label,
                        .elementor-editor-active .nias-main-modal .nsinput-field input[type="email"]:focus~label,
                        .elementor-editor-active .nias-main-modal .nsinput-field input[type="text"]:valid~label,
                        .elementor-editor-active .nias-main-modal .nsinput-field input[type="password"]:valid~label,
                        .elementor-editor-active .nias-main-modal .nsinput-field input[type="tel"]:valid~label,
                        .elementor-editor-active .nias-main-modal .nsinput-field input[type="email"]:not(:placeholder-shown)~label {
                                transform: translateY(var(--nias-label-ty-f, -14px)) translateX(var(--nias-label-tx-f, -5px)) scale(var(--nias-label-sc-f, 0.7)) !important;
                        }

                        <?php
                        if ('yes' === $settings['nias_show_success']) {
                                ?>
                                .elementor-editor-active .nias-success {
                                        display: flex !important;
                                }

                                <?php
                        }
                        ?>
                @keyframes nias-success-spin {
                        from { transform: rotate(0deg); }
                        to   { transform: rotate(360deg); }
                }

                #nias-toast-wrap {
                        position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
                        z-index: 2147483647; display: flex; flex-direction: column; align-items: center;
                        gap: 10px; pointer-events: none;
                        width: max-content; max-width: min(400px, calc(100vw - 32px));
                }
                .nias-toast {
                        display: flex; align-items: center; gap: 10px;
                        padding: 12px 16px; border-radius: 10px; font-size: 14px;
                        line-height: 1.5; direction: rtl;
                        font-family: inherit;
                        pointer-events: auto; box-shadow: 0 4px 24px rgba(0,0,0,.35);
                        opacity: 0; transform: translateY(-10px) scale(.96);
                        transition: opacity .25s ease, transform .25s ease;
                        min-width: 220px;
                }
                .nias-toast--show    { opacity: 1; transform: none; }
                .nias-toast--info    { background:#1e3a5f; color:#93c5fd; border:1px solid rgba(147,197,253,.25); }
                .nias-toast--success { background:#14532d; color:#86efac; border:1px solid rgba(134,239,172,.25); }
                .nias-toast--error   { background:#450a0a; color:#fca5a5; border:1px solid rgba(252,165,165,.25); }
                .nias-toast--warning { background:#431407; color:#fdba74; border:1px solid rgba(253,186,116,.25); }
                .nias-toast__icon { flex-shrink:0; font-size:15px; }
                .nias-toast__msg  { flex:1; }
                .nias-toast__close {
                        flex-shrink:0; background:none; border:none; cursor:pointer;
                        color:inherit; opacity:.55; font-size:18px; line-height:1;
                        font-family:inherit; padding:0; margin-right:auto;
                }
                .nias-toast__close:hover { opacity:1; }
                /* پیام‌ها با توست نمایش داده می‌شوند؛ ناحیه پیام درون‌خطی فقط کانال انتقال متن است و نباید دیده شود */
                .nias-main-modal.nias-elementor .nias-login-message { display: none !important; }
                </style>
                <div id="nias-toast-wrap"></div>
                <div class="nias-success" style="display: none;">
                        <?php if ('yes' === ($settings['nias_success_show_icon'] ?? '')) { ?>
                                <div class="nias-success-icon">
                                        <?php \Elementor\Icons_Manager::render_icon($settings['nias_success_icon'], ['aria-hidden' => 'true']); ?>
                                </div>
                        <?php } ?>
                        <p class="niaslogin-success-title"><?php echo esc_html($settings['nias_secondform_successtext']); ?></p>
                        <?php if ('yes' === ($settings['nias_success_show_loader'] ?? 'yes')) { ?>
                        <p class="niaslogin-success-subtitle">
                        <div class="elementor-icon">
                                <?php \Elementor\Icons_Manager::render_icon($settings['nias_secondform_successloader'], ['aria-hidden' => 'true']); ?>
                        </div>
                        <?php echo esc_html($settings['nias_secondform_successloadertext']); ?></p>
                        <?php } ?>
                </div>
                <?php
                if ('yes' === $settings['nias_close_outbox']) {
                        ?>
                        <div class="nias-close-modal container"></div>
                        <?php
                }
                ?>

                <div class="nias-main-modal nias-elementor" style="z-index:999999">


                        <?php
                        if ('yes' === $settings['nias_close_button']) {
                                ?>
                                <button class="nias-close-modal" type="button">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                                <path d="M12 22C17.5 22 22 17.5 22 12C22 6.5 17.5 2 12 2C6.5 2 2 6.5 2 12C2 17.5 6.5 22 12 22Z"
                                                        stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="M9.16998 14.83L14.83 9.17004" stroke="white" stroke-width="1.5" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                <path d="M14.83 14.83L9.16998 9.17004" stroke="white" stroke-width="1.5" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                        </svg>
                                        بستن
                                </button>
                                <?php
                        }
                        ?>

                        <div>
                                <form class="nias-login-form" id="nias-login">
                                        <div class="nias-login-form-logo">
                                                <?php
                                                echo \Elementor\Group_Control_Image_Size::get_attachment_image_html($settings, 'thumbnail', 'nias_firstform_image');
                                                ?>
                                        </div>
                                        <span class="nias-modal-title first-form"><?php echo esc_html($settings['nias_firstform_title']); ?></span>
                                        <p class="nias-subtitle" style="margin: 0;"><?php echo esc_html($settings['nias_firstform_subtitle']); ?>
                                        </p>
                                        <div class="nias-login-field">
                                                <?php
                                                if ($email_activate) {
                                                        ?>
                                                        <div class="nias-email-box nsinput-field">
                                                                <input id="nias_email_input" placeholder=" " type="email" name="ns_email" required>
                                                                <label class="ns-radio-label"
                                                                        for="nias_email_input"><?php echo esc_html($settings['nias_firstform_label']); ?></label>
                                                        </div>
                                                        <?php
                                                } elseif ($email_phone_activate) {
                                                        ?>
                                                        <div class="nsinput-field">
                                                                <input required="" placeholder=" " autocomplete="on" type="text" name="ns_email_phone"
                                                                        id="nias_email_phone_input" />
                                                                <label class="ns-radio-label"
                                                                        for="nias_email_phone_input"><?php echo esc_html($settings['nias_firstform_label']); ?></label>
                                                        </div>
                                                        <?php
                                                } else {
                                                        ?>
                                                        <label for="niasphoneinput"><?php echo esc_html($settings['nias_firstform_label']); ?></label>
                                                        <div class="nias-phone-box">
                                                                <svg class="nias-checkicon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                                        viewBox="0 0 24 24" fill="none">
                                                                        <path opacity="0.4"
                                                                                d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z"
                                                                                fill="#00C72C" />
                                                                        <path
                                                                                d="M10.5795 15.5801C10.3795 15.5801 10.1895 15.5001 10.0495 15.3601L7.21945 12.5301C6.92945 12.2401 6.92945 11.7601 7.21945 11.4701C7.50945 11.1801 7.98945 11.1801 8.27945 11.4701L10.5795 13.7701L15.7195 8.6301C16.0095 8.3401 16.4895 8.3401 16.7795 8.6301C17.0695 8.9201 17.0695 9.4001 16.7795 9.6901L11.1095 15.3601C10.9695 15.5001 10.7795 15.5801 10.5795 15.5801Z"
                                                                                fill="#00C72C" />
                                                                </svg>
                                                                <input id="niasphoneinput" maxlength="11" type="text" inputmode="tel"
                                                                        placeholder="<?php echo esc_html($settings['nias_firstform_placeholder']); ?>" name="phone"
                                                                        required>
                                                                <span><?php echo esc_html($settings['nias_firstform_countrycode']); ?></span>
                                                                <?php
                                                                echo \Elementor\Group_Control_Image_Size::get_attachment_image_html($settings, 'thumbnail', 'nias_firstform_phonnumberimage');
                                                                ?>
                                                        </div>
                                                        <?php
                                                }
                                                ?>

                                                <span class="nias-login-message" style="display:none"></span>
                                                <input type="hidden" name="action" value="nias_login">
                                                <?php wp_nonce_field('nias-login'); ?>
                                                <button>
                                                        <div class="elementor-icon">
                                                                <?php \Elementor\Icons_Manager::render_icon($settings['nias_firstform_icon_load'], ['aria-hidden' => 'true']); ?>
                                                        </div>
                                                        <?php
                                                        echo esc_html($settings['nias_firstform_button']);

                                                        ?>
                                                </button>
                                        </div>
                                </form>

                                <form class="nias-login-code" id="nias-code-form" autocomplete="one-time-code">
                                        <div class="nias-login-form-logo-code">
                                                <?php
                                                echo \Elementor\Group_Control_Image_Size::get_attachment_image_html($settings, 'thumbnail', 'nias_secondform_image');
                                                ?>
                                        </div>
                                        <?php
                                        // منطق جدید برای فرم تاییدیه بر اساس password_activate و password_otp_activate
                                        if ($password_otp_activate) {

                                                // اگر password_otp_activate فعال باشد، هردو تب (کد تایید و پسورد) نمایش داده می‌شوند
                                                ?>

                                                <section class="nias-tabs" role="tablist" aria-label="تب‌های محتوایی">
                                                        <!-- رادیوهای کنترل تب‌ها: تب اول = پسورد، تب دوم = کد تایید -->
                                                        <input checked type="radio" name="nias-tabs" id="nias-tab-1" aria-controls="nias-panel-1" />
                                                        <label id="nias-label-1" for="nias-tab-1" role="tab" aria-selected="true" tabindex="0">ورود
                                                                پسورد</label>

                                                        <input type="radio" name="nias-tabs" id="nias-tab-2" aria-controls="nias-panel-2" />
                                                        <label id="nias-label-2" for="nias-tab-2" role="tab" aria-selected="false" tabindex="-1">ورود با کد
                                                                تایید</label>

                                                        <!-- پنل‌های محتوا -->
                                                        <div class="nias-tabs__panels">
                                                                <!-- پنل اول: ورود با پسورد -->
                                                                <div id="nias-panel-1" class="nias-panel" role="tabpanel" aria-labelledby="nias-label-1">
                                                                        <span
                                                                                class="nias-modal-title second-form"><?php echo esc_html($settings['nias_secondform_passwordtitle']); ?></span>
                                                                        <p class="nias-login-subtitle"><?php echo esc_html($settings['nias_secondform_passwordsubtitle']); ?>
                                                                        </p>
                                                                        <div class="nsinput-field">
                                                                                <input
                                                                                        required=""
                                                                                        autocomplete="off"
                                                                                        type="password"
                                                                                        placeholder=" "
                                                                                        name="ns_password"
                                                                                        id="ns_password" />
                                                                                <label class="ns-radio-label" for="ns_password"><?php echo esc_html($settings['nias_secondform_passwordlabel']); ?></label>
                                                                        </div>

                                                                        <?php echo $nias_manual_password_block; ?>

                                                                        <button type="button" class="nias-forgot-password" id="nias-forgot-password">
                                                                                <?php echo esc_html($settings['nias_secondform_passwordbtn']); ?>
                                                                        </button>
                                                                        <span class="ns-text-small"><?php echo esc_html($settings['nias_secondform_passwordunderbtn']); ?></span>

                                                                        <?php if (get_option('nias_email_activate')) { ?>
                                                                                <input type="hidden" id="nias_forgot_email" name="nias_forgot_email" value="">
                                                                                <script>
                                                                                        document.addEventListener("DOMContentLoaded", function() {
                                                                                                const emailInput = document.getElementById("nias_email_input");
                                                                                                const hiddenField = document.getElementById("nias_forgot_email");
                                                                                                if (emailInput && hiddenField) {
                                                                                                        emailInput.addEventListener("input", function() {
                                                                                                                hiddenField.value = emailInput.value;
                                                                                                        });
                                                                                                }
                                                                                        });
                                                                                </script>
                                                                        <?php } elseif (get_option('nias_email_phone_activate')) { ?>
                                                                                <input type="hidden" id="nias_forgot_email_phone" name="nias_forgot_email_phone" value="">
                                                                                <script>
                                                                                        document.addEventListener("DOMContentLoaded", function() {
                                                                                                const input = document.getElementById("nias_email_phone_input");
                                                                                                const hiddenField = document.getElementById("nias_forgot_email_phone");
                                                                                                if (input && hiddenField) {
                                                                                                        input.addEventListener("input", function() {
                                                                                                                hiddenField.value = input.value;
                                                                                                        });
                                                                                                }
                                                                                        });
                                                                                </script>
                                                                        <?php } ?>
                                                                </div>

                                                                <!-- پنل دوم: ورود با کد تایید -->
                                                                <div id="nias-panel-2" class="nias-panel" role="tabpanel" aria-labelledby="nias-label-2">
                                                                        <span
                                                                                class="nias-modal-title second-form"><?php echo esc_html($settings['nias_secondform_title']); ?></span>
                                                                        <p class="nias-login-subtitle"><?php echo esc_html($settings['nias_secondform_subtitle']); ?>
                                                                        </p>
                                                                        <div class="nias-under-code">
                                                                                <div class="nias-resendbox">
                                                                                        <a class="nias-resend" href="#">ارسال مجدد</a>
                                                                                        <svg width="120" height="120" viewBox="0 0 120 120" class="nias-circle-counter">
                                                                                                <circle class="fill-neutral-light" cx="60" cy="60" r="40" stroke-width="10px"
                                                                                                        style="fill: #e5f4ff;"></circle>
                                                                                                <circle class="stroke-neutral-light fill-[none]" cx="60" cy="60" r="55"
                                                                                                        stroke-width="10px" style="stroke-linecap: round;stroke:#e5f4ff;fill: none;">
                                                                                                </circle>
                                                                                                <circle class="stroke-neutral-dark fill-[none] transition-all duration-300" cx="60"
                                                                                                        cy="60" r="55" stroke-width="10px" transform="rotate(-90 60 60)"
                                                                                                        style="stroke-dasharray: 345.575;stroke-dashoffset: 0;stroke-linecap: round;transition-duration: .3s;transition-property: all;stroke:#043ccc;fill: #e0000000;">
                                                                                                </circle>
                                                                                        </svg>
                                                                                        <svg class="nias-spinner-timer" fill="#043ccc" xmlns="http://www.w3.org/2000/svg"
                                                                                                width="20" height="20" viewBox="0 0 24 24">
                                                                                                <path
                                                                                                        d="M12,1A11,11,0,1,0,23,12,11,11,0,0,0,12,1Zm0,20a9,9,0,1,1,9-9A9,9,0,0,1,12,21Z">
                                                                                                </path>
                                                                                                <rect class="spinner_d9Sa spinner_qQQY" x="11" y="6" rx="1" width="2" height="7">
                                                                                                </rect>
                                                                                                <rect class="spinner_d9Sa spinner_pote" x="11" y="11" rx="1" width="2" height="9">
                                                                                                </rect>
                                                                                        </svg>
                                                                                        <span class="nias-countdown">00:00</span>
                                                                                </div>
                                                                        </div>
                                                                        <p class="nias-login-result">برای دریافت کد تایید روی این تب کلیک کنید</p>
                                                                        <div class="nias-login-field">
                                                                                <div class="nias-code-box">
                                                                                        <?php foreach (range(1, $digits) as $index): ?>
                                                                                                <input type="tel" inputmode="tel" pattern="[0-9]*" maxlength="1"
                                                                                                        autocomplete="one-time-code" aria-label="Digit <?php echo $index; ?>">
                                                                                        <?php endforeach; ?>
                                                                                </div>
                                                                        </div>
                                                                </div>

                                                        </div>
                                                </section>

                                                <?php
                                        } elseif ($password_activate) {
                                                // اگر فقط password_activate فعال باشد، فقط بخش وارد کردن پسورد نمایش داده می‌شود
                                                ?>
                                                <span
                                                        class="nias-modal-title second-form"><?php echo esc_html($settings['nias_secondform_passwordtitle']); ?></span>
                                                <p class="nias-login-subtitle"><?php echo esc_html($settings['nias_secondform_passwordsubtitle']); ?></p>
                                                <div class="nsinput-field">
                                                        <input placeholder=" " required="" autocomplete="off" type="password" name="ns_password"
                                                                id="ns_password" />
                                                        <label class="ns-radio-label"
                                                                for="ns_password"><?php echo esc_html($settings['nias_secondform_passwordlabel']); ?></label>
                                                </div>

                                                <?php echo $nias_manual_password_block; ?>

                                                <button type="button" class="nias-forgot-password" id="nias-forgot-password">
                                                        <?php echo esc_html($settings['nias_secondform_passwordbtn']); ?>
                                                </button>
                                                <span class="ns-text-small"><?php echo esc_html($settings['nias_secondform_passwordunderbtn']); ?></span>

                                                <!-- فیلد پنهان برای انتقال ایمیل/شماره -->
                                                <?php if ($email_activate) { ?>
                                                        <input type="hidden" id="nias_forgot_email" name="nias_forgot_email" value="">
                                                        <script>
                                                                document.addEventListener("DOMContentLoaded", function () {
                                                                        const emailInput = document.getElementById("nias_email_input");
                                                                        const hiddenField = document.getElementById("nias_forgot_email");
                                                                        if (emailInput && hiddenField) {
                                                                                emailInput.addEventListener("input", function () {
                                                                                        hiddenField.value = emailInput.value;
                                                                                });
                                                                        }
                                                                });
                                                        </script>
                                                <?php } elseif ($email_phone_activate) { ?>
                                                        <input type="hidden" id="nias_forgot_email_phone" name="nias_forgot_email_phone" value="">
                                                        <script>
                                                                document.addEventListener("DOMContentLoaded", function () {
                                                                        const input = document.getElementById("nias_email_phone_input");
                                                                        const hiddenField = document.getElementById("nias_forgot_email_phone");
                                                                        if (input && hiddenField) {
                                                                                input.addEventListener("input", function () {
                                                                                        hiddenField.value = input.value;
                                                                                });
                                                                        }
                                                                });
                                                        </script>
                                                <?php } ?>

                                                <?php
                                        } else {
                                                // اگر هیچکدام فعال نباشند، فقط بخش کد تایید نمایش داده می‌شود
                                                ?>
                                                <span
                                                        class="nias-modal-title second-form"><?php echo esc_html($settings['nias_secondform_title']); ?></span>
                                                <p class="nias-login-subtitle"><?php echo esc_html($settings['nias_secondform_subtitle']); ?></p>

                                                <div class="nias-under-code">
                                                        <div class="nias-resendbox">
                                                                <a class="nias-resend" href="#">ارسال مجدد</a>
                                                                <svg width="120" height="120" viewBox="0 0 120 120" class="nias-circle-counter">
                                                                        <circle class="fill-neutral-light" cx="60" cy="60" r="40" stroke-width="10px"
                                                                                style="fill: #e5f4ff;"></circle>
                                                                        <circle class="stroke-neutral-light fill-[none]" cx="60" cy="60" r="55" stroke-width="10px"
                                                                                style="stroke-linecap: round;stroke:#e5f4ff;fill: none;"></circle>
                                                                        <circle class="stroke-neutral-dark fill-[none] transition-all duration-300" cx="60" cy="60"
                                                                                r="55" stroke-width="10px" transform="rotate(-90 60 60)"
                                                                                style="stroke-dasharray: 345.575;stroke-dashoffset: 0;stroke-linecap: round;transition-duration: .3s;transition-property: all;stroke:#043ccc;fill: #e0000000;">
                                                                        </circle>
                                                                </svg>
                                                                <span class="nias-countdown">00:00</span>
                                                        </div>
                                                </div>


                                                <p class="nias-login-result"><?php echo esc_html($settings['nias_secondform_label']); ?></p>
                                                <div class="nias-login-field">
                                                        <div class="nias-code-box">
                                                                <?php foreach (range(1, $digits) as $index): ?>
                                                                        <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" name="one-time-code"
                                                                                autocomplete="one-time-code" aria-label="Digit <?php echo $index; ?>">
                                                                <?php endforeach; ?>
                                                        </div>
                                                </div>

                                                <?php
                                        }
                                        ?>


                                        <span class="nias-login-message" style="display:none"></span>

                                        <div class="nias-confirm-box">
                                                <button class="nias-confirm-code">
                                                        <input type="hidden" name="code" id="nias-verify-code" value="">
                                                        <input type="hidden" name="identifier" value="">
                                                        <input type="hidden" name="phone" value="">
                                                        <input type="hidden" name="_wpnonce" value="">
                                                        <input type="hidden" name="action" value="nias_verify">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                                                <path d="M12,1A11,11,0,1,0,23,12,11,11,0,0,0,12,1Zm0,20a9,9,0,1,1,9-9A9,9,0,0,1,12,21Z" />
                                                                <rect class="spinner_d9Sa spinner_qQQY" x="11" y="6" rx="1" width="2" height="7" />
                                                                <rect class="spinner_d9Sa spinner_pote" x="11" y="11" rx="1" width="2" height="9" />
                                                        </svg>
                                                        <?php echo esc_html($settings['nias_secondform_button']); ?>

                                                </button>
                                                <button type="button" class="nias-change-phone" id="nias-change-number">
                                                        <?php echo esc_html($settings['nias_secondform_editbutton']); ?>

                                                </button>
                                        </div>

                                        <?php
                                        // دکمه تمام‌عرض ورود با گوگل (در صورت فعال بودن) زیر دکمه‌های تایید کد و اصلاح
                                        if (function_exists('nias_google_login_button')) {
                                                echo nias_google_login_button();
                                        }
                                        ?>
                                </form>
                        </div>
                </div>
                <?php
        }
}
