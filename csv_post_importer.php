<?php

// Set maximum file size and time limit for file uploads
@ini_set( 'upload_max_size' , '64M' );
@ini_set( 'post_max_size', '64M');
@ini_set( 'max_execution_time', '300' );

/*
Plugin Name: CSV Post Importer
Description: Create posts from a CSV file
Version: 1.0
Author: Oppong Samuel Addo
*/

// Register the plugin activation hook
register_activation_hook('csv_post_importer.php', 'csv_post_importer_activate' );

function csv_post_importer_activate() {
  // Create a new page in the WordPress admin for importing posts from CSV
  add_menu_page( 'CSV Post Importer', 'CSV Post Importer', 'manage_options', 'csv_post_importer', 'csv_post_importer_page' );
}

add_action('admin_menu', 'csv_post_importer_activate');

function csv_post_importer_page() {

  if ( ! current_user_can( 'manage_options' ) ){
    wp_die( __( 'You do not have the permission to access this page.' ) );
  }
  // Check if the CSV file has been uploaded
  if ( isset( $_FILES['csv_file'] ) && ! empty( $_FILES['csv_file']['tmp_name'] ) ) {
    // Open the CSV file
    if ( ( $handle = fopen( $_FILES['csv_file']['tmp_name'], 'r' ) ) !== false ) {
		
		$count = 0;
		$errors = 0;
      // Loop through each row in the CSV file
      while ( ( $data = fgetcsv( $handle ) ) !== false ) {
        // Extract the data from the current row
        $post_title = $data[0];
        $post_author = $data[1];
        $post_content = $data[2];
        $post_date = $data[3];
        $post_image_url = $data[4];
        $post_name = $data[5];
        
        

        $post_date_cleaned = date('Y-m-d H:i:s', strtotime($post_date));

        // Get the selected post status from the form (default to 'draft')
        $post_status = isset( $_POST['post_status'] ) ? sanitize_text_field( $_POST['post_status'] ) : 'draft';
      
        // Create a new post array
        $new_post = array(
          'post_title' => $post_title,
          'post_name' => $post_name,
          'post_content' => $post_content,
          'post_author' => $post_author,
          'post_date' => $post_date_cleaned,
          'post_status' => $post_status
        );
        
        // Insert the new post into WordPress
        $post_id = wp_insert_post( $new_post );
        if ( ! empty( $post_image_url ) ) {
          $image_data = @file_get_contents( $post_image_url );
          if ( $image_data !== false ) {
            $filename = basename( $post_image_url );
            $upload_file = wp_upload_bits( $filename, null, $image_data );
            if ( $upload_file['error'] == false ) {
              $attachment = array(
                'post_mime_type' => $upload_file['type'],
                'post_title' => preg_replace( '/\.[^.]+$/', '', $filename ),
                'post_content' => '',
                'post_status' => 'inherit',
                'guid' => $upload_file['url']
              );
              $attachment_id = wp_insert_attachment( $attachment, $upload_file['file'], $post_id );
              if ( ! is_wp_error( $attachment_id ) ) {
                require_once( ABSPATH . 'wp-admin/includes/image.php' );
				require_once( ABSPATH . 'wp-admin/includes/media.php');
				require_once( ABSPATH . 'wp-admin/includes/file.php');
                $attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
                wp_update_attachment_metadata( $attachment_id, $attachment_data );
                set_post_thumbnail( $post_id, $attachment_id );
              }
            }else {
				// Display a warning message if the image cannot be uploaded
				echo '<div class="notice notice-warning is-dismissible"><p>Could not upload image: ' . $post_image_url . 				'</p></div>';
			}
          }else {
				// Display a warning message if the image cannot be fetched
				echo '<div class="notice notice-warning is-dismissible"><p>No image found at: ' . $post_image_url . '</p>					</div>';
			}
        } else {
			// Display a warning message if no image URL is provided
			echo '<div class="notice notice-warning is-dismissible"><p>No image URL provided for post: ' . $post_title . '</p></div>';
		}
      }
      
      // Close the CSV file
      fclose( $handle );
      
      // Display a success message
      echo '<div class="notice notice-success is-dismissible"><p>Posts imported successfully.</p></div>';
    } else {
      // Display an error message if the CSV file cannot be opened
      echo '<div class="notice notice-error is-dismissible"><p>Could not open CSV file.</p></div>';
    }
  }
  
  // Display the import form
  echo '<h1>Import Posts from CSV</h1>';
  echo '<form method="post" enctype="multipart/form-data">';
  echo '<input type="file" name="csv_file" required>';
  echo '<label for="post_status">Post Status:</label>';
  echo '<select id="post_status" name="post_status">';
  echo '<option value="draft">Draft</option>';
  echo '<option value="publish">Publish</option>';
  echo '</select>';
  echo '<button type="submit">Import Posts</button>';
  echo '</form>';
}
