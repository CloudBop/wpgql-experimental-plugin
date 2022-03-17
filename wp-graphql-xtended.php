<?php 
/**
 * Plugin Name: WP GraphQL Xtended
 * Plugin URI: https://www.wp-graphql-xtended.com
 * Version: 0.0.1-alpha
 * Author:
 * Author URI:
 * Description: This is an example plugin for extending WP-GraphQL
 */

// 
add_action('graphql_register_types', 'wpgql_extended_register_types');
// 
function wpgql_extended_register_types(){
  // Register a new type in the Schema
  register_graphql_field('RootQuery', 'example', [
    //doc
    'description' => __( 'This is just an example field', 'your-textdomain' ),
    'type' => 'String',
    // resolver 
    'resolve' => function(
      // root, prev object that was resolved
      $root, 
      // input graphql args
      $args, 
      // state of graphql resolver, current user executing request
      $context, 
      // object that provides state of tree/graph
      $info
    ) {
      return "Hello.";
    }
  ]);

}