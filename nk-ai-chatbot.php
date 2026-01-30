<?php
/**
 * Plugin Name: Custom AI Chatbot
 * Description: A premium AI chatbot with "Deep Explore" theater mode, product cards.
 * Version: 1.0.0
 * Author: Mandasa
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define Constants
define('NK_CHATBOT_PATH', plugin_dir_path(__FILE__));
define('NK_CHATBOT_URL', plugin_dir_url(__FILE__));

// Enqueue Assets
function nk_chatbot_enqueue_assets() {
    // Enqueue Font (Inter)
    wp_enqueue_style('google-fonts-inter', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', [], null);

    // Main Chat CSS
    wp_enqueue_style('nk-chatbot-css', NK_CHATBOT_URL . 'assets/css/chat-widget.css', [], '1.0.0');

    // Main Chat JS
    wp_enqueue_script('nk-chatbot-js', NK_CHATBOT_URL . 'assets/js/chat-widget.js', [], '1.0.0', true);

    // Pass data to JS (Data, API Endpoint, etc.)
    wp_localize_script('nk-chatbot-js', 'nkChatbotConfig', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('nk_chatbot_nonce'),
        'assets'  => NK_CHATBOT_URL . 'assets/'
    ]);
}
add_action('wp_enqueue_scripts', 'nk_chatbot_enqueue_assets');

// Include Admin Settings
require_once NK_CHATBOT_PATH . 'admin/settings.php';

// Add Chat Container to Footer
function nk_chatbot_render_widget() {
    ?>
    <div id="nk-chatbot-container" class="nk-chatbot-closed">
        <!-- Floating Toggle Button -->
        <button id="nk-chatbot-toggle" aria-label="Open Chat">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
        </button>

        <!-- Chat Window (Main Widget) -->
        <div id="nk-chat-window">
            <!-- Header -->
            <div class="nk-chat-header">
                <div class="nk-header-info">
                    <!-- Reset/Back Button (Left Side) -->
                    <button id="nk-reset-menu" title="Go Back">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    </button>
                    <span class="nk-bot-avatar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 1 0 10 10 10 10 0 0 0-10-10z"></path><path d="M12 16v-4"></path><path d="M12 8h.01"></path></svg>
                    </span>
                    <span class="nk-bot-name">Clinical Assistant <small>(Beta)</small></span>
                </div>
                <div class="nk-header-actions">
                    <!-- Theater Mode Toggle -->
                    <button id="nk-theater-toggle" title="Deep Explore / Theater Mode">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                    </button>
                    <!-- Close Button -->
                    <button id="nk-chat-close" title="Close Chat">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
            </div>

            <!-- Messages Area -->
            <div id="nk-chat-messages">
                <!-- Welcome Message -->
                <div class="nk-message nk-message-bot">
                    <div class="nk-message-content">Hello! I'm your research assistant. Ask me about peptides, protocols, or products.</div>
                </div>
                
                <!-- Quick Actions / Starter Chips -->
                <div class="nk-quick-actions">
                    <button class="nk-chip" data-action="find_products">
                        <span class="nk-chip-icon">🔍</span> Find Products
                    </button>
                    <button class="nk-chip" data-action="dosing">
                        <span class="nk-chip-icon">💊</span> Dosing
                    </button>
                    <button class="nk-chip" data-action="protocols">
                        <span class="nk-chip-icon">📋</span> Protocols
                    </button>
                     <button class="nk-chip" data-action="build_stack">
                        <span class="nk-chip-icon">🧬</span> Build Stack
                    </button>
                </div>
            </div>

            <!-- Suggestion Pills -->
            <div class="nk-chat-pills">
                <button class="nk-pill" data-action="product_info">Product Info</button>
                <button class="nk-pill" data-action="dosing_help">Dosing Help</button>
                <button class="nk-pill" data-action="research_guide">Research Guide</button>
            </div>

            <!-- Input Area -->
            <div class="nk-chat-input-area">
                <input type="text" id="nk-chat-input" placeholder="Ask anything..." />
                <button id="nk-chat-send">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                </button>
            </div>
            
            <!-- Footer Branding (Removed as requested) -->
            <div class="nk-chat-footer"><strong>Note-</strong> This is a beta version. Responses may change.</div>
        </div>
    </div>
    <?php
}
add_action('wp_footer', 'nk_chatbot_render_widget');

// AJAX Handler for Chat
add_action('wp_ajax_nk_chat_response', 'nk_chat_response_handler');
add_action('wp_ajax_nopriv_nk_chat_response', 'nk_chat_response_handler');

function nk_chat_response_handler() {
    check_ajax_referer('nk_chatbot_nonce', 'nonce');
    
    $message = sanitize_text_field($_POST['message']);
    $history = isset($_POST['history']) ? json_decode(stripslashes($_POST['history']), true) : [];
    
    // Get Settings
    $api_key = get_option('nk_chatbot_api_key');
    $model = get_option('nk_chatbot_model', 'gpt-4-turbo');
    
    // 1. Search for Products in Database (Real Store Retrieval)
    $found_products = nk_search_products($message);
    
    // 2. Build Context for AI
    $product_context = "";
    if (!empty($found_products)) {
        $product_context = "I found these relevant products in our store:\n";
        foreach ($found_products as $p) {
            $product_context .= "- {$p['title']} ({$p['price']}): {$p['full_desc']}. Link: {$p['link']}\n";
        }
        $product_context .= "\nINSTRUCTION: Briefly explain the benefits of 1-2 relevant products at the end of your response as a recommendation for the user's research goal or query.";
    } else {
        $product_context = "No direct product matches found. If the user asked about a specific research goal (like weight loss or injury), explain that while we have many products, nothing matched those specific terms exactly in the current search. Encourage them to browse the full catalog.";
    }

    // 3. Strict System Prompt (LoopAI Style) with Inventory Context
    $knowledge_text = get_option('nk_chatbot_knowledge_base', '');
    $files = get_option('nk_chatbot_knowledge_files', []);
    $file_content = "";
    
    // Read from multiple uploaded files
    if (!empty($files) && is_array($files)) {
        foreach ($files as $file) {
            $path = $file['path'];
            if (file_exists($path)) {
                $content = file_get_contents($path);
                // Limit each file's content to avoid massive context
                $file_content .= "\n\n--- Source: " . $file['name'] . " ---\n" . mb_strimwidth($content, 0, 10000, "..."); 
            }
        }
    }

    $combined_knowledge = $knowledge_text . "\n\n" . $file_content;
    
    $base_prompt = "You are Clinical Assistant, a specialized peptide research assistant for Clinical Peptides store.
    
    CORE INSTRUCTIONS:
    - Prioritize information in the 'OFFICIAL KNOWLEDGE BASE' and 'INVENTORY STATUS' sections below.
    - If a dosage or protocol is mentioned in the Knowledge Base, use it EXACTLY.
    - If someone asks a general question NOT in the knowledge base, politely explain you are a specialized assistant.
    - Disclaimer: 'Please remember to do your own research. This information should not be considered medical advice.'
    
    OFFICIAL KNOWLEDGE BASE:
    $combined_knowledge
    
    INVENTORY STATUS:
    $product_context";
    
    $system_prompt = get_option('nk_chatbot_system_prompt', $base_prompt);
    
    // Use the dynamic constructed prompt
    $final_system_prompt = $base_prompt; 

    // If the user modified the system prompt specifically via settings, we could also append. 
    // For now, $base_prompt contains everything vital.

    if (empty($api_key)) {
        wp_send_json_error(['message' => 'API Key is missing. Please check settings.']);
    }

    // Construct Messages for API
    $messages = [
        ['role' => 'system', 'content' => $final_system_prompt]
    ];
    
    if (!empty($history) && is_array($history)) {
        $messages = array_merge($messages, array_slice($history, -6));
    }
    
    $messages[] = ['role' => 'user', 'content' => $message];

    // Call OpenAI API
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body' => json_encode([
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => 600,
            'temperature' => 0.7
        ]),
        'timeout' => 30
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Error connecting to AI: ' . $response->get_error_message()]);
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['error'])) {
        wp_send_json_error(['message' => 'API Error: ' . $data['error']['message']]);
    }

    $ai_reply = $data['choices'][0]['message']['content'] ?? 'Sorry, I could not generate a response.';
    
    // Prepare Response
    $response_data = [
        'type' => 'text',
        'content' => $ai_reply
    ];

    // Attach products if we found them locally
    if (!empty($found_products)) {
        $response_data['type'] = 'products';
        $response_data['products'] = $found_products;
    }

    wp_send_json_success($response_data);
}

/**
 * Helper: Search Products in WooCommerce/WP
 */
