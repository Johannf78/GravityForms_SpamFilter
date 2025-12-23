# Gravity Forms Spam Filter

A powerful WordPress plugin snippet that automatically detects and filters spam submissions in Gravity Forms using advanced pattern analysis and keyword matching.

## Features

### 🎯 Multi-Layer Spam Detection

- **Keyword Matching**: Detects common spam keywords across multiple categories (pharmaceuticals, gambling, scams, SEO spam, etc.)
- **Gibberish Detection**: Identifies random character strings and nonsensical text patterns
- **Pattern Analysis**: Analyzes text for suspicious characteristics:
  - High consonant-to-vowel ratios
  - Excessive mixed case patterns
  - Lack of common dictionary words
  - Character repetition patterns
  - Consecutive consonants without vowels

### 🛡️ Smart Filtering

- **Multi-Field Correlation**: Detects spam when multiple fields show suspicious patterns
- **Field Type Awareness**: Only analyzes text-based fields (skips email, phone, etc.)
- **Flexible Separator Matching**: Catches spam attempts using spaces, hyphens, or underscores
- **Automatic Spam Marking**: Marks entries as spam in Gravity Forms
- **Notification Blocking**: Prevents email notifications from being sent for spam entries

### ⚡ Performance Optimized

- Uses static variables instead of globals for better performance
- Efficient regex pattern matching
- Early exit on spam detection
- Minimal database queries

## Requirements

- WordPress 5.0+
- Gravity Forms plugin installed and activated
- PHP 7.0+

## Installation

### Method 1: Code Snippets Plugin (Recommended)

1. Install and activate the [Code Snippets](https://wordpress.org/plugins/code-snippets/) plugin
2. Go to **Snippets → Add New** in your WordPress admin
3. Copy the entire contents of `gravityforms-spam-filter.php`
4. Paste it into the code editor
5. Set the snippet to **Run everywhere**
6. Click **Save Changes and Activate**

### Method 2: Theme Functions.php

1. Open your theme's `functions.php` file
2. Copy the entire contents of `gravityforms-spam-filter.php`
3. Paste it at the end of the file
4. Save the file

⚠️ **Note**: Using the Code Snippets plugin is recommended as it allows you to easily enable/disable the filter without modifying theme files.

## Configuration

### Customizing Spam Keywords

Edit the `$spam_keywords` array in the `custom_gravity_forms_spam_filter()` function to add or remove keywords:

```php
$spam_keywords = array(
    // Pharmaceuticals and health
    'viagra', 'cialis', 'pharmacy', 'diet pills', 'male enhancement',
    
    // Financial and money schemes
    'casino', 'payday loan', 'quick cash', 'bitcoin', 'forex', 
    
    // Add your custom keywords here
    'your-custom-keyword',
);
```

### Adjusting Gibberish Detection Sensitivity

Modify the threshold in the gibberish detection function:

```php
// Lower threshold = more sensitive (may catch more spam but also more false positives)
$gibberish_check = custom_gravity_forms_detect_gibberish($original_value, 0.6);

// Higher threshold = less sensitive (fewer false positives but may miss some spam)
$gibberish_check = custom_gravity_forms_detect_gibberish($original_value, 0.8);
```

Default threshold is `0.65` for individual fields and `0.7` for the detection function.

### Multi-Field Detection Threshold

The filter automatically marks entries as spam if:
- 2+ fields show suspicious patterns, OR
- 1 field is suspicious AND the form has ≤5 total fields

To adjust this behavior, modify the logic in the main filter function around line 310-324.

## How It Works

### Detection Process

1. **Entry Submission**: Gravity Forms submits an entry
2. **Field Collection**: Filter collects all text-based field values
3. **Keyword Check**: Scans for known spam keywords using regex patterns
4. **Pattern Analysis**: If no keywords found, analyzes text for gibberish patterns
5. **Multi-Field Correlation**: Checks if multiple fields show suspicious patterns
6. **Spam Marking**: If spam detected, marks entry as spam and blocks notifications

### Pattern Detection Methods

The gibberish detector analyzes:

- **Consonant Ratio**: Random strings typically have >75% consonants
- **Mixed Case Patterns**: Excessive case changes (e.g., `mMjMUEsycpBMqHruCOvQDf`)
- **Dictionary Words**: Checks for presence of common English words
- **Character Repetition**: Detects excessive repeating characters
- **Consecutive Consonants**: Finds long sequences without vowels

## Testing

See `gravityforms-spam-filter-test.md` for comprehensive test cases including:

- Legitimate submissions (should pass)
- Obvious spam (should be blocked)
- Edge cases (borderline scenarios)

### Quick Test

Submit a form with random characters in a text field:
- **Name**: `John Smith`
- **Description**: `TSobdmYmfntlmmOT`

This should be marked as spam.

## Debugging

Enable WordPress debug logging to see detection details:

1. Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

2. Check `wp-content/debug.log` for entries like:
```
Gravity Forms Spam Detected: Entry ID 123 - Reasons: Field 5 appears to be random characters/gibberish (high consonant ratio, excessive mixed case pattern)
```

## Troubleshooting

### Spam Not Being Detected

1. **Check if snippet is active**: Verify the Code Snippet is activated
2. **Check hook priority**: Ensure no other plugins are interfering
3. **Review field types**: Only text-based fields are analyzed
4. **Check thresholds**: Lower gibberish threshold if needed

### False Positives

1. **Review detection reasons**: Check debug logs to see what triggered detection
2. **Adjust thresholds**: Increase gibberish detection threshold
3. **Whitelist patterns**: Add legitimate patterns to exception list
4. **Review keywords**: Remove keywords that match legitimate content

### Notifications Still Being Sent

1. **Check Gravity Forms settings**: Ensure notifications are configured correctly
2. **Verify hook**: The `gform_notification` filter should be active
3. **Check entry status**: Verify entries are actually marked as spam

## Performance

- **Minimal overhead**: Only processes text fields, skips others
- **Efficient regex**: Single compiled pattern for keyword matching
- **Early exits**: Stops processing once spam is detected
- **No database queries**: Uses static variables for tracking

## Compatibility

- ✅ Gravity Forms 2.0+
- ✅ WordPress Multisite
- ✅ All Gravity Forms field types
- ✅ Works with other spam prevention plugins
- ✅ Compatible with Gravity Forms add-ons

## Changelog

### Version 2.0
- Added gibberish/random character detection
- Added multi-field correlation analysis
- Improved pattern matching with flexible separators
- Replaced global variables with static variables
- Enhanced detection accuracy

### Version 1.1
- Initial release with keyword matching
- Basic spam detection and notification blocking

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

### Areas for Improvement

- Additional language support for dictionary word checking
- Machine learning-based pattern detection
- Admin interface for keyword management
- Spam pattern reporting and analytics

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

- **Issues**: Report bugs or request features on [GitHub Issues](https://github.com/yourusername/gravityforms-spam-filter/issues)
- **Documentation**: See `gravityforms-spam-filter-test.md` for test cases
- **WordPress**: Compatible with WordPress Code Snippets plugin

## Credits

Developed to solve real-world spam problems in Gravity Forms submissions. Uses advanced pattern analysis techniques to detect spam without relying solely on keyword matching.

---

**Note**: This is a code snippet, not a standalone plugin. It requires Gravity Forms and should be installed via Code Snippets plugin or theme functions.php.

