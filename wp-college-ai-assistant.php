<?php
/*
Plugin Name: WP College AI Assistant
Plugin URI: https://github.com/millaw/wp-college-ai-assistant
Description: A WordPress plugin that provides AI assistance for college-related tasks.
Version: 1.0
Author: Milla Wynn
Author URI: https://github.com/millaw
License: GPLv3
*/

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Autoload classes
spl_autoload_register(function ($class_name) {
    $file = plugin_dir_path(__FILE__) . 'classes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Handle OllamaStream requests
if (isset($_GET['ollama_stream'])) {
    OllamaStream::handle_request();
    exit;
}

// Route search requests
if (isset($_GET['search_query'])) {
    OllamaStream::handle_search($_GET['search_query']);
    exit;
}

// Route AI queries
if (isset($_GET['ai_query'])) {
    OllamaStream::handle_ai_query($_GET['ai_query']);
    exit;
}

// Route PDF search requests
if (isset($_GET['pdf_search'])) {
    OllamaStream::handle_pdf_search($_GET['pdf_search']);
    exit;
}

class WPCollegeAIAssistant {
    public function __construct() {
        // Initialize hooks and actions
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_shortcode('college_ai_assistant', [$this, 'render_shortcode']);
    }

    public function init() {
        // Initialization code for the plugin
    }

    public function enqueue_assets() {
        wp_enqueue_style('wp-college-ai-assistant-style', plugin_dir_url(__FILE__) . 'assets/css/style.css');
        wp_enqueue_script('wp-college-ai-assistant-script', plugin_dir_url(__FILE__) . 'assets/js/script.js', ['jquery'], null, true);
    }

    public function add_admin_menu() {
        add_options_page(
            'WP College AI Assistant Settings',
            'AI Assistant',
            'manage_options',
            'wp-college-ai-assistant',
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['api_url'], $_POST['api_key'])) {
            update_option('wp_college_ai_assistant_api_url', sanitize_text_field($_POST['api_url']));
            update_option('wp_college_ai_assistant_api_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>WP College AI Assistant Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="api_url">API URL</label></th>
                        <td><input name="api_url" type="text" id="api_url" value="<?php echo esc_attr(API_URL); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="api_key">API Key</label></th>
                        <td><input name="api_key" type="text" id="api_key" value="<?php echo esc_attr(API_KEY); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_shortcode() {
        ob_start();
        ?>
        <div class="wp-college-ai-assistant">
            <div class="chat-container">
                <div class="theme-switch">
                    <label class="switch">
                        <input type="checkbox" id="theme-toggle" checked />
                        <span class="slider"></span>
                    </label>
                    <span class="mode-text">Light Mode</span>
                </div>

                <div id="chat-messages">
                    <div class="bot-message">🤖 Hello! How can I help you?</div>
                </div>

                <div class="input-area">
                    <input type="text" id="question-input" placeholder="Type your message..." />
                    <button id="ask-button">Send</button>
                </div>
                <div id="loader" class="hidden">Thinking...</div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize the plugin
new WPCollegeAIAssistant();

// Use multisite-compatible functions for settings
if (is_multisite()) {
    define('API_URL', get_site_option('wp_college_ai_assistant_api_url', API_URL));
    define('API_KEY', get_site_option('wp_college_ai_assistant_api_key', API_KEY));
} else {
    define('API_URL', get_option('wp_college_ai_assistant_api_url', API_URL));
    define('API_KEY', get_option('wp_college_ai_assistant_api_key', API_KEY));
}
?>
