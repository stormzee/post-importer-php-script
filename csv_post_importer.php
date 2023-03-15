<?php

// Set maximum file size and time limit for file uploads
@ini_set( 'upload_max_size' , '64M' );
@ini_set( 'post_max_size', '64M');
@ini_set( 'max_execution_time', '300' );

/*
Plugin Name: CSV Post Importer
Description: Imports posts from a CSV file.
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
      // Loop through each row in the CSV file
      while ( ( $data = fgetcsv( $handle ) ) !== false ) {
        // Extract the data from the current row
        $post_name = $data[0];
        $post_content = $data[1];
        $post_title = $data[2];
        $post_author = $data[3];
        $post_date = $data[4];
        

        $post_date_cleaned = date('Y-m-d H:i:s', strtotime($post_date));
        // Set the post status to draft
        $post_status = 'draft';


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
  echo '<button type="submit">Import Posts</button>';
  echo '</form>';
}