function nk_search_products($query_text) {
    if (empty($query_text)) return [];

    // 1. Keyword Extraction (Balanced Stop-word removal)
    $stop_words = [
        'what', 'is', 'the', 'best', 'for', 'do', 'you', 'have', 'i', 'need', 
        'want', 'to', 'buy', 'get', 'can', 'help', 'me', 'find', 'show', 
        'tell', 'about', 'recommend', 'suggestion', 'suggestions', 'are', 'in', 'stock',
        'available', 'price', 'cost', 'how', 'much', 'does', 'work', 'please', 'give', 'details',
        'recommendation'
    ];
    // Note: Words like "weight", "loss", "fat", "muscle" are NOT stop words.

    // Clean up input
    $clean_term = strtolower(strip_tags($query_text));
    $clean_term = preg_replace('/[^\w\s-]/', '', $clean_term);
    
    // Detect "Catalog" or "Show all" Intent
    $is_catalog_request = false;
    if (stripos($clean_term, 'catalog') !== false || 
        stripos($clean_term, 'all product') !== false || 
        stripos($clean_term, 'full list') !== false ||
        stripos($clean_term, 'all peptide') !== false ||
        stripos($clean_term, 'shop') !== false) {
        $is_catalog_request = true;
    }

    $args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => $is_catalog_request ? 6 : 2, // Recommend 2 products as requested
        'orderby'        => $is_catalog_request ? 'date' : 'relevance',
        'order'          => 'DESC'
    ];

    if (!$is_catalog_request) {
        // Normal Search: Extract Keywords
        $words = explode(' ', $clean_term);
        $keywords = array_diff($words, $stop_words);
        $search_keyword = implode(' ', $keywords);
        
        if (empty(trim($search_keyword))) return [];
        
        $args['s'] = $search_keyword;
    }
    // Else: No 's' parameter needed, just fetch latest products

    $query = new WP_Query($args);
    $results = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            
            // Get Image
            $img_url = get_the_post_thumbnail_url(get_the_ID(), 'medium');
            if (!$img_url && function_exists('wc_placeholder_img_src')) {
                $img_url = wc_placeholder_img_src(); 
            }

            // Get Price
            $price = '';
            if (function_exists('wc_get_product')) {
                $_product = wc_get_product(get_the_ID());
                $price = $_product ? $_product->get_price_html() : '';
            } else {
                 $price = 'View Product';
            }

            // Get Excerpt/Description (Key for "kya he sari details")
            $raw_excerpt = get_the_excerpt();
            if (empty($raw_excerpt)) {
                $raw_excerpt = wp_trim_words(get_the_content(), 20);
            }
            
            // Short version for Card display
            $card_desc = wp_trim_words($raw_excerpt, 15, '...');

            $results[] = [
                'title' => get_the_title(),
                'price' => strip_tags($price),
                'image' => $img_url,
                'link'  => get_permalink(),
                'desc'  => $card_desc,      // For JS Card
                'full_desc' => $raw_excerpt // For AI Context
            ];
        }
        wp_reset_postdata();
    }

    return $results;
}
