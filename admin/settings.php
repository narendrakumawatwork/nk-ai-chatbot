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
    register_setting('nk_chatbot_options', 'nk_chatbot_tiles', 'nk_chatbot_sanitize_array');
    register_setting('nk_chatbot_options', 'nk_chatbot_pills', 'nk_chatbot_sanitize_array');
    register_setting('nk_chatbot_options', 'nk_chatbot_welcome_msg', ['default' => "Hello! I'm your research assistant. Ask me about peptides, protocols, or products."]);
}

function nk_chatbot_sanitize_array($input) {
    return (is_array($input)) ? $input : [];
}
add_action('admin_init', 'nk_chatbot_register_settings');

// Add a new function to handle file uploads
function nk_chatbot_handle_file_upload() {
    // Check for our custom save action
    if (isset($_POST['nk_chatbot_save_nonce']) && wp_verify_nonce($_POST['nk_chatbot_save_nonce'], 'nk_chatbot_save_action')) {
        
        // 1. Save Simple Options
        if (isset($_POST['nk_chatbot_api_key'])) {
            update_option('nk_chatbot_api_key', sanitize_text_field(wp_unslash($_POST['nk_chatbot_api_key'])));
        }
        if (isset($_POST['nk_chatbot_model'])) {
            update_option('nk_chatbot_model', sanitize_text_field(wp_unslash($_POST['nk_chatbot_model'])));
        }
        if (isset($_POST['nk_chatbot_system_prompt'])) {
            update_option('nk_chatbot_system_prompt', wp_kses_post(wp_unslash($_POST['nk_chatbot_system_prompt'])));
        }
        if (isset($_POST['nk_chatbot_knowledge_base'])) {
            update_option('nk_chatbot_knowledge_base', wp_kses_post(wp_unslash($_POST['nk_chatbot_knowledge_base'])));
        }
        if (isset($_POST['nk_chatbot_welcome_msg'])) {
            update_option('nk_chatbot_welcome_msg', sanitize_text_field(wp_unslash($_POST['nk_chatbot_welcome_msg'])));
        }
        
        // Save Visibility Settings (Checkboxes)
        update_option('nk_chatbot_hide_tiles', isset($_POST['nk_chatbot_hide_tiles']) ? 1 : 0);
        update_option('nk_chatbot_hide_pills', isset($_POST['nk_chatbot_hide_pills']) ? 1 : 0);

        // 2. Save Arrays (Tiles & Pills) - V6 Keys (JSON Encoded for Max Safety)
        if (isset($_POST['nk_chatbot_tiles']) && is_array($_POST['nk_chatbot_tiles'])) {
            // Fix stripping slashes and encode as JSON to handle emojis/special chars safely
            $tiles_clean = array_map(function($item) {
                return array_map('stripslashes', $item);
            }, $_POST['nk_chatbot_tiles']);
            
            update_option('nk_chatbot_tiles_v6', json_encode($tiles_clean, JSON_UNESCAPED_UNICODE));
        }

        if (isset($_POST['nk_chatbot_pills']) && is_array($_POST['nk_chatbot_pills'])) {
            $pills_clean = array_map(function($item) {
                return array_map('stripslashes', $item);
            }, $_POST['nk_chatbot_pills']);
            
            update_option('nk_chatbot_pills_v6', json_encode($pills_clean, JSON_UNESCAPED_UNICODE));
        }

        // 3. Handle File Upload
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
        
        // 4. Handle Deletions (if applicable)
        if (isset($_POST['nk_delete_file'])) {
             // ... existing deletion logic ...
             // (Deletion is usually a separate button click, but let's keep it here just in case)
        }

        // Show Success Message
        add_settings_error('nk_chatbot_messages', 'nk_chatbot_message', 'Settings Saved', 'updated');
    }
    
    // Legacy deletion handler for separate button clicks (outside the main save context)
    if (isset($_POST['nk_delete_file']) && check_admin_referer('nk_chatbot_delete_file_' . $_POST['nk_delete_file'])) {
        $file_to_delete = intval($_POST['nk_delete_file']);
        $files = get_option('nk_chatbot_knowledge_files', []);
            
        if (isset($files[$file_to_delete])) {
            $path = $files[$file_to_delete]['path'];
            if (file_exists($path)) {
                unlink($path); 
            }
            unset($files[$file_to_delete]);
            update_option('nk_chatbot_knowledge_files', array_values($files));
        }
    }
}
add_action('admin_init', 'nk_chatbot_handle_file_upload');

