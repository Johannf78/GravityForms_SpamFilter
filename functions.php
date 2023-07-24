<?php
// Mark Gravity Forms submission as spam based on certain phrases using add_filter
// For debugging, uncomment the "For debugging" echo out lines.
// Replace the spam phrases in the array with the phrases you want to mark as spam
add_filter( 'gform_entry_is_spam', 'mark_submission_as_spam', 10, 3 );
function mark_submission_as_spam( $is_spam, $form, $entry ) {
    if ( $is_spam ) {
        return $is_spam;
    }
	//For debugging
	//echo 'Function is being called.<br>';
	
     // List of phrases that, if found in the submission, will mark it as spam
    $spam_phrases = array(
        'spam phrase 1',
        'spam phrase 2',
        'spam phrase 3',
    );
	
	//For debugging, loop through the $spam_phrases array and echo each phrase
	/*
	foreach ($spam_phrases as $phrase) {
    	echo $phrase . '<br>';
	}
	*/

	//For debugging. Output all the submitted values
//    echo '<pre>';
//    print_r($entry);
//    echo '</pre>';

	//For debugging. Output the submitted fields and values
/*
	echo '<h2>Submitted Form Fields and Values:</h2>';
    echo '<ul>';
    foreach ($form['fields'] as $field) {
        // Get the field label
        $label = rgar($field, 'label');

        // Get the field value from the entry
        $field_id = $field['id'];
        $value = rgar($entry, $field_id);

        // Output the field label and value
        echo '<li><strong>' . esc_html($label) . ':</strong> ' . esc_html($value) . '</li>';
    }
    echo '</ul>';
*/
	
	// Get all the submitted values from the entry
    $form_data = $entry['form'];
	
	
	// Convert all the submitted values to a single string for easier searching
	// This implode function is not working, so doing it with a normal loop...
    //$submission_string = implode( ' ', $form_data );
	//echo $submission_string;
	foreach ($form['fields'] as $field) {
    
        // Get the field value from the entry
        $field_id = $field['id'];
        $value = rgar($entry, $field_id);

		$submission_string = $submission_string . $value;
    }
	//For debugging
	//echo 'submission_string: ' . $submission_string . '<br>';
	
		
	 // Check if any of the spam phrases are present in the submission
    foreach ( $spam_phrases as $phrase ) {
        if ( stripos( $submission_string, $phrase ) !== false ) {
            // If a spam phrase is found, mark the submission as spam
             $is_spam = true;
            break; // No need to continue checking if we've found a spam phrase
        }	
    }
	//For debugging
	//echo 'Is spam: ' .  $is_spam . '<br>';
	
    // For testing purposes you can uncomment this line to set all submissions as spam
    //$is_spam = true;
 
    if ( $is_spam && method_exists( 'GFCommon', 'set_spam_filter' ) ) {
        GFCommon::set_spam_filter( rgar( $form, 'id' ), 'The name of your spam check', 'the reason this entry is being marked as spam' );
    }
 
    return $is_spam;
}
