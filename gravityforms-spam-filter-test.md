# Gravity Forms Spam Filter - Test Cases

Use these test cases to manually verify the spam filter is working correctly.

## Legitimate Submissions (Should NOT be marked as spam)

### Test 1: Normal Business Inquiry
- **Name:** `John Smith`
- **Company:** `Acme Corporation`
- **Email:** `john.smith@acme.com`
- **Address:** `123 Main Street, New York, USA`
- **Description:** `I need a quote for 100 units of your product. Can you provide pricing and delivery time?`

### Test 2: Short but Legitimate
- **Name:** `Maria Schmidt`
- **Company:** `Tech Solutions`
- **Email:** `maria@techsol.de`
- **Address:** `Berlin, Germany`
- **Description:** `Interested in bulk pricing`

### Test 3: Longer Detailed Inquiry
- **Name:** `Peter Mueller`
- **Company:** `Industrial Supplies GmbH`
- **Email:** `peter.mueller@industrialsupplies.de`
- **Address:** `Hauptstrasse 123, Munich, Germany`
- **Description:** `We are looking for a supplier for our manufacturing facility. We need approximately 500 units per month. Please send us a quote with your best pricing and delivery terms.`

### Test 4: With Special Characters (Legitimate)
- **Name:** `José García`
- **Company:** `García & Sons`
- **Email:** `jose@garcia-sons.com`
- **Address:** `Calle Mayor 45, Madrid`
- **Description:** `Necesito información sobre sus productos`

---

## Spam Submissions (Should be marked as spam)

### Test 5: Random Characters (Like Your Example)
- **Name:** `John Smith`
- **Company:** `Test Company`
- **Email:** `test@example.com`
- **Address:** `123 Main Street, City, Country`
- **Description:** `TSobdmYmfntlmmOT`

### Test 6: Multiple Random Character Fields
- **Name:** `mMjMUEsycpBMqHruCOvQDf`
- **Company:** `vXkiLrmiRgDinhJio`
- **Email:** `test@example.com`
- **Address:** `QEKAYEJaVmGspWNj`
- **Description:** `TSobdmYmfntlmmOT`

### Test 7: Keyword Spam
- **Name:** `John Doe`
- **Company:** `Company`
- **Email:** `spam@example.com`
- **Address:** `123 Main St`
- **Description:** `I want to buy viagra and cialis from your pharmacy`

### Test 8: Mixed Case Random String
- **Name:** `aBcDeFgHiJkLmNoPqRsTuVwXyZ`
- **Company:** `Test Company`
- **Email:** `test@test.com`
- **Address:** `Test Address`
- **Description:** `This is a legitimate inquiry`

### Test 9: High Consonant Ratio
- **Name:** `John Smith`
- **Company:** `Company`
- **Email:** `test@test.com`
- **Address:** `Address`
- **Description:** `bcdfghjklmnpqrstvwxyzbcdfghjklmnpqrstvwxyz`

### Test 10: Casino/Gambling Spam
- **Name:** `Player One`
- **Company:** `Gaming Corp`
- **Email:** `player@casino.com`
- **Address:** `Las Vegas`
- **Description:** `Check out our casino and betting opportunities. Play poker and slots now!`

### Test 11: SEO Spam
- **Name:** `SEO Expert`
- **Company:** `SEO Services`
- **Email:** `seo@spam.com`
- **Address:** `Internet`
- **Description:** `We offer cheap SEO services to boost your search engine rankings and get free daily traffic`

### Test 12: Financial Scam
- **Name:** `Lucky Winner`
- **Company:** `Bank`
- **Email:** `winner@bank.com`
- **Address:** `Nigeria`
- **Description:** `Congratulations you won! Claim your prize now. Wire transfer required.`

---

## Edge Cases (Borderline - Test Carefully)

### Test 13: Technical Terms (Should Pass)
- **Name:** `Engineer Name`
- **Company:** `Tech Corp`
- **Email:** `engineer@tech.com`
- **Address:** `Address`
- **Description:** `Need specifications for RS-232 interface, TCP/IP protocol, and JSON API integration`

### Test 14: Abbreviations (Should Pass)
- **Name:** `Dr. Smith`
- **Company:** `ABC Corp`
- **Email:** `dr@abc.com`
- **Address:** `123 St`
- **Description:** `Looking for XYZ product with QTY 100`

### Test 15: Single Suspicious Field (Should Pass if Other Fields are Normal)
- **Name:** `John Smith`
- **Company:** `Acme Corporation`
- **Email:** `john.smith@acme.com`
- **Address:** `123 Main Street, New York, USA`
- **Description:** `TSobdmYmfntlmmOT` (only this field is suspicious)

**Note:** This might be caught if the form has ≤5 fields total, which is intentional.

### Test 16: Legitimate Text with Some Mixed Case
- **Name:** `John O'Brien`
- **Company:** `McDonald's Corp`
- **Email:** `john@mcdonalds.com`
- **Address:** `Main Street`
- **Description:** `I need a quote for our McDonald's franchise location`

### Test 17: Short Legitimate Inquiry
- **Name:** `Test User`
- **Company:** `Test`
- **Email:** `test@test.com`
- **Address:** `Test`
- **Description:** `Quote please`

---

## Testing Checklist

For each test:
1. ✅ Submit the form
2. ✅ Check if entry is marked as "Spam" in Gravity Forms
3. ✅ Verify notifications are blocked (if configured)
4. ✅ Check debug logs (if `WP_DEBUG` is enabled) for detection reasons

### What to Look For:
- **Legitimate submissions** (Tests 1-4, 13-14, 16-17): Should NOT be marked as spam
- **Obvious spam** (Tests 5-12): Should be marked as spam
- **Edge case** (Test 15): May be marked as spam if form has ≤5 fields (by design)

### If You Want to See Detection Details:

Add this temporary debug code at the top of your spam filter function to see what's being detected:

```php
// Temporary debug - remove after testing
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('GF Spam Filter - Checking Entry ID: ' . $entry['id']);
    error_log('GF Spam Filter - Field Values: ' . print_r($field_values, true));
}
```

This will help you verify which patterns are triggering detection.

---

## Expected Results Summary

| Test # | Expected Result | Reason |
|--------|----------------|--------|
| 1-4 | ✅ Pass (Not Spam) | Legitimate business inquiries |
| 5 | ❌ Spam | Random characters in description |
| 6 | ❌ Spam | Multiple fields with random characters |
| 7 | ❌ Spam | Contains spam keywords (viagra, cialis) |
| 8 | ❌ Spam | Random mixed case pattern in name |
| 9 | ❌ Spam | High consonant ratio, no vowels |
| 10 | ❌ Spam | Contains gambling keywords |
| 11 | ❌ Spam | Contains SEO spam keywords |
| 12 | ❌ Spam | Contains scam keywords |
| 13-14 | ✅ Pass (Not Spam) | Technical terms/abbreviations are legitimate |
| 15 | ⚠️ May be Spam | Single suspicious field (depends on form field count) |
| 16-17 | ✅ Pass (Not Spam) | Legitimate text despite some mixed case |