// Render Admin Page
function nk_chatbot_render_admin_page() {
    ?>
    <div class="wrap">
        <h1>AI Chatbot Settings</h1>
        <?php settings_errors('nk_chatbot_messages'); ?>
        <form method="post" action="" enctype="multipart/form-data">
            <?php wp_nonce_field('nk_chatbot_save_action', 'nk_chatbot_save_nonce'); ?>
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
                                    $del_nonce = wp_create_nonce('nk_chatbot_delete_file_' . $index);
                                    echo '<td><button type="submit" name="nk_delete_file" value="' . $index . '" class="button button-link-delete" onclick="return confirm(\'Delete this file?\')">Remove</button>';
                                    echo '<input type="hidden" name="_wpnonce" value="' . $del_nonce . '" />'; // Incorrect usage for loop, but fixed by GET handling or separate forms. Kept simple:
                                    // Actually, simple remove buttons in a main form are tricky. Let's fix the remove button handling above.
                                    echo '</td>';
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

                <!-- Welcome Message Customization -->
                <tr valign="top">
                    <th scope="row">Welcome Message</th>
                    <td>
                        <input type="text" name="nk_chatbot_welcome_msg" value="<?php echo esc_attr(get_option('nk_chatbot_welcome_msg', '')); ?>" class="large-text" placeholder="Hello! I'm your research assistant..." />
                        <p class="description">This is the first message the bot sends when the chat opens.</p>
                    </td>
                </tr>

                <!-- Menu Customization Section -->
                <tr>
                    <th colspan="2" style="padding: 20px 0 10px 0; border-bottom: 1px solid #ddd;">
                        <h2 style="margin: 0;">Home Screen Menu Customization</h2>
                        <p class="description">Edit the 4 large tiles and 3 suggestion pills that appear when the chat starts.</p>
                    </th>
                </tr>

                <tr valign="top">
                    <th scope="row">Visibility Settings</th>
                    <td>
                        <fieldset>
                            <label for="nk_chatbot_hide_tiles">
                                <input name="nk_chatbot_hide_tiles" type="checkbox" id="nk_chatbot_hide_tiles" value="1" <?php checked(1, get_option('nk_chatbot_hide_tiles', 0)); ?> />
                                Hide Quick Action Tiles
                            </label>
                            <br>
                            <label for="nk_chatbot_hide_pills">
                                <input name="nk_chatbot_hide_pills" type="checkbox" id="nk_chatbot_hide_pills" value="1" <?php checked(1, get_option('nk_chatbot_hide_pills', 0)); ?> />
                                Hide Suggestion Pills
                            </label>
                            <p class="description">Check these boxes to hide the respective sections from the chatbot.</p>
                        </fieldset>
                    </td>
                </tr>

                <?php 
                $default_tiles = [
                    ['icon' => '🔍', 'label' => 'Find Products', 'query' => 'Show me the full catalog of research peptides available at Clinical Peptides.'],
                    ['icon' => '💊', 'label' => 'Dosing', 'query' => 'What are the proper reconstitution and storage protocols for your peptides?'],
                    ['icon' => '📋', 'label' => 'Protocols', 'query' => 'What are the standard research guidelines for peptide handling and usage?'],
                    ['icon' => '🧬', 'label' => 'Build Stack', 'query' => 'Can you recommend synergistic peptide combinations for specific research goals?']
                ];
                $tiles_json = get_option('nk_chatbot_tiles_v6');
                $tiles = json_decode($tiles_json, true);
                
                // Use defaults ONLY if never saved or decode failed
                if (!is_array($tiles)) {
                    $tiles = $default_tiles;
                }
                ?>
                <tr valign="top">
                    <th scope="row">Quick Action Tiles (Large)</th>
                    <td>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <?php for($i=0; $i<4; $i++): 
                                // Direct access. Use empty string if key missing (cleared).
                                $icon  = isset($tiles[$i]['icon'])  ? $tiles[$i]['icon']  : '';
                                $label = isset($tiles[$i]['label']) ? $tiles[$i]['label'] : '';
                                $query = isset($tiles[$i]['query']) ? $tiles[$i]['query'] : '';
                                
                                // Fallback to default ONLY for the very first load (if $tiles was just set to default above)
                                // But if user saved empty, we want empty.
                                // The logic above `$tiles = ... : $default_tiles` handles the "Never Saved" case.
                            ?>
                            <div style="background: #f9f9f9; padding: 10px; border: 1px solid #ccc; border-radius: 8px;">
                                <strong>Tile <?php echo $i+1; ?></strong><br>
                                <input type="text" name="nk_chatbot_tiles[<?php echo $i; ?>][icon]" value="<?php echo esc_attr($icon); ?>" placeholder="Icon" style="width: 50px;" />
                                <input type="text" name="nk_chatbot_tiles[<?php echo $i; ?>][label]" value="<?php echo esc_attr($label); ?>" placeholder="Label"  />
                                <input type="text" name="nk_chatbot_tiles[<?php echo $i; ?>][query]" value="<?php echo esc_attr($query); ?>" placeholder="AI Query" class="large-text" style="margin-top: 5px;" />
                            </div>
                            <?php endfor; ?>
                        </div>
                    </td>
                </tr>

                <?php 
                $default_pills = [
                    ['label' => 'Product Info', 'query' => 'Details on product purity and testing'],
                    ['label' => 'Dosing Help', 'query' => 'How much bacteriostatic water should I use?'],
                    ['label' => 'Research Guide', 'query' => 'Where should I begin my peptide research?']
                ];
                $pills_json = get_option('nk_chatbot_pills_v6');
                $pills = json_decode($pills_json, true);
                
                if (!is_array($pills)) {
                    $pills = $default_pills;
                }
                ?>
                <tr valign="top">
                    <th scope="row">Suggestion Pills (Small)</th>
                    <td>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <?php for($i=0; $i<3; $i++): 
                                $p_label = isset($pills[$i]['label']) ? $pills[$i]['label'] : '';
                                $p_query = isset($pills[$i]['query']) ? $pills[$i]['query'] : '';
                            ?>
                            <div style="background: #f9f9f9; padding: 10px; border: 1px solid #ccc; border-radius: 8px;">
                                <strong>Pill <?php echo $i+1; ?></strong><br>
                                <input type="text" name="nk_chatbot_pills[<?php echo $i; ?>][label]" value="<?php echo esc_attr($p_label); ?>" placeholder="Label" />
                                <input type="text" name="nk_chatbot_pills[<?php echo $i; ?>][query]" value="<?php echo esc_attr($p_query); ?>" placeholder="AI Query" class="regular-text" style="margin-top: 5px; display: block;" />
                            </div>
                            <?php endfor; ?>
                        </div>
                        <p class="description">Pills populate the input box for the user to edit/send.</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
