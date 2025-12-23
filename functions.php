<?php
/**
 * Gravity Forms Spam Filter
 * Version 2.0
 * This code detects spam submissions in Gravity Forms based on keywords and pattern analysis.
 * Features: keyword matching, gibberish detection, random character pattern detection.
 * Add this to WordPress using Code Snippets plugin.
 */

/**
 * Helper function to manage spam entry tracking using static variable
 * 
 * @param int|null $entry_id Entry ID to mark as spam, or null to check/get all
 * @param bool $check_only If true, only check if entry is spam (don't add)
 * @return bool|array True if entry is spam, false if not, or array of all spam entries if $entry_id is null
 */
function custom_gravity_forms_get_spam_entries($entry_id = null, $check_only = false) {
    static $spam_entries = array();
    
    if ($entry_id === null) {
        return $spam_entries;
    }
    
    if ($check_only) {
        return isset($spam_entries[$entry_id]);
    }
    
    $spam_entries[$entry_id] = true;
    return true;
}

/**
 * Detect gibberish/random character strings in text
 * 
 * @param string $text The text to analyze
 * @param float $threshold Minimum spam score to consider as spam (0.0 to 1.0)
 * @return array Array with 'is_spam' (bool) and 'score' (float) and 'reasons' (array)
 */
function custom_gravity_forms_detect_gibberish($text, $threshold = 0.7) {
    $text = trim($text);
    $length = strlen($text);
    
    // Skip very short strings (less than 8 characters)
    if ($length < 8) {
        return array('is_spam' => false, 'score' => 0, 'reasons' => array());
    }
    
    $score = 0;
    $reasons = array();
    $max_score = 0;
    
    // Remove spaces and special characters for analysis
    $clean_text = preg_replace('/[^a-zA-Z0-9]/', '', $text);
    $clean_length = strlen($clean_text);
    
    if ($clean_length < 6) {
        return array('is_spam' => false, 'score' => 0, 'reasons' => array());
    }
    
    // 1. Check consonant-to-vowel ratio (random strings have high consonant ratio)
    $vowels = preg_match_all('/[aeiouAEIOU]/', $clean_text);
    $consonants = $clean_length - $vowels;
    if ($clean_length > 0) {
        $consonant_ratio = $consonants / $clean_length;
        // Normal text usually has 40-60% consonants, random strings often >70%
        if ($consonant_ratio > 0.75) {
            $consonant_score = ($consonant_ratio - 0.75) * 4; // Scale 0.75-1.0 to 0-1.0
            $score += min($consonant_score, 0.3);
            $max_score += 0.3;
            if ($consonant_ratio > 0.8) {
                $reasons[] = 'high consonant ratio (' . round($consonant_ratio * 100) . '%)';
            }
        }
    }
    
    // 2. Check for excessive mixed case patterns (like mMjMUEsycpBMqHruCOvQDf)
    $mixed_case_changes = 0;
    $prev_case = null;
    for ($i = 0; $i < $clean_length; $i++) {
        $char = $clean_text[$i];
        if (ctype_alpha($char)) {
            $is_upper = ctype_upper($char);
            if ($prev_case !== null && $prev_case !== $is_upper) {
                $mixed_case_changes++;
            }
            $prev_case = $is_upper;
        }
    }
    $mixed_case_ratio = $clean_length > 0 ? $mixed_case_changes / $clean_length : 0;
    // Normal text has low case changes, random strings have many
    if ($mixed_case_ratio > 0.3 && $clean_length > 10) {
        $case_score = min(($mixed_case_ratio - 0.3) * 2, 0.25);
        $score += $case_score;
        $max_score += 0.25;
        if ($mixed_case_ratio > 0.4) {
            $reasons[] = 'excessive mixed case pattern';
        }
    }
    
    // 3. Check for lack of common words (dictionary check)
    // Split into words and check if any are common English words
    $words = preg_split('/\s+/', $text);
    $word_count = count(array_filter($words, function($w) {
        return strlen(trim($w)) > 2;
    }));
    
    // Common short English words that should appear in legitimate text
    $common_words = array('the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'her', 'was', 'one', 'our', 'out', 'day', 'get', 'has', 'him', 'his', 'how', 'its', 'may', 'new', 'now', 'old', 'see', 'two', 'way', 'who', 'boy', 'did', 'its', 'let', 'put', 'say', 'she', 'too', 'use');
    
    $found_common_words = 0;
    foreach ($words as $word) {
        $clean_word = strtolower(preg_replace('/[^a-z]/', '', $word));
        if (in_array($clean_word, $common_words) && strlen($clean_word) >= 3) {
            $found_common_words++;
        }
    }
    
    // If we have multiple words but no common words, it's suspicious
    if ($word_count >= 3 && $found_common_words == 0 && $length > 20) {
        $no_words_score = min(0.2, ($word_count - 2) * 0.05);
        $score += $no_words_score;
        $max_score += 0.2;
        $reasons[] = 'no common words found';
    }
    
    // 4. Check character repetition patterns (random strings often repeat characters)
    $char_counts = array_count_values(str_split(strtolower($clean_text)));
    $max_repeats = max($char_counts);
    $repeat_ratio = $max_repeats / $clean_length;
    // If a single character appears more than 30% of the time, it's suspicious
    if ($repeat_ratio > 0.3 && $clean_length > 8) {
        $repeat_score = min(($repeat_ratio - 0.3) * 2, 0.15);
        $score += $repeat_score;
        $max_score += 0.15;
        if ($repeat_ratio > 0.4) {
            $reasons[] = 'excessive character repetition';
        }
    }
    
    // 5. Check for random-looking patterns (consecutive consonants without vowels)
    $consecutive_consonants = preg_match_all('/[bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ]{5,}/i', $clean_text);
    if ($consecutive_consonants > 0) {
        $consecutive_score = min($consecutive_consonants * 0.1, 0.1);
        $score += $consecutive_score;
        $max_score += 0.1;
        $reasons[] = 'consecutive consonants without vowels';
    }
    
    // Normalize score to 0-1 range
    $normalized_score = $max_score > 0 ? min($score / $max_score, 1.0) : 0;
    
    return array(
        'is_spam' => $normalized_score >= $threshold,
        'score' => $normalized_score,
        'reasons' => $reasons
    );
}

/**
 * Filter Gravity Forms entries for spam keywords.
 * 
 * @param array $entry The entry object
 * @param array $form The form object
 * @return void
 */
function custom_gravity_forms_spam_filter($entry, $form) {
    // Skip processing if entry is already marked as spam
    if (!empty($entry['status']) && $entry['status'] === 'spam') {
        return;
    }
    
    // Define your spam keywords here (all lowercase, deduplicated)
    $spam_keywords = array(
        // Pharmaceuticals and health
        'viagra', 'cialis', 'pharmacy', 'diet pills', 'male enhancement',
        
        // Financial and money schemes
        'casino', 'payday loan', 'quick cash', 'bitcoin', 'forex', 
        'make money fast', 'get rich', 'investment opportunity',
        
        // Adult content
        'xxx', 'adult dating', 'hot singles', 'sexy',
        
        // Gambling
        'betting', 'gambling', 'poker', 'slots', 'sportsbook',
        
        // Fake services
        'cheap seo', 'buy followers', 'boost rankings', 'search engine', 
        'video promotion', 'search index', 'free daily traffic', 'seo', 
        'postura', 'business data',
		
        // Domains
        'gazeta.pl', 'easerelief.net',
        
        // Common spam phrases
        'free money', 'congratulations you won', 'lucky winner',
        'claim your prize', 'limited time offer',
        
        // Scams
        'verify your account', 'western union', 'wire transfer', 
        'nigerian prince', 'inheritance', 'lottery winner',
        
        // Malicious
        'your computer has virus', 'urgent action required',
		
        // Foreign language
        'datos relevantes'
    );
    
    // Separate single-word and multi-word keywords for different matching strategies
    $single_words = array();
    $multi_words = array();
    
    foreach ($spam_keywords as $keyword) {
        // Check if keyword contains spaces (multi-word phrase)
        if (strpos($keyword, ' ') !== false) {
            $multi_words[] = $keyword;
        } else {
            $single_words[] = $keyword;
        }
    }
    
    // Build regex patterns
    $patterns = array();
    
    // Single words: use word boundaries for exact matching
    if (!empty($single_words)) {
        $single_pattern = '\b(' . implode('|', array_map('preg_quote', $single_words)) . ')\b';
        $patterns[] = $single_pattern;
    }
    
    // Multi-word phrases: use flexible separators (spaces, hyphens, underscores, multiple spaces)
    if (!empty($multi_words)) {
        $multi_patterns = array();
        foreach ($multi_words as $phrase) {
            // Split phrase into words and escape each word separately
            $words = preg_split('/\s+/', $phrase);
            $escaped_words = array_map(function($word) {
                return preg_quote($word, '/');
            }, $words);
            // Join words with flexible separator pattern (spaces, hyphens, underscores)
            $flexible = implode('[\\s\\-_]+', $escaped_words);
            $multi_patterns[] = $flexible;
        }
        $multi_pattern = '\b(' . implode('|', $multi_patterns) . ')\b';
        $patterns[] = $multi_pattern;
    }
    
    // Combine all patterns into one regex
    $regex_pattern = '/(' . implode('|', $patterns) . ')/i';
    
    // Flag to track if spam is detected
    $is_spam = false;
    $spam_reasons = array();
    $suspicious_fields = 0; // Track multiple suspicious fields
    
    // Get only the field values we need to check
    $field_values = array();
    $field_types = array(); // Store field types for later use
    foreach ($form['fields'] as $field) {
        // Skip fields that aren't visible or don't contain user input
        if (!is_object($field) || 
            !property_exists($field, 'type') || 
            in_array($field->type, array('html', 'section', 'page', 'captcha', 'password'))) {
            continue;
        }
        
        $field_id = $field->id;
        if (isset($entry[$field_id]) && !empty($entry[$field_id])) {
            $field_values[$field_id] = strtolower($entry[$field_id]);
            $field_types[$field_id] = isset($field->type) ? $field->type : '';
        }
    }
    
    // If no fields to check, exit early
    if (empty($field_values)) {
        return;
    }
    
    // Check all collected field values for spam content
    foreach ($field_values as $field_id => $value) {
        // First check for keyword matches
        if (preg_match_all($regex_pattern, $value, $matches)) {
            $is_spam = true;
            $spam_reasons[] = "Field {$field_id} contains keyword(s): '" . implode("', '", $matches[0]) . "'";
            
            // Break early once spam is detected
            break;
        }
        
        // Check for gibberish/random character patterns (only for text fields with sufficient length)
        $field_type = isset($field_types[$field_id]) ? $field_types[$field_id] : '';
        
        // Only check text-based fields (not email, phone, etc.) for gibberish
        $text_field_types = array('text', 'textarea', 'name', 'hidden');
        if (in_array($field_type, $text_field_types) || empty($field_type)) {
            // Get original value (not lowercased) for better pattern detection
            $original_value = isset($entry[$field_id]) ? $entry[$field_id] : $value;
            
            // Check for gibberish patterns (lower threshold for individual fields)
            $gibberish_check = custom_gravity_forms_detect_gibberish($original_value, 0.6);
            if ($gibberish_check['is_spam']) {
                $suspicious_fields++;
                $reason_text = "Field {$field_id} appears to be random characters/gibberish";
                if (!empty($gibberish_check['reasons'])) {
                    $reason_text .= ' (' . implode(', ', $gibberish_check['reasons']) . ')';
                }
                $spam_reasons[] = $reason_text;
                
                // If multiple fields are suspicious, mark as spam immediately
                if ($suspicious_fields >= 2) {
                    $is_spam = true;
                    break;
                }
            }
        }
    }
    
    // If we found suspicious patterns but didn't hit the threshold, check if we should mark as spam anyway
    if (!$is_spam && $suspicious_fields >= 1 && count($field_values) <= 5) {
        // If form has few fields and one is suspicious, it's likely spam
        $is_spam = true;
        $spam_reasons[] = "Multiple suspicious patterns detected across {$suspicious_fields} field(s)";
    }
    
    // If spam is detected, mark the entry as spam
    if ($is_spam) {
        // Mark the entry as spam
        GFAPI::update_entry_property($entry['id'], 'status', 'spam');
        
        // Only log if WP_DEBUG is enabled to save resources
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Gravity Forms Spam Detected: Entry ID ' . $entry['id'] . ' - Reasons: ' . implode(', ', $spam_reasons));
        }
        
        // Store this entry ID in static variable rather than transient to save DB queries
        custom_gravity_forms_get_spam_entries($entry['id']);
    }
}

/**
 * Prevent notifications from being sent for spam entries
 * 
 * @param array $notification The notification object
 * @param array $form The form object
 * @param array $entry The entry object
 * @return array|bool The notification object or false to cancel
 */
function custom_gravity_forms_cancel_spam_notifications($notification, $form, $entry) {
    // Quickly check if entry is marked as spam
    if (!empty($entry['status']) && $entry['status'] === 'spam') {
        return false; // Cancel the notification
    }
    
    // Check if this entry was marked as spam by our filter using static variable
    if (custom_gravity_forms_get_spam_entries($entry['id'], true)) {
        return false; // Cancel the notification
    }
    
    // Otherwise, send the notification as normal
    return $notification;
}

// Hook into Gravity Forms after submission
add_action('gform_after_submission', 'custom_gravity_forms_spam_filter', 10, 2);

// Hook into the notification system to cancel notifications for spam entries
add_filter('gform_notification', 'custom_gravity_forms_cancel_spam_notifications', 10, 3); 
