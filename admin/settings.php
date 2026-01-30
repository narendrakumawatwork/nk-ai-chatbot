<?php
/**
 * Admin REST API & Settings for NK AI Chatbot
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add Admin Menu
function nk_chatbot_admin_menu() {
    add_menu_page(
        'AI Chatbot',
        'AI Chatbot',
        'manage_options',
        'nk-chatbot-settings',
        'nk_chatbot_render_admin_page',
        'dashicons-superhero',
        100
    );
}
add_action('admin_menu', 'nk_chatbot_admin_menu');

// Register Settings
function nk_chatbot_register_settings() {
    register_setting('nk_chatbot_options', 'nk_chatbot_api_key');
    register_setting('nk_chatbot_options', 'nk_chatbot_system_prompt');
    register_setting('nk_chatbot_options', 'nk_chatbot_knowledge_base');
    register_setting('nk_chatbot_options', 'nk_chatbot_model', ['default' => 'gpt-4-turbo']);
}
add_action('admin_init', 'nk_chatbot_register_settings');

// Add a new function to handle file uploads
function nk_chatbot_handle_file_upload() {
    // Check if the form was submitted and it's our settings page
    if (isset($_POST['option_page']) && $_POST['option_page'] === 'nk_chatbot_options') {
        
        // 1. Handle Deletions
        if (isset($_POST['nk_delete_file'])) {
            $file_to_delete = $_POST['nk_delete_file'];
            $files = get_option('nk_chatbot_knowledge_files', []);
            
            if (isset($files[$file_to_delete])) {
                $path = $files[$file_to_delete]['path'];
                if (file_exists($path)) {
                    unlink($path); // Remove physical file
                }
                unset($files[$file_to_delete]);
                update_option('nk_chatbot_knowledge_files', array_values($files)); // Re-index array
            }
        }

        // 2. Handle New File Upload
        if (isset($_FILES['nk_chatbot_file']) && !empty($_FILES['nk_chatbot_file']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            
            $uploaded_file = $_FILES['nk_chatbot_file'];
            $upload_overrides = array('test_form' => false);
            
            $move_file = wp_handle_upload($uploaded_file, $upload_overrides);
            
            if ($move_file && !isset($move_file['error'])) {
                $files = get_option('nk_chatbot_knowledge_files', []);
                $files[] = [
                    'name' => basename($move_file['file']),
                    'path' => $move_file['file'],
                    'url'  => $move_file['url'],
                    'time' => current_time('mysql')
                ];
                update_option('nk_chatbot_knowledge_files', $files);
            }
        }
    }
}
add_action('admin_init', 'nk_chatbot_handle_file_upload');

// Render Admin Page
function nk_chatbot_render_admin_page() {
    ?>
    <div class="wrap">
        <h1>AI Chatbot Settings</h1>
        <form method="post" action="options.php" enctype="multipart/form-data">
            <?php settings_fields('nk_chatbot_options'); ?>
            <?php do_settings_sections('nk_chatbot_options'); ?>
            
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">OpenAI API Key</th>
                    <td>
                        <input type="password" name="nk_chatbot_api_key" value="<?php echo esc_attr(get_option('nk_chatbot_api_key')); ?>" class="regular-text" />
                        <p class="description">Enter your OpenAI API Secret Key (sk-...).</p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">AI Model</th>
                    <td>
                        <select name="nk_chatbot_model">
                            <option value="gpt-3.5-turbo" <?php selected(get_option('nk_chatbot_model'), 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                            <option value="gpt-4" <?php selected(get_option('nk_chatbot_model'), 'gpt-4'); ?>>GPT-4</option>
                            <option value="gpt-4-turbo" <?php selected(get_option('nk_chatbot_model'), 'gpt-4-turbo'); ?>>GPT-4 Turbo (Recommended)</option>
                        </select>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">System Prompt</th>
                    <td>
                        <textarea name="nk_chatbot_system_prompt" rows="8" cols="50" class="large-text code"><?php echo esc_textarea(get_option('nk_chatbot_system_prompt', 'You are Clinical Assistant, a specialized peptide research assistant for Clinical Peptides store.')); ?></textarea>
                        <p class="description">Core instructions (Brand name, refusal rules, etc.).</p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Knowledge Base (Training Data)</th>
                    <td>
                        <textarea name="nk_chatbot_knowledge_base" rows="10" cols="50" class="large-text code"><?php echo esc_textarea(get_option('nk_chatbot_knowledge_base', '')); ?></textarea>
                        <p class="description">Paste important dosages/protocols here for quick reference.</p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Upload Knowledge Documents</th>
                    <td>
                        <input type="file" name="nk_chatbot_file" accept=".txt,.csv,.json" />
                        <p class="description">Upload .txt, .csv, or .json files. Each file you upload will be added to the Knowledge Base list below.</p>
                        
                        <div style="margin-top: 20px; background: #fff; border: 1px solid #ccd0d4; padding: 15px; border-radius: 4px;">
                            <h3 style="margin-top: 0;">Uploaded Knowledge Files</h3>
                            <?php 
                            $files = get_option('nk_chatbot_knowledge_files', []);
                            if (!empty($files)) {
                                echo '<table class="widefat fixed striped" style="border:none; box-shadow:none;">';
                                echo '<thead><tr><th>File Name</th><th>Upload Date</th><th style="width: 80px;">Action</th></tr></thead>';
                                echo '<tbody>';
                                foreach ($files as $index => $file) {
                                    echo '<tr>';
                                    echo '<td>' . esc_html($file['name']) . '</td>';
                                    echo '<td>' . esc_html($file['time']) . '</td>';
                                    echo '<td><button type="submit" name="nk_delete_file" value="' . $index . '" class="button button-link-delete" onclick="return confirm(\'Delete this file?\')">Remove</button></td>';
                                    echo '</tr>';
                                }
                                echo '</tbody></table>';
                            } else {
                                echo '<p>No files uploaded yet.</p>';
                            }
                            ?>
                        </div>

                        <div style="margin-top: 20px; background: #f0f6fb; border-left: 4px solid #11a0d2; padding: 15px;">
                            <strong>💡 What is this for?</strong>
                            <p style="margin-bottom: 0;">These documents act as the <b>AI's Brain</b>. When a user asks a question (like "How to use BPC-157?"), the AI will scan all these files to find the specific research data, clinical protocols, and product details you've uploaded. This ensures the AI gives accurate answers based on your actual business data rather than general information.</p>
                        </div>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
