<?php
    /**
     * @Filename: class-nh_Module.php
     * @Description This file contains the abstract class Nh_Module, which serves as the base module for NH applications.
     * It provides common functionality and methods that can be extended by specific modules.
     *
     * @User: NINJA MASTER - Mustafa Shaaban
     * @Date: 1/4/2023
     */

    namespace NH\APP\CLASSES;

    use NH\APP\HELPERS\Nh_Ajax_Response;
    use NH\APP\HELPERS\Nh_Hooks;

    /**
     * The abstract class Nh_Module is the base module for NH applications.
     *
     * @class abstract Nh_Module
     * @version 1.0
     * @since 1.0.0
     * @package NinjaHub
     * @author Mustafa Shaaban
     */
    abstract class Nh_Module extends Nh_Post
    {
        /**
         * @var Nh_Module|null The singleton instance of the Nh_Module class.
         */
        private static ?Nh_Module $instance = NULL;

        /**
         * @var Nh_Hooks The Nh_Hooks instance for managing hooks and actions.
         */
        protected Nh_Hooks $hooks;

        /**
         * @var string The module name.
         */
        protected string $module = '';

        /**
         * Constructs a new Nh_Module object.
         *
         * @param string $module_name The name of the module.
         */
        public function __construct(string $module_name)
        {
            parent::__construct();

            $this->module = $this->type = $module_name;
            $this->hooks  = new Nh_Hooks;

            $this->actions($module_name);
            $this->filters($module_name);

            $this->hooks->run();
        }

        /**
         * Returns the singleton instance of the Nh_Module class.
         *
         * @return Nh_Module The Nh_Module instance.
         */
        public static function get_instance(): Nh_Module
        {
            $class = __CLASS__;
            if (!self::$instance instanceof $class) {
                self::$instance = new $class;
            }

            return self::$instance;
        }

        /**
         * Sets up the actions for the module.
         *
         * @param string $module_name The name of the module.
         *
         * @return void
         * @since 1.0.0
         * @package NinjaHub
         * @version 1.0
         */
        abstract protected function actions(string $module_name): void;

        /**
         * Sets up the filters for the module.
         *
         * @param string $module_name The name of the module.
         *
         * @return void
         * @since 1.0.0
         * @package NinjaHub
         * @version 1.0
         */
        abstract protected function filters(string $module_name): void;

        /**
         * Retrieves all posts of the module.
         *
         * @param array  $status The post statuses to retrieve.
         * @param int    $limit The maximum number of posts to retrieve.
         * @param string $orderby The field to order the posts by.
         * @param string $order The order of the posts (ASC or DESC).
         * @param array  $not_in The post IDs to exclude from the results.
         *
         * @return array An array of Nh_Post objects representing the retrieved posts.
         * @since 1.0.0
         * @package NinjaHub
         * @version 1.0
         */
        public function get_all(array $status = [ 'any' ], int $limit = 10, string $orderby = 'ID', string $order = 'DESC', array $not_in = [ '0' ]): array
        {
            $posts     = new \WP_Query([
                "post_type"      => $this->module,
                "post_status"    => $status,
                "posts_per_page" => $limit,
                "orderby"        => $orderby,
                "not__in"        => $not_in,
                "order"          => $order,
            ]);
            $Nh_Posts = [];

            foreach ($posts->get_posts() as $post) {
                $Nh_Posts[] = $this->convert($post, $this->meta_data);
            }

            return $Nh_Posts;
        }

        /**
         * Retrieves a post of the module by its ID.
         *
         * @param int $post_id The ID of the post to retrieve.
         *
         * @return Nh_Post|\WP_Error The Nh_Post object representing the retrieved post, or a WP_Error object on failure.
         * @since 1.0.0
         * @package NinjaHub
         * @version 1.0
         */
        public function get_by_id(int $post_id = 0): Nh_Post|\WP_Error
        {
            $error = new \WP_Error();

            if ($post_id <= 0) {
                $error->add('invalid_id', __("No invalid post id", 'ninja'), [
                    'status'  => FALSE,
                    'details' => [ 'post_id' => $post_id ]
                ]);
                return $error;
            }

            $posts = get_post($post_id);

            if ($posts) {
                $Nh_Posts = $this->convert($posts, $this->meta_data);
            } else {
                $error->add('invalid_id', __("No posts available.", 'ninja'), [
                    'status'  => FALSE,
                    'details' => [ 'post_id' => $post_id ]
                ]);
                return $error;
            }

            return $this->assign($Nh_Posts);
        }

        /**
         * Retrieves posts of the module by their IDs.
         *
         * @param array $post_ids The IDs of the posts to retrieve.
         * @param array $status The post statuses to retrieve.
         *
         * @return array An array of Nh_Post objects representing the retrieved posts.
         * @since 1.0.0
         * @package NinjaHub
         * @version 1.0
         */
        public function get_by_ids(array $post_ids = [], array $status = [ 'publish' ]): array
        {
            $Nh_Posts = [];

            if (empty($post_ids)) {
                return $Nh_Posts;
            }

            $posts = new \WP_Query([
                "post__in"    => $post_ids,
                "post_type"   => $this->module,
                "post_status" => $status,
            ]);

            foreach ($posts->get_posts() as $post) {
                $Nh_Posts[] = $this->convert($post, $this->meta_data);
            }

            return $Nh_Posts;
        }

        /**
         * Retrieves the terms of a taxonomy.
         *
         * @param string $tax_name The name of the taxonomy.
         *
         * @return int|string|array|\WP_Error|\WP_Term The retrieved terms.
         * @version 1.0
         * @since 1.0.0
         * @package NinjaHub
         */
        public function get_taxonomy_terms(string $tax_name): int|string|array|\WP_Error|\WP_Term
        {
            return get_terms([
                'taxonomy'   => $tax_name,
                'hide_empty' => FALSE,
                // TODO:: Switch to TRUE on production
            ]);
        }

        /**
         * Assigns the properties of a Nh_Post object to the Nh_Module object.
         *
         * @param Nh_Post $obj The Nh_Post object to assign.
         *
         * @return Nh_Module The updated Nh_Module object.
         */
        public function assign(Nh_Post $obj): Nh_Module
        {
            $this->ID            = $obj->ID;
            $this->author        = $obj->author;
            $this->type          = $obj->type;
            $this->name          = $obj->name;
            $this->title         = $obj->title;
            $this->content       = $obj->content;
            $this->excerpt       = $obj->excerpt;
            $this->status        = $obj->status;
            $this->parent        = $obj->parent;
            $this->created_date  = $obj->created_date;
            $this->modified_date = $obj->modified_date;
            $this->thumbnail     = $obj->thumbnail;
            $this->link          = $obj->link;
            $this->taxonomy      = $obj->taxonomy;

            foreach ($obj->meta_data as $name => $value) {
                $this->set_meta_data($name, $value);
            }

            return $this;
        }

        /**
         * Handles the loadmore AJAX request.
         *
         * @return void
         * @since 1.0.0
         * @package NinjaHub
         * @version 1.0
         */
        public function loadmore_ajax(): void
        {

            $page = intval($_POST['data']);

            $articles = $this->load_more([ 'publish' ], $page);
            $last     = FALSE;

            if ($page * 10 >= $articles['count']) {
                $last = TRUE;
            }

            ob_start();
            foreach ($articles as $key => $article) {
                if ('count' === $key) {
                    continue;
                }
                get_template_part('template-parts/post-ajax/archive', 'loadmore', [ 'data' => $article ]);
            }
            $html = ob_get_clean();

            new Nh_Ajax_Response(TRUE, __('Successful Response!', 'ninja'), [
                'html' => $html,
                'last' => $last
            ]);
        }

        /**
         * Retrieves additional posts for the loadmore functionality.
         *
         * @param array  $status The post statuses to retrieve.
         * @param int    $page The current page of posts.
         * @param int    $limit The maximum number of posts to retrieve.
         * @param string $order The order of the posts (ASC or DESC).
         * @param array  $author The array of authors.
         *
         * @return array An array of Nh_Post objects representing the retrieved posts, including a 'count' key with the total count of posts.
         * @since 1.0.0
         * @package NinjaHub
         * @version 1.0
         */
        public function load_more(array $status = [ 'any' ], int $page = 1, int $limit = 10, string $order = 'DESC', array $author = []): array
        {
            $posts     = new \WP_Query([
                "post_type"      => $this->module,
                "post_status"    => $status,
                "posts_per_page" => $limit,
                "orderby"        => 'ID',
                "order"          => $order,
                "paged"          => $page,
                "author__in"     => $author,
            ]);
            $Nh_Posts = [];

            foreach ($posts->get_posts() as $post) {
                $Nh_Posts[] = $this->convert($post, $this->meta_data);
            }

            $Nh_Posts['count'] = $posts->found_posts;

            return $Nh_Posts;
        }

    }