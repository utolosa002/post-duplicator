<?php

/**
 * Thehe jQuery ajax call to create a new post.
 * Duplicates all the data including custom meta.
 *
 * @since 2.18
 */
function m4c_duplicate_post() {
	
	// Get access to the database
	global $wpdb;

	// Include WPML API
	include_once( WP_PLUGIN_DIR . '/sitepress-multilingual-cms/inc/wpml-api.php' );

	// Check the nonce
	check_ajax_referer( 'm4c_ajax_file_nonce', 'security' );
	
	// Get variables
	$original_id  = $_POST['original_id'];

/*MODHACK*/ $_type  = get_post_type($original_id);
/*MODHACK*/ if (ICL_LANGUAGE_CODE=='eu' ){
/*MODHACK*/ $trans_id /*_zenb*/ = icl_object_id($original_id,$_type,false,'es');
/*MODHACK*/ }else{
/*MODHACK*/ $trans_id = icl_object_id($original_id,$_type,false,'eu');
/*MODHACK*/ }
/*MODHACK*/ //$trans_id  = $_POST[$trans_id_zenb];
	
	// Get the post as an array
	$duplicate = get_post( $original_id, 'ARRAY_A' );
/*MODHACK*/ $duplicate_tr = get_post( $trans_id, 'ARRAY_A' );
	
	$settings = get_mtphr_post_duplicator_settings();
/*MODHACK*/$settings1 = get_mtphr_post_duplicator_settings();
	
	// Modify some of the elements
	$duplicate['post_title'] = $duplicate['post_title'].' '.$settings['title'];
	$duplicate['post_name'] = sanitize_title($duplicate['post_name'].'-'.$settings['slug']);
/*MODHACK*/$duplicate_tr['post_title'] = $duplicate_tr['post_title'].' '.$settings1['title'];
/*MODHACK*/$duplicate_tr['post_name'] = sanitize_title($duplicate_tr['post_name'].'-'.$settings1['slug']);

	// Set the status
	if( $settings['status'] != 'same' ) {
		$duplicate['post_status'] = $settings['status'];
/*MODHACK*/	$duplicate_tr['post_status'] = $settings1['status'];
	}
	
	// Set the type
	if( $settings['type'] != 'same' ) {
		$duplicate['post_type'] = $settings['type'];
/*MODHACK*/	$duplicate_tr['post_type'] = $settings1['type'];
	}
	
	// Set the post date
	$timestamp = ( $settings['timestamp'] == 'duplicate' ) ? strtotime($duplicate['post_date']) : current_time('timestamp',0);
	$timestamp_gmt = ( $settings['timestamp'] == 'duplicate' ) ? strtotime($duplicate['post_date_gmt']) : current_time('timestamp',1);
	
	if( $settings['time_offset'] ) {
		$offset = intval($settings['time_offset_seconds']+$settings['time_offset_minutes']*60+$settings['time_offset_hours']*3600+$settings['time_offset_days']*86400);
		if( $settings['time_offset_direction'] == 'newer' ) {
			$timestamp = intval($timestamp+$offset);
			$timestamp_gmt = intval($timestamp_gmt+$offset);
		} else {
			$timestamp = intval($timestamp-$offset);
			$timestamp_gmt = intval($timestamp_gmt-$offset);
		}
	}
	$duplicate['post_date'] = date('Y-m-d H:i:s', $timestamp);
	$duplicate['post_date_gmt'] = date('Y-m-d H:i:s', $timestamp_gmt);
	$duplicate['post_modified'] = date('Y-m-d H:i:s', current_time('timestamp',0));
	$duplicate['post_modified_gmt'] = date('Y-m-d H:i:s', current_time('timestamp',1));

/*MODHACK*/$duplicate_tr['post_date'] = date('Y-m-d H:i:s', $timestamp);
/*MODHACK*/$duplicate_tr['post_date_gmt'] = date('Y-m-d H:i:s', $timestamp_gmt);
/*MODHACK*/$duplicate_tr['post_modified'] = date('Y-m-d H:i:s', current_time('timestamp',0));
/*MODHACK*/$duplicate_tr['post_modified_gmt'] = date('Y-m-d H:i:s', current_time('timestamp',1));

	// Remove som$trans_ide of the keys
	unset( $duplicate['ID'] );
	unset( $duplicate['guid'] );
	unset( $duplicate['comment_count'] );

/*MODHACK*/unset( $duplicate_tr['ID'] );
/*MODHACK*/unset( $duplicate_tr['guid'] );
/*MODHACK*/unset( $duplicate_tr['comment_count'] );

	// Insert the post into the database
	$duplicate_id = wp_insert_post( $duplicate );
/*MODHACK*/$duplicate_tr_id = wp_insert_post( $duplicate_tr );

    // Get trid of original post
/*MODHACK*/ $trid = wpml_get_content_trid( 'post_artxiboa',$duplicate_id);
/*MODHACK*/ if (ICL_LANGUAGE_CODE=='eu' ){
/*MODHACK*/ $wpdb->update($wpdb->prefix.'icl_translations', array('language_code'=>'es'), array('element_id'=> $duplicate_tr_id));
/*MODHACK*/ $wpdb->update($wpdb->prefix.'icl_translations', array( 'trid' => $trid, 'element_type' => 'post_artxiboa', 'language_code' => 'es', 'source_language_code' => ICL_LANGUAGE_CODE ), array( 'element_id' => $duplicate_tr_id ) );
/*MODHACK*/ }else{
/*MODHACK*/ $wpdb->update($wpdb->prefix.'icl_translations', array('language_code'=>'eu'), array('element_id'=> $duplicate_tr_id));
/*MODHACK*/ $wpdb->update($wpdb->prefix.'icl_translations', array( 'trid' => $trid, 'element_type' => 'post_artxiboa', 'language_code' => 'eu', 'source_language_code' => ICL_LANGUAGE_CODE ), array( 'element_id' => $duplicate_tr_id ));
/*MODHACK*/ }

	// Duplicate all the taxonomies/terms
	$taxonomies = get_object_taxonomies( $duplicate['post_type'] );
	foreach( $taxonomies as $taxonomy ) {
		$terms = wp_get_post_terms( $original_id, $taxonomy, array('fields' => 'names') );
		wp_set_object_terms( $duplicate_id, $terms, $taxonomy );
	}	

/*MODHACK*/$taxonomies1 = get_object_taxonomies( $duplicate_tr['post_type'] );
/*MODHACK*/foreach( $taxonomies1 as $taxonomy1 ) {
/*MODHACK*/	$terms1 = wp_get_post_terms( $trans_id, $taxonomy1, array('fields' => 'names') );
/*MODHACK*/	wp_set_object_terms( $duplicate_tr_id, $terms1, $taxonomy1 );
/*MODHACK*/}
  
  // Duplicate all the custom fields
	$custom_fields = get_post_custom( $original_id );
	foreach ( $custom_fields as $key => $value ) {
		if( is_array($value) && count($value) > 0 ) {
			foreach( $value as $i=>$v ) {
				$result = $wpdb->insert( $wpdb->prefix.'postmeta', array(
					'post_id' => $duplicate_id,
					'meta_key' => $key,
					'meta_value' => $v
				));
			}
		}
	}

/*MODHACK*/$custom_fields1 = get_post_custom( $trans_id );
/*MODHACK*/foreach ( $custom_fields1 as $key1 => $value1 ) {
/*MODHACK*/	if( is_array($value1) && count($value1) > 0 ) {
/*MODHACK*/		foreach( $value1 as $i1=>$v1 ) {
/*MODHACK*/			$result1 = $wpdb->insert( $wpdb->prefix.'postmeta', array(
/*MODHACK*/				'post_id' => $duplicate_tr_id,
/*MODHACK*/				'meta_key' => $key1,
/*MODHACK*/				'meta_value' => $v1
/*MODHACK*/			));
/*MODHACK*/		}
/*MODHACK*/	}
/*MODHACK*/}

	echo $duplicate_id;
/*MODHACK*/echo $duplicate_id_tr;

	die(); // this is required to return a proper result
}
add_action( 'wp_ajax_m4c_duplicate_post', 'm4c_duplicate_post' );
