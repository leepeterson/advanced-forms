<?php


/**
 * The main function used to output a form
 * 
 *
 * @since 1.0.0
 *
 */
function advanced_form( $form_id, $args = array() ) {
	
	// Render form and catch output
	ob_start();
	
	do_action( 'af/form/render', $form_id, $args );
	
	$output = ob_get_clean();
	
	
	if ( ! isset( $args['echo'] ) || $args['echo'] ) {
		echo $output;
	}
	
	
	return $output;
	
}


/**
 * Helepr function to extract a specific field value from submitted fields
 *
 * @since 1.0.0
 *
 */
function af_get_field( $field_key_or_name, $fields ) {
	
	foreach( $fields as $field ) {
		
		if ( $field['key'] == $field_key_or_name || $field['name'] == $field_key_or_name ) {
			
			return $field['value'];
			
		}
		
	}
	
	return false;
	
}


/**
 * Used to register a form programmatically
 *
 * @since 1.0.0
 *
 */
function af_register_form( $form ) {
	
	global $af_registered_forms;
	
	if ( ! $af_registered_forms || ! is_array( $af_registered_forms ) ) {
		
		$af_registered_forms = array();
		
	}
	
	$form = af_get_valid_form( $form );
	
	if ( $form ) {
		
		$af_registered_forms[] = $form;
		
	}
	
	
	return $form;
	
}


/**
 * Validates and fills a form array with default values
 *
 * @since 1.0.0
 *
 */
function af_get_valid_form( $form ) {
	
	// A form key is always required
	if ( ! isset( $form['key'] ) ) {
		return;
	}
	
	$args = array(
		'post_id' 		=> false,
		'title' 		=> '',
		'key'			=> '',
		'display' 		=> array(
			'description' 				=> '',
			'success_message' 			=> '',
		),
		'create_entries' => false,
	);
	
	$args = apply_filters( 'af/form/valid_form', $args );
	
	$form = wp_parse_args( $form, $args );
	
	return $form;
	
}


/**
 * Generates a form array from a form post object
 *
 * @since 1.0.0
 *
 */
function af_form_from_post( $form_post ) {
	
	// Get post object if ID has been passed
	if ( is_numeric( $form_post ) ) {
		$form_post = get_post( $form_post );
	}
	
	// Make sure we have a post and that it's a form
	if ( ! $form_post || 'af_form' != $form_post->post_type ) {
		return false;
	}
	
	
	$form = array(
		'post_id' 		=> $form_post->ID,
		'title' 		=> $form_post->post_title,
		'key'			=> get_post_meta( $form_post->ID, 'form_key', true ),
		'display' 		=> array(
			'description' 				=> get_field( 'form_description', $form_post->ID ),
			'success_message' 			=> get_field( 'form_success_message', $form_post->ID ),
		),
		'create_entries' => get_field( 'form_create_entries', $form_post->ID ),
	);
	
	
	$form = apply_filters( 'af/form/from_post', $form, $form_post );
	$form = apply_filters( 'af/form/from_post/id=' . $form['post_id'], $form, $form_post );
	$form = apply_filters( 'af/form/from_post/key=' . $form['key'], $form, $form_post );
	
	
	return af_get_valid_form( $form );
	
}


/**
 * Retrieves a form either 
 *
 * @since 1.0.0
 *
 */
function af_form_from_key( $key ) {
	
	global $af_registered_forms;
	
	if ( $af_registered_forms && is_array( $af_registered_forms ) ) {
		
		foreach ( $af_registered_forms as $registered_form ) {
			
			if ( $registered_form['key'] == $key ) {
				
				return af_get_valid_form( $registered_form );
				
			}
			
		}
	
	}
	
	
	// Form not a registered one, search posts by key meta
	$args = array(
		'post_type' => 'af_form',
		'posts_per_page' => '1',
		'meta_query' => array(
			array(
				'key' => 'form_key',
				'value' => $key,
			),
		),
	);
	
	$form_query = new WP_Query( $args );
	
	if ( $form_query->have_posts() ) {
		
		return af_form_from_post( $form_query->posts[0] );
		
	}
	
	
	return false;
	
}


/**
 * Retrieves a form by form key or form ID
 *
 * @since 1.0.0
 *
 */
function af_get_form( $form_id_or_key ) {
	
	$form = false;
	
	
	if ( af_is_valid_form_key( $form_id_or_key ) ) {
		
		$form = af_form_from_key( $form_id_or_key );
		
	} elseif ( is_numeric( $form_id_or_key ) ) {
		
		$form = af_form_from_post( $form_id_or_key );

	}
	
	
	return $form;
	
}

	
/**
 * Returns all forms, both those saved as posts and those registered
 *
 * @since 1.0.0
 *
 */
function af_get_forms() {

	$forms = array();
	
	// Get all forms saved as posts
	$args = array(
		'post_type' => 'af_form',
		'posts_per_page' => -1,
	);
	$form_query = new WP_Query( $args );
	
	if ( $form_query->have_posts() ) {
		
		foreach( $form_query->posts as $form_post ) {
			
			$form = af_form_from_post( $form_post );
			
			$forms[] = $form;
			
		}
		
	}
	
	// Get all programmatically registered forms
	global $af_registered_forms;
	
	if ( $af_registered_forms && is_array( $af_registered_forms ) ) {
		
		foreach( $af_registered_forms as $registered_form ) {
			
			$forms[] = af_get_valid_form( $registered_form );
			
		}
		
	}
	
	return $forms;
	
}


/**
 * Returns all fields groups used by specified form
 *
 * @since 1.0.0
 *
 */
function af_get_form_field_groups( $form_key ) {
	
	// If a full form array is passed
	if ( is_array( $form_key ) ) {
		
		$form_key = $form_key['key'];
		
	}
	
	// Location rule filter
	$args = array(
		'af_form' => $form_key,
	);
	
	$field_groups = acf_get_field_groups( $args );

	return $field_groups;
		
}


/**
 * Returns all fields assigned to a form
 *
 * @since 1.0.1
 *
 */
function af_get_form_fields( $form_key ) {
	
	$form_fields = array();
	
	$field_groups = af_get_form_field_groups( $form_key );
	
	if ( $field_groups ) {
		
		foreach ( $field_groups as $field_group ) {
			
			$fields = acf_get_fields( $field_group );
			
			foreach ( $fields as $field ) {
				
				$form_fields[] = $field;
				
			}
			
		}
		
	}
	
	return $form_fields;
	
}