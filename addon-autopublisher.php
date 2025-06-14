<?php
/**
 * Plugin Name: EPOCA Autopublisher for WordPress
 * Description: Ripubblica automaticamente i post più vecchi ogni ora, con impostazioni personalizzabili (categorie, parola chiave) e anteprima in backend.
 * Version: 1.4
 * Author: Salvatore Capolupo
 * Text Domain: epoca-autopublisher
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Epoca_Autopublisher {
    /** Singleton instance */
    private static $instance;

    /** Option key */
    const OPTION_KEY = 'epoca_autopublisher_settings';
    /** Cron hook */
    const CRON_HOOK  = 'epoca_republish_event';

    /** Retrieve singleton */
    public static function instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
            self::$instance->setup_hooks();
        }
        return self::$instance;
    }

    /** Register hooks */
    private function setup_hooks() {
        // Activation/deactivation
        register_activation_hook( __FILE__, [ $this, 'activate' ] );
        register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );

        // Admin
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_notices', [ $this, 'admin_notice_preview' ] );

        // Cron
        add_filter( 'cron_schedules', [ $this, 'add_hourly_schedule' ] );
        add_action( self::CRON_HOOK, [ $this, 'republish_oldest_post' ] );
    }

    /** Activation hook */
    public function activate() {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time(), 'epoca_hourly', self::CRON_HOOK );
        }
    }

    /** Deactivation hook */
    public function deactivate() {
        $timestamp = wp_next_scheduled( self::CRON_HOOK );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::CRON_HOOK );
        }
    }

    /** Add hourly schedule */
    public function add_hourly_schedule( $schedules ) {
        $schedules['epoca_hourly'] = [
            'interval' => HOUR_IN_SECONDS,
            'display'  => __( 'Ogni ora', 'epoca-autopublisher' ),
        ];
        return $schedules;
    }

    /** Register settings */
    public function register_settings() {
        register_setting(
            'epoca_autopublisher',
            self::OPTION_KEY,
            [ 'sanitize_callback' => [ $this, 'sanitize_settings' ] ]
        );

        add_settings_section(
            'epoca_section_main',
            __( 'Impostazioni', 'epoca-autopublisher' ),
            '__return_false',
            'epoca-autopublisher'
        );

        add_settings_field(
            'categories',
            __( 'Categorie', 'epoca-autopublisher' ),
            [ $this, 'field_categories' ],
            'epoca-autopublisher',
            'epoca_section_main'
        );

        add_settings_field(
            'keyword',
            __( 'Parola filtro', 'epoca-autopublisher' ),
            [ $this, 'field_keyword' ],
            'epoca-autopublisher',
            'epoca_section_main'
        );

        add_settings_field(
            'preview',
            __( 'Prossimo da ripubblicare', 'epoca-autopublisher' ),
            [ $this, 'field_preview' ],
            'epoca-autopublisher',
            'epoca_section_main'
        );
    }

    /** Sanitize settings input */
    public function sanitize_settings( $input ) {
        $output = [];
        if ( isset( $input['categories'] ) && is_array( $input['categories'] ) ) {
            $output['categories'] = array_map( 'intval', $input['categories'] );
        }
        if ( isset( $input['keyword'] ) ) {
            $output['keyword'] = sanitize_text_field( $input['keyword'] );
        }
        return $output;
    }

    /** Add settings page */
    public function add_settings_page() {
        add_menu_page(
            __( 'EPOCA Autopublisher', 'epoca-autopublisher' ),
            __( 'EPOCA Autopublisher', 'epoca-autopublisher' ),
            'manage_options',
            'epoca-autopublisher',
            [ $this, 'settings_page_html' ],
            'dashicons-update'
        );
    }

    /** Settings page HTML */
    public function settings_page_html() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        echo '<div class="wrap"><h1>' . esc_html__( 'EPOCA Autopublisher', 'epoca-autopublisher' ) . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields( 'epoca_autopublisher' );
        do_settings_sections( 'epoca-autopublisher' );
        submit_button();
        echo '</form></div>';
    }

    /** Categories field */
    public function field_categories() {
        $opts = get_option( self::OPTION_KEY, [] );
        $selected = $opts['categories'] ?? [];
        foreach ( get_categories( ['hide_empty' => false] ) as $cat ) {
            printf(
                '<label><input type="checkbox" name="%1$s[categories][]" value="%2$d" %3$s> %4$s</label><br>',
                esc_attr( self::OPTION_KEY ),
                $cat->term_id,
                checked( in_array( $cat->term_id, $selected ), true, false ),
                esc_html( $cat->name )
            );
        }
    }

    /** Keyword field */
    public function field_keyword() {
        $opts = get_option( self::OPTION_KEY, [] );
        printf(
            '<input type="text" name="%1$s[keyword]" value="%2$s" placeholder="es. WordPress" class="regular-text">',
            esc_attr( self::OPTION_KEY ),
            esc_attr( $opts['keyword'] ?? '' )
        );
    }

    /** Preview field */
    public function field_preview() {
        $post = $this->get_next_post_to_publish();
        if ( $post ) {
            printf(
                '<p><strong>%1$s</strong> (ID: %2$d) <a href="%3$s" target="_blank">%4$s</a></p>',
                esc_html( get_the_title( $post->ID ) ),
                $post->ID,
                esc_url( get_edit_post_link( $post->ID ) ),
                esc_html__( 'Modifica', 'epoca-autopublisher' )
            );
        } else {
            echo '<p>' . esc_html__( 'Nessun post trovato.', 'epoca-autopublisher' ) . '</p>';
        }
    }

    /** Admin notice on settings page */
    public function admin_notice_preview() {
        if ( empty( $_GET['page'] ) || 'epoca-autopublisher' !== $_GET['page'] ) {
            return;
        }
        $post = $this->get_next_post_to_publish();
        if ( $post ) {
            printf(
                '<div class="notice notice-info"><p>%s <a href="%s" target="_blank">%s</a></p></div>',
                esc_html__( 'Prossimo post da ripubblicare:', 'epoca-autopublisher' ),
                esc_url( get_edit_post_link( $post->ID ) ),
                esc_html( get_the_title( $post->ID ) )
            );
        }
    }

    /** Fetch next post */
    private function get_next_post_to_publish() {
        $opts = get_option( self::OPTION_KEY, [] );
        $args = [
            'posts_per_page' => 1,
            'orderby'        => 'date',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        ];
        if ( ! empty( $opts['categories'] ) ) {
            $args['category__in'] = $opts['categories'];
        }
        if ( ! empty( $opts['keyword'] ) ) {
            $args['s'] = $opts['keyword'];
        }
        $posts = get_posts( $args );
        return $posts[0] ?? false;
    }

    /** Republish */
    public function republish_oldest_post() {
        $post = $this->get_next_post_to_publish();
        if ( ! $post ) {
            return;
        }
        wp_update_post([
            'ID'        => $post->ID,
            'post_date'=> current_time( 'mysql' ),
        ]);
    }
}

// Boot plugin
Epoca_Autopublisher::instance();
