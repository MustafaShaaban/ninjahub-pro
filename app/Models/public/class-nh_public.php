<?php
    /**
     * @Filename: class-nh_public.php
     * @Description:
     * @User: NINJA MASTER - Mustafa Shaaban
     * @Date: 25/10/2021
     */

    namespace NH\APP\MODELS\FRONT;

    use NH\APP\CLASSES\Nh_Init;
    use NH\APP\HELPERS\Nh_Hooks;
    use NH\Nh;

    /**
     * Description...
     *
     * @class Nh_Public
     * @version 1.0
     * @since 1.0.0
     * @package NinjaHub
     * @author Mustafa Shaaban
     */
    class Nh_Public
    {
        /**
         * @var \NH\APP\HELPERS\Nh_Hooks
         */
        private Nh_Hooks $hooks;

        /**
         * @param \NH\APP\HELPERS\Nh_Hooks $hooks
         */
        public function __construct(Nh_Hooks $hooks)
        {
            $this->hooks = $hooks;
            $this->actions();
            $this->filters();
            Nh_Init::get_instance()
                    ->run('public');

        }

        public function actions(): void
        {
            $this->hooks->add_action('wp_enqueue_scripts', $this, 'enqueue_styles');
            $this->hooks->add_action('wp_enqueue_scripts', $this, 'enqueue_scripts');
            $this->hooks->add_action('init', $this, 'init', 1);
            $this->hooks->run();
        }

        public function filters(): void
        {
            $this->hooks->add_filter('nhml_permalink', $this, 'nhml_permalink', 10, 1);
            $this->hooks->run();
        }

        public function enqueue_styles(): void
        {
            $this->hooks->add_style(Nh::_DOMAIN_NAME . '-public-style-fontawesome', Nh_Hooks::PATHS['public']['vendors'] . '/css/fontawesome/css/all.min', TRUE);
            if (NH_lANG === 'ar') {
                $this->hooks->add_style(Nh::_DOMAIN_NAME . '-public-style-bs5', Nh_Hooks::PATHS['public']['vendors'] . '/css/bootstrap5/bootstrap.rtl.min', TRUE);
                $this->hooks->add_style(Nh::_DOMAIN_NAME . '-public-style-main', Nh_Hooks::PATHS['root']['css'] . '/style-rtl');
            } else {
                $this->hooks->add_style(Nh::_DOMAIN_NAME . '-public-style-bs5', Nh_Hooks::PATHS['public']['vendors'] . '/css/bootstrap5/bootstrap.min', TRUE);
                $this->hooks->add_style(Nh::_DOMAIN_NAME . '-public-style-main', Nh_Hooks::PATHS['root']['css'] . '/style');
            }

            $this->hooks->run();
        }

        public function enqueue_scripts(): void
        {
            global $gglcptch_options;

            $this->hooks->add_script(Nh::_DOMAIN_NAME . '-public-script-bs5', Nh_Hooks::PATHS['public']['vendors'] . '/js/bootstrap5/bootstrap.min', [
                'jquery'
            ], Nh::_VERSION, NULL, TRUE);

            $this->hooks->add_script(Nh::_DOMAIN_NAME . '-public-script-main', Nh_Hooks::PATHS['public']['js'] . '/main', [
                'jquery',
                Nh::_DOMAIN_NAME . '-public-script-bs5'
            ]);

            $this->hooks->add_localization(Nh::_DOMAIN_NAME . '-public-script-main', 'nhGlobals', [
                'domain_key'  => Nh::_DOMAIN_NAME,
                'ajaxUrl'     => admin_url('admin-ajax.php'),
                'environment' => Nh::_ENVIRONMENT,
                'publicKey'   => $gglcptch_options['public_key'],
                'phrases'     => [
                    'default'        => __("This field is required.", "nh"),
                    'email'          => __("Please enter a valid email address.", "nh"),
                    'number'         => __("Please enter a valid number.", "nh"),
                    'equalTo'        => __("Please enter the same value again.", "nh"),
                    'maxlength'      => __("Please enter no more than {0} characters.", "nh"),
                    'minlength'      => __("Please enter at least {0} characters.", "nh"),
                    'max'            => __("Please enter a value less than or equal to {0}.", "nh"),
                    'min'            => __("Please enter a value greater than or equal to {0}.", "nh"),
                    'pass_regex'     => __("Your password must contain at least one lowercase letter, one uppercase letter, one digit, and one special character from the following: ! @ # $ % ^ & *.", "ninja"),
                    'phone_regex'    => __("Please enter a valid Phone number.", "ninja"),
                    'intlTelNumber'  => __("Please enter a valid International Telephone Number.", "ninja"),
                    'email_regex'    => __("Please enter a valid email address.", "ninja"),
                    'file_extension' => __("Please upload a file with a valid extension.", "ninja"),
                    'file_max_size'  => __("File size must be less than {0} KB", "ninja"),
                    'choices_select' => __("Press to select", "ninja"),
                    'noChoicesText'  => __("'No choices to choose from'", "ninja"),
                    'time_regex'     => __("Invalid time range format. Please use HH:mm AM/PM - HH:mm AM/PM", "ninja"),
                    'englishOnly'   => __("Only English text is allowed.", "ninja"),
                    'arabicOnly'   => __("Only Arabic text is allowed.", "ninja"),
                ]
            ]);

            if (is_front_page()) {
                $this->hooks->add_script(Nh::_DOMAIN_NAME . '-public-script-home', Nh_Hooks::PATHS['public']['js'] . '/home');
            }

            if (is_page([
                'account',
                'login',
                'registration',
                'registration-landing',
                'forgot-password',
                'reset-password',
                'verification',
            ])) {
                $this->hooks->add_script(Nh::_DOMAIN_NAME . '-public-script-authentication', Nh_Hooks::PATHS['public']['js'] . '/authentication');
            }

            $this->hooks->run();
        }

        /**
         * NH INIT
         */
        public function init(): void
        {
            session_start();
        }

        /**
         * Description...
         *
         * @param $url
         *
         * @version 1.0
         * @since 1.0.0
         * @package talents-spot
         * @author Mustafa Shaaban
         * @return string
         */
        public function nhml_permalink($url): string
        {
            global $user_ID, $wp;
            if (is_user_logged_in() && is_plugin_active('sitepress-multilingual-cms/sitepress.php')) {
                $user_site_language = get_user_meta($user_ID, 'site_language', TRUE);
                $user_site_language = empty($user_site_language) ? 'en' : $user_site_language;

                // Check if the current URL contains the Arabic slug ("/ar/") or the language parameter ("?lang=ar").
                if (!str_contains($url, "/$user_site_language/") && !str_contains($url, "?lang=$user_site_language")) {
                    $redirect_url = apply_filters('wpml_permalink', $url, $user_site_language); // Get the Arabic version of the current page or post URL.
                    if ($redirect_url) {
                        $url = $redirect_url;
                    }
                }
            }

            return $url;
        }

        /**
         * Description...
         * @version 1.0
         * @since 1.0.0
         * @package NinjaHub
         * @author Mustafa Shaaban
         * @return array
         */
        public static function get_available_languages(): array
        {
            $languages       = apply_filters('wpml_active_languages', NULL, 'orderby=id&order=desc');
            $languages_codes = [];

            if (!empty($languages)) {
                foreach ($languages as $l) {
                    $languages_codes[] = [
                        'code' => $l['language_code'],
                        'name' => $l['translated_name']
                    ];
                }
            }
            return $languages_codes;
        }
    }
