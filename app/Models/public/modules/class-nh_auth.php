<?php
    /**
     * @Filename: class-nh_auth.php
     * @Description:
     * @User: NINJA MASTER - Mustafa Shaaban
     * @Date: 5/10/2023
     */

    namespace NH\APP\MODELS\FRONT\MODULES;

    use NH\APP\CLASSES\Nh_User;
    use NH\APP\HELPERS\Nh_Ajax_Response;
    use NH\APP\HELPERS\Nh_Cryptor;
    use NH\APP\HELPERS\Nh_Hooks;
    use NH\APP\HELPERS\Nh_Mail;
    use NH\APP\MODELS\FRONT\Nh_Public;
    use NH\Nh;
    use WP_Error;

    /**
     * Description...
     *
     * @class Nh_Auth
     * @version 1.0
     * @since 1.0.0
     * @package NinjaHub
     * @author Mustafa Shaaban
     */
    class Nh_Auth extends Nh_User
    {

        /**
         * @var object|\NH\APP\HELPERS\Nh_Hooks
         */
        protected object $hooks;

        public function __construct()
        {
            parent::__construct();
            $this->hooks = new Nh_Hooks;

            $this->shortcodes();
            $this->actions();
            $this->filters();

            $this->hooks->run();
        }

        private function shortcodes(): void
        {
        }

        private function actions(): void
        {
            // TODO: Implement actions() method.
            $this->hooks->add_action('wp_login', $this, 'after_wp_login');
            $this->hooks->add_action('wp_ajax_nopriv_' . Nh::_DOMAIN_NAME . '_registration_ajax', $this, 'registration_ajax');
            $this->hooks->add_action('wp_ajax_nopriv_' . Nh::_DOMAIN_NAME . '_registration_student_ajax', $this, 'registration_student_ajax');
            $this->hooks->add_action('wp_ajax_nopriv_' . Nh::_DOMAIN_NAME . '_verify_ajax', $this, 'verify_ajax');
            $this->hooks->add_action('wp_ajax_' . Nh::_DOMAIN_NAME . '_interests_ajax', $this, 'interests_ajax');
            $this->hooks->add_action('wp_ajax_nopriv_' . Nh::_DOMAIN_NAME . '_interests_ajax', $this, 'interests_ajax');
            $this->hooks->add_action('wp_ajax_nopriv_' . Nh::_DOMAIN_NAME . '_login_ajax', $this, 'login_ajax');
            $this->hooks->add_action('wp_ajax_nopriv_' . Nh::_DOMAIN_NAME . '_change_password_ajax', $this, 'change_password_ajax');
            $this->hooks->add_action('wp_ajax_nopriv_' . Nh::_DOMAIN_NAME . '_forgot_password_ajax', $this, 'forgot_password_ajax');
        }

        private function filters(): void
        {
            // TODO: Implement filters() method.
            $this->hooks->add_filter('template_redirect', $this, 'restrict_redirections');
        }

        public function after_wp_login()
        {

        }

        /**
         * Description...
         * @version 1.0
         * @since 1.0.0
         * @package NinjaHub
         * @author Mustafa Shaaban
         * @return void
         * @throws \Exception
         */
        public function registration_ajax(): void
        {
            $form_data    = $_POST['data'];
            $full_name    = sanitize_text_field($form_data['full_name']);
            $user_email   = sanitize_text_field($form_data['user_email']);
            $phone_number = sanitize_text_field($form_data['phone_number']);
            $school_name  = sanitize_text_field($form_data['school_name']);
            $location     = sanitize_text_field($form_data['location']);
            $venue        = sanitize_text_field($form_data['venue']);
            // $user_password                 = sanitize_text_field($form_data['user_password']);
            // $confirm_password              = sanitize_text_field($form_data['confirm_password']);
            $recaptcha_response            = sanitize_text_field($form_data['g-recaptcha-response']);
            $_POST["g-recaptcha-response"] = $recaptcha_response;

            if (is_user_logged_in()) {
                new Nh_Ajax_Response(FALSE, __('You are already logged In!.', 'ninja'));
            }

            if (empty($form_data)) {
                new Nh_Ajax_Response(FALSE, __("Can't register with empty credentials.", 'ninja'));
            }

            if (!wp_verify_nonce($form_data['registration_nonce'], Nh::_DOMAIN_NAME . "_registration_form")) {
                new Nh_Ajax_Response(FALSE, __("Something went wrong!.", 'ninja'));
            }

            if (empty($full_name)) {
                new Nh_Ajax_Response(FALSE, __("The full name field shouldn't be empty!.", 'ninja'));
            }

            if (empty($user_email)) {
                new Nh_Ajax_Response(FALSE, __("The E-mail field shouldn't be empty!.", 'ninja'));
            }
            // elseif(explode('@', $user_email)[1] !== 'moe.eg'){
            //     new Nh_Ajax_Response(FALSE, __("This is not MOE email account!.", 'ninja'));
            // }

            if (empty($phone_number)) {
                new Nh_Ajax_Response(FALSE, __("The phone number field is empty!.", 'ninja'));
            }

            if (empty($school_name)) {
                new Nh_Ajax_Response(FALSE, __("The school name field is empty!.", 'ninja'));
            }

            if (empty($location)) {
                new Nh_Ajax_Response(FALSE, __("The location field is empty!.", 'ninja'));
            }

            if (empty($venue)) {
                new Nh_Ajax_Response(FALSE, __("The venue field is empty!.", 'ninja'));
            }

            $check_result = apply_filters('gglcptch_verify_recaptcha', TRUE, 'string', 'platform_registration');

            if ($check_result !== TRUE) {
                new Nh_Ajax_Response(FALSE, __($check_result, 'ninja')); /* the reCAPTCHA answer  */
            }

            $this->username = $phone_number;
            // $this->password     = $user_password;
            $this->email        = $user_email;
            $this->display_name = ucfirst(strtolower($full_name));
            $this->role         = self::COACH;
            $this->first_name   = ucfirst(strtolower($full_name));
            $this->nickname     = ucfirst(strtolower($full_name));

            $this->set_user_meta('nickname', $this->nickname);

            $user = $this->insert();

            if (is_wp_error($user)) {
                new Nh_Ajax_Response(FALSE, $user->get_error_message());
            }

            $user->profile->set_meta_data('phone_number', $phone_number);
            $user->profile->set_meta_data('school_name', $school_name);
            $user->profile->set_meta_data('location', $location);
            $user->profile->set_meta_data('venue', $venue);
            $user->profile->set_meta_data('profile_type', 'coach');
            $user->profile->set_meta_data('rate', 5);
            $user->profile->update();


            ob_start();
            get_template_part('app/Views/modals-ajax/email-verification-popup', NULL, [ 'email' => $user_email ]);
            $html = ob_get_clean();
            new Nh_Ajax_Response(TRUE, __('Your account has been created successfully, Please check your E-mail to activate your account', 'ninja'), [
                'redirect_url' => apply_filters('nhml_permalink', get_permalink(get_page_by_path('account/login'))),
                'html'         => $html
            ]);
        }


        /**
         * Description...
         * @version 1.0
         * @since 1.0.0
         * @package NinjaHub
         * @author Mustafa Shaaban
         * @return void
         * @throws \Exception
         */
        public function registration_student_ajax(): void
        {
            $form_data                     = $_POST['data'];
            $full_name                     = sanitize_text_field($form_data['full_name']);
            $user_email                    = sanitize_text_field($form_data['user_email']);
            $phone_number                  = sanitize_text_field($form_data['phone_number']);
            $school_name                   = sanitize_text_field($form_data['school_name']);
            $student_age                   = sanitize_text_field($form_data['student_age']);
            $location                      = sanitize_text_field($form_data['location']);
            $user_password                 = sanitize_text_field($form_data['user_password']);
            $confirm_password              = sanitize_text_field($form_data['confirm_password']);
            $student_data                  = sanitize_text_field($form_data['student_data']);
            $recaptcha_response            = sanitize_text_field($form_data['g-recaptcha-response']);
            $_POST["g-recaptcha-response"] = $recaptcha_response;

            if (is_user_logged_in()) {
                new Nh_Ajax_Response(FALSE, __('You are already logged In!.', 'ninja'));
            }

            if (empty($form_data)) {
                new Nh_Ajax_Response(FALSE, __("Can't register with empty credentials.", 'ninja'));
            }

            if (!wp_verify_nonce($form_data['registration_student_nonce'], Nh::_DOMAIN_NAME . "_registration_student_form")) {
                new Nh_Ajax_Response(FALSE, __("Something went wrong!.", 'ninja'));
            }

            if (empty($full_name)) {
                new Nh_Ajax_Response(FALSE, __("The full name field shouldn't be empty!.", 'ninja'));
            }

            if (empty($user_email)) {
                new Nh_Ajax_Response(FALSE, __("The E-mail field shouldn't be empty!.", 'ninja'));
            }
            // elseif(explode('@', $user_email)[1] !== 'moe.eg'){
            //     new Nh_Ajax_Response(FALSE, __("This is not MOE email account!.", 'ninja'));
            // }

            if (empty($phone_number)) {
                new Nh_Ajax_Response(FALSE, __("The phone number field is empty!.", 'ninja'));
            }

            if (empty($student_age)) {
                new Nh_Ajax_Response(FALSE, __("The age field is empty!.", 'ninja'));
            }

            if (empty($school_name)) {
                new Nh_Ajax_Response(FALSE, __("The school name field is empty!.", 'ninja'));
            }

            if (empty($location)) {
                new Nh_Ajax_Response(FALSE, __("The location field is empty!.", 'ninja'));
            }

            if (empty($user_password)) {
                new Nh_Ajax_Response(FALSE, __("The password field is empty!.", 'ninja'));
            }

            if (empty($confirm_password)) {
                new Nh_Ajax_Response(FALSE, __("The confirm password field shouldn't be empty!.", 'ninja'));
            }

            if ($user_password !== $confirm_password) {
                new Nh_Ajax_Response(FALSE, __("The passwords should be identical!.", 'ninja'));
            }

            $check_result = apply_filters('gglcptch_verify_recaptcha', TRUE, 'string', 'platform_registration_student');

            if ($check_result !== TRUE) {
                new Nh_Ajax_Response(FALSE, __($check_result, 'ninja')); /* the reCAPTCHA answer  */
            }

            if (!empty($student_data) && is_serialized(Nh_Cryptor::Decrypt($student_data))) {
                $key = unserialize(Nh_Cryptor::Decrypt($student_data));

                if (!(new Nh_Team())->is_profile_exists('player', 'player_email', $key['player_email']) || !(new Nh_Team())->is_profile_exists('player', 'phone_number', $key['student_phone_number'])) {
                    new Nh_Ajax_Response(FALSE, __("Invalid player data.", 'ninja'));
                }
            }

            $this->username     = $phone_number;
            $this->password     = $user_password;
            $this->email        = $user_email;
            $this->display_name = ucfirst(strtolower($full_name));
            $this->role         = empty($student_data) ? self::STUDENT : self::PLAYER;
            $this->first_name   = ucfirst(strtolower($full_name));
            $this->nickname     = ucfirst(strtolower($full_name));

            $this->set_user_meta('nickname', $this->nickname);

            $user = $this->insert();


            if (is_wp_error($user)) {
                new Nh_Ajax_Response(FALSE, $user->get_error_message());
            }

            if (empty($student_data)) {

                $user->profile->set_meta_data('phone_number', $phone_number);
                $user->profile->set_meta_data('player_name', $full_name);
                $user->profile->set_meta_data('age', $student_age);
                $user->profile->set_meta_data('school_name', $school_name);
                $user->profile->set_meta_data('location', $location);
                $user->profile->set_meta_data('profile_type', 'student');
                $user->profile->set_meta_data('rate', 5);
                $user->profile->update();

            }

            new Nh_Ajax_Response(TRUE, __('Your account has been created successfully, Please check your E-mail to activate your account', 'ninja'), [
                'redirect_url' => apply_filters('nhml_permalink', get_permalink(get_page_by_path('account/login'))),
            ]);
        }


        /**
         * Description...
         * @version 1.0
         * @since 1.0.0
         * @package NinjaHub
         * @author Mustafa Shaaban
         * @return void
         * @throws \Exception
         */
        public function login_ajax(): void
        {

            $form_data                     = $_POST['data'];
            $user_login                    = sanitize_text_field($form_data['user_login']);
            $user_password                 = sanitize_text_field($form_data['user_password']);
            $recaptcha_response            = sanitize_text_field($form_data['g-recaptcha-response']);
            $_POST["g-recaptcha-response"] = $recaptcha_response;


            if (is_user_logged_in()) {
                new Nh_Ajax_Response(FALSE, __('You are already logged In!.', 'ninja'));
            }

            if (empty($form_data)) {
                new Nh_Ajax_Response(FALSE, __("Can't login with empty credentials.", 'ninja'));
            }

            if (!wp_verify_nonce($form_data['login_nonce'], Nh::_DOMAIN_NAME . "_login_form")) {
                new Nh_Ajax_Response(FALSE, __("Something went wrong!.", 'ninja'));
            }

            if (empty($user_login)) {
                new Nh_Ajax_Response(FALSE, __("The username field is empty!.", 'ninja'));
            }

            if (empty($user_password)) {
                new Nh_Ajax_Response(FALSE, __("The password field shouldn't be empty!.", 'ninja'));
            }

            $check_result = apply_filters('gglcptch_verify_recaptcha', TRUE, 'string', 'platform_login');

            if ($check_result !== TRUE) {
                new Nh_Ajax_Response(FALSE, __($check_result, 'ninja')); /* the reCAPTCHA answer  */
            }

            $user   = $this->login();
            $admins = [
                self::ADMIN,
            ];

            if (is_wp_error($user)) {
                new Nh_Ajax_Response(FALSE, $user->get_error_message(), $user->get_error_data());
            }

            if (in_array($user->role, $admins)) {
                $redirect_url = get_admin_url();
            } elseif ($user->role == Nh_User::STUDENT || $user->role == Nh_User::PLAYER) {
                if (empty($user->profile->meta_data['has_interests']) && $user->role == Nh_User::STUDENT) {
                    $redirect_url = apply_filters('nhml_permalink', get_permalink(get_page_by_path('account/profile/interests')));
                } else {
                    $redirect_url = apply_filters('nhml_permalink', get_permalink(get_page_by_path('account/profile/student')));
                }
            } else {
                $redirect_url = apply_filters('nhml_permalink', get_permalink(get_page_by_path('account/profile/fitting-room')));
            }

            new Nh_Ajax_Response(TRUE, __('You have been logged in successfully.', 'ninja'), [
                'redirect_url' => $redirect_url
            ]);
        }

        /**
         * Description...
         * @version 1.0
         * @since 1.0.0
         * @package NinjaHub
         * @author Mustafa Shaaban
         * @return void
         */
        public function change_password_ajax(): void
        {

            $form_data                     = $_POST['data'];
            $user_password                 = sanitize_text_field($form_data['user_password']);
            $user_password_confirm         = sanitize_text_field($form_data['user_password_confirm']);
            $recaptcha_response            = sanitize_text_field($form_data['g-recaptcha-response']);
            $_POST["g-recaptcha-response"] = $recaptcha_response;

            if (empty($form_data)) {
                new Nh_Ajax_Response(FALSE, __("Can't login with empty credentials.", 'ninja'));
            }

            if (!wp_verify_nonce($form_data['change_password_nonce'], Nh::_DOMAIN_NAME . "_change_password_form")) {
                new Nh_Ajax_Response(FALSE, __("Something went wrong!.", 'ninja'));
            }

            if (empty($user_password)) {
                new Nh_Ajax_Response(FALSE, __("The password field is empty!.", 'ninja'));
            }

            if (empty($user_password_confirm)) {
                new Nh_Ajax_Response(FALSE, __("The confirm password field is empty!.", 'ninja'));
            }

            if ($user_password !== $user_password_confirm) {
                new Nh_Ajax_Response(FALSE, __("Your password is not identical!.", 'ninja'));
            }

            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{8,})/', $user_password)) {
                new Nh_Ajax_Response(FALSE, __("Your password is not complex enough!", 'ninja'));
            }

            $check_result = apply_filters('gglcptch_verify_recaptcha', TRUE, 'string', 'platform_reset_password');

            if ($check_result !== TRUE) {
                new Nh_Ajax_Response(FALSE, __($check_result, 'ninja')); /* the reCAPTCHA answer  */
            }

            $user = $this->change_password();

            if (is_wp_error($user)) {
                new Nh_Ajax_Response(FALSE, $user->get_error_message(), $user->get_error_data());
            }

            new Nh_Ajax_Response(TRUE, sprintf(__('Your password has been changed successfully!. you can login with your new password from <a href="%s">here</a>', 'ninja'), get_permalink(get_page_by_path('account/login'))), [
                'redirect_url' => get_permalink(get_page_by_path('account/login'))
            ]);
        }

        /**
         * Description...
         * @version 1.0
         * @since 1.0.0
         * @package NinjaHub
         * @author Mustafa Shaaban
         * @return void
         */
        public function restrict_redirections(): void
        {
            global $user_ID, $wp;

            // restrict user from accessing the crud pages
            if (is_page([
                    'login',
                    'registration',
                    'registration-landing',
                    'forgot-password',
                    'reset-password',
                    'verification',
                ]) && is_user_logged_in()) {
                $url = apply_filters('nhml_permalink', get_permalink(get_page_by_path('dashboard')));
                wp_safe_redirect($url);
                exit();
            }

            // restrict user from accessing the crud pages
            if (is_page([
                'verification',
            ])) {

                $key          = $_GET['key'] ?? '';
                $decrypt_data = Nh_Cryptor::Decrypt($key); // Decrypt the reset code

                if ($decrypt_data && is_serialized($decrypt_data)) {
                    $reset_data = unserialize($decrypt_data);
                    $status     = get_user_meta($reset_data['user_id'], 'account_verification_status', TRUE);
                    if ((int)$status === 1) {
                        $url = apply_filters('nhml_permalink', get_permalink(get_page_by_path('account/login')));
                        wp_safe_redirect($url);
                        exit();
                    }
                }
            }

            // prevent accessing the sensitive pages
            if (is_page([
                    'account',
                    'dashboard'
                ]) && !is_user_logged_in()) {
                $url = apply_filters('nhml_permalink', get_permalink(get_page_by_path('account/login')));
                wp_safe_redirect($url);
                exit();
            }

        }

        /**
         * Description...
         * @version 1.0
         * @since 1.0.0
         * @package NinjaHub
         * @author Ahmed Gamal
         * @return void
         */
        public function forgot_password_ajax(): void
        {

            $form_data                     = $_POST['data'];
            $user_email                    = sanitize_text_field($form_data['user_email']);
            $recaptcha_response            = sanitize_text_field($form_data['g-recaptcha-response']);
            $_POST["g-recaptcha-response"] = $recaptcha_response;

            $check_result = apply_filters('gglcptch_verify_recaptcha', TRUE, 'string', 'platform_forgot_password');

            if ($check_result !== TRUE) {
                new Nh_Ajax_Response(FALSE, __($check_result, 'ninja'));/* the reCAPTCHA answer */
            }

            if (is_user_logged_in()) {
                new Nh_Ajax_Response(FALSE, __('You are already logged In!.', 'ninja'));
            }

            if (empty($form_data)) {
                new Nh_Ajax_Response(FALSE, __("Can't login with empty credentials.", 'ninja'));
            }

            if (!wp_verify_nonce($form_data['forgot_nonce'], Nh::_DOMAIN_NAME . "_forgot_form")) {
                new Nh_Ajax_Response(FALSE, __("Something went wrong!.", 'ninja'));
            }

            if (empty($user_email)) {
                new Nh_Ajax_Response(FALSE, __("The email field is empty!.", 'ninja'));
            }

            if (!preg_match('/^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/', $user_email)) {
                new Nh_Ajax_Response(FALSE, __("Your email address is not a valid email!", 'ninja'));
            }

            $user = $this->forgot_password($user_email);

            if (is_wp_error($user)) {
                new Nh_Ajax_Response(FALSE, $user->get_error_message(), $user->get_error_data());
            }

            new Nh_Ajax_Response(TRUE, __('Email has been sent successfully!.', 'ninja'), [
                'redirect_url' => home_url()
            ]);
        }

        public function verify_ajax(): void
        {
            $form_data                     = $_POST['data'];
            $full_name                     = sanitize_text_field($form_data['full_name']);
            $phone_number                  = sanitize_text_field($form_data['phone_number']);
            $school_name                   = sanitize_text_field($form_data['school_name']);
            $location                      = sanitize_text_field($form_data['location']);
            $venue                         = sanitize_text_field($form_data['venue']);
            $user_password                 = sanitize_text_field($form_data['user_password']);
            $confirm_password              = sanitize_text_field($form_data['confirm_password']);
            $recaptcha_response            = sanitize_text_field($form_data['g-recaptcha-response']);
            $decrypt_data                  = Nh_Cryptor::Decrypt(sanitize_text_field($form_data['user_reset_data']));
            $reset_data                    = unserialize($decrypt_data); // Unserialize the decrypted data
            $user                          = Nh_User::get_user_by('ID', (int)$reset_data['user_id']); // Get the user associated with the reset code
            $_POST["g-recaptcha-response"] = $recaptcha_response;

            if (is_user_logged_in()) {
                new Nh_Ajax_Response(FALSE, __('You are already logged In!.', 'ninja'));
            }

            if (empty($form_data)) {
                new Nh_Ajax_Response(FALSE, __("Can't register with empty credentials.", 'ninja'));
            }

            if (!wp_verify_nonce($form_data['verify_nonce'], Nh::_DOMAIN_NAME . "_verify_form")) {
                new Nh_Ajax_Response(FALSE, __("Something went wrong!.", 'ninja'));
            }

            if (empty($full_name)) {
                new Nh_Ajax_Response(FALSE, __("The full name field shouldn't be empty!.", 'ninja'));
            }

            if (empty($phone_number)) {
                new Nh_Ajax_Response(FALSE, __("The phone number field is empty!.", 'ninja'));
            }

            if (empty($school_name)) {
                new Nh_Ajax_Response(FALSE, __("The school name field is empty!.", 'ninja'));
            }

            if (empty($location)) {
                new Nh_Ajax_Response(FALSE, __("The location field is empty!.", 'ninja'));
            }

            if (empty($venue)) {
                new Nh_Ajax_Response(FALSE, __("The venue field is empty!.", 'ninja'));
            }

            if (empty($user_password)) {
                new Nh_Ajax_Response(FALSE, __("The password field is empty!.", 'ninja'));
            }

            if (empty($confirm_password)) {
                new Nh_Ajax_Response(FALSE, __("The confirm password field shouldn't be empty!.", 'ninja'));
            }

            if ($user_password !== $confirm_password) {
                new Nh_Ajax_Response(FALSE, __("The passwords should be identical!.", 'ninja'));
            }

            $check_result = apply_filters('gglcptch_verify_recaptcha', TRUE, 'string', 'frontend_verify');

            if ($check_result !== TRUE) {
                new Nh_Ajax_Response(FALSE, __($check_result, 'ninja')); /* the reCAPTCHA answer  */
            }

            $user->username = $phone_number;
            $user->password = $user_password;
            wp_set_password($user_password, $user->ID);
            $user->display_name = ucfirst(strtolower($full_name));
            $user->role         = self::COACH;
            $user->first_name   = ucfirst(strtolower($full_name));
            $user->nickname     = ucfirst(strtolower($full_name));

            $user->set_user_meta('nickname', $user->nickname);

            if (is_wp_error($user)) {
                new Nh_Ajax_Response(FALSE, $user->get_error_message());
            }

            $user->profile->set_meta_data('phone_number', $phone_number);
            $user->profile->set_meta_data('school_name', $school_name);
            $user->profile->set_meta_data('location', $location);
            $user->profile->set_meta_data('venue', $venue);
            $user->profile->set_meta_data('profile_type', 'coach');
            $user->profile->set_meta_data('rate', 5);
            $user->profile->update();
            $user->update();

            $check_key = Nh_User::check_verification_key($form_data['user_reset_data']);

            ob_start();
            get_template_part('app/Views/modals-ajax/email-verified-popup');
            $html = ob_get_clean();
            if (!is_wp_error($check_key)) {
                new Nh_Ajax_Response(TRUE, __('Your account has been verified successfully', 'ninja'), [
                    'redirect_url' => apply_filters('nhml_permalink', get_permalink(get_page_by_path('account/login'))),
                    'html'         => $html
                ]);
            } else {
                new Nh_Ajax_Response(FALSE, $check_key->get_error_message(), []);
            }
        }

        public function interests_ajax(): void
        {
            global $user_ID;
            $form_data                     = $_POST['data'];
            $favorite_sport                = sanitize_text_field($form_data['favorite_sport']);
            $participatory_sport           = sanitize_text_field($form_data['participatory_sport']);
            $joined_club                   = sanitize_text_field($form_data['joined_club']);
            $sports_activities             = $form_data['sports_activities'];
            $recaptcha_response            = sanitize_text_field($form_data['g-recaptcha-response']);
            $user                          = Nh_User::get_user_by('ID', (int)$user_ID);
            $_POST["g-recaptcha-response"] = $recaptcha_response;

            if (empty($form_data)) {
                new Nh_Ajax_Response(FALSE, __("Can't apply with empty credentials.", 'ninja'));
            }

            if (!wp_verify_nonce($form_data['interests_nonce'], Nh::_DOMAIN_NAME . "_interests_form")) {
                new Nh_Ajax_Response(FALSE, __("Something went wrong!.", 'ninja'));
            }


            $check_result = apply_filters('gglcptch_verify_recaptcha', TRUE, 'string', 'frontend_verify');

            if ($check_result !== TRUE) {
                new Nh_Ajax_Response(FALSE, __($check_result, 'ninja')); /* the reCAPTCHA answer  */
            }

            if (is_wp_error($user)) {
                new Nh_Ajax_Response(FALSE, $user->get_error_message());
            }
            if (!empty($favorite_sport)) {
                $user->profile->set_meta_data('favorite_sport', $favorite_sport);
            }
            if (!empty($participatory_sport)) {
                $user->profile->set_meta_data('participatory_sport', $participatory_sport);
            }
            if (!empty($joined_club)) {
                $user->profile->set_meta_data('joined_club', $joined_club);
            }
            if (!empty($sports_activities)) {
                $user->profile->set_meta_data('sports_activities', $sports_activities);
            }
            $user->profile->set_meta_data('has_interests', 1);
            $user->profile->update();
            $user->update();


            new Nh_Ajax_Response(TRUE, __('Your account has been updated successfully', 'ninja'), [
                'redirect_url' => apply_filters('nhml_permalink', get_permalink(get_page_by_path('account/profile/student'))),
            ]);
        }

    }
