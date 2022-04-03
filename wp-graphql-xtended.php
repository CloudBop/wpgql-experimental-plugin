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
  // Register a new type in the Schema at root of graph
  register_graphql_field('RootQuery', 'example', [
    //doc
    'description' => __( 'This is just an example field', 'your-textdomain' ),
    'type' => 'String',
    'args' => [
      'test'=> [
        //doc
        'description' => __( 'This is just an example field', 'your-textdomain' ),
        'type' => "String"
      ],
    ],
    // resolver 
    'resolve' => function(
      // prev object that was resolved, (@ root of graph)
      $root, 
      // input graphql args
      $args, 
      // state of graphql resolver, current user executing request
      $context, 
      // object that provides state of tree/graph
      $info
    ) {

      // will resolve the function and return json in wpgraphiql
      // wp_send_json( [ 'args' => $args ]);
      
      // use wpgrapql debug - requires debug to be enabled. -> sends message with resolver
      // graphql_debug( [ 'args' => $args ]);
      //... will still resolve
      return "Hello. ". $args['test'];
    }
  ]);

  // Register a new type in the Schema at Posts graph
  register_graphql_field('Post', 'color', [
    //doc
    'description' => __( 'The color field meta', 'your-textdomain' ),
    'type' => 'String',
    // 'args' => [
    //   'test'=> [
    //     //doc
    //     'description' => __( 'This is just an example field', 'your-textdomain' ),
    //     'type' => "String"
    //   ],
    // ],
    // resolver 
    'resolve' => function(
      // prev object that was resolved, (@ root of graph)
      $post, 
      // input graphql args
      $args, 
      // state of graphql resolver, current user executing request
      $context, 
      // object that provides state of tree/graph
      $info
    ) {
      // will resolve the function early and return json;
      // wp_send_json( [ 'args' => $args ]);
      
      // use wpgrapql debug - requires debug to be enabled. -> sends message with resolver
      // graphql_debug( $post );
      //... will still resolve

      $color = get_post_meta($post->databaseId, 'color', true);
      // nullable
      return $color ?? null;
      //what's this? wp_kses_no_null($string, $options);
    }
  ]);
}

// store/set color value as post meta data
// add_action( 'init', function(){
// double check this creates the meta on every post.
// update_post_meta($post_id="3551", $meta_key="color", $meta_value="blue", $prev_value);
// });
/** broadcast 'users'(*) to non-authenticated requests
 * (* users that have no published/public posts)
 * 
 * {
 *  users(where:{login:"Unpublished Author"}) {
 *    nodes {
 *      id
 *      name
 *    }
 *  }
 * }
 * 
 * WPGraphQL sets an argument of 'has_published_posts' => true for the underlying WP_User_Query, meaning that the SQL query for a list of users will reduce the results to users that have published posts.
 * To adjust this, we can use the `graphql_connection_query_args` like so:
 */
add_filter( 'graphql_connection_query_args', function( $query_args, $connection_resolver ) {

  
  if ( $connection_resolver instanceof \WPGraphQL\Data\Connection\UserConnectionResolver ) {
    unset( $query_args['has_published_posts'] );
  }
  if ( $connection_resolver instanceof \WPGraphQL\Data\Connection\PostObjectConnectionResolver ) {
    
    if(is_user_logged_in() && $query_args["graphql_args"]["where"]["status"]==="private"){
      //ensure the wp query is looking 4 private posts
      $query_args['post_status'] = ["private"];
      graphql_debug(['iran'=>'graphql_connection_query_args', 'query_args'=>$query_args]);
    };
  }
  // graphql_debug( [ 
  //     'filter' => "graphql_connection_query_args",
  //     'connection_resolver' => $connection_resolver,
  //     'query_args' => $query_args,
  //     // 'data' => $data,
  //     // 'owner' => $owner,
  //     // 'current_user' => $current_user
  //   ]);

  // wp_send_json( [ 'connection_resolver' => $connection_resolver, 'query_args' => $query_args ]);
  // if ( $connection_resolver instanceof \WPGraphQL\Data\Connection\PostConnectionResolver ) {
  //   // wp_send_json( [ 'query_args' => $query_args ]);
  //   // unset( $query_args['has_published_posts'] );
  // }
  return $query_args;
}, 10, 2 );
/*
Filter the User Model to make all Users public
WPGraphQL has a Model Layer that centralizes the logic to determine if any given object, or fields of the object, should be allowed to be seen by the user requesting data.
The User Model prevents unpublished users from being seen by non-authenticated WPGraphQL requests.
To lift this restriction, we can use the following filter:
*/
add_filter( 'graphql_object_visibility', function( $visibility, $model_name, $data, $owner, $current_user ) {
  // only apply our adjustments to the UserObject Model  
  if ( 'UserObject' === $model_name ) {
    // $visibility = 'public';
    // graphql_debug( [ 
    //   'model_name' => "UserObject",
    //   'visibility' => $visibility,
    //   // 'model_name' => $model_name,
    //   // 'data' => $data,
    //   // 'owner' => $owner,
    //   // 'current_user' => $current_user
    // ]);
  } else if ( 'PostObject' === $model_name ) {
    // update the PostObject model to allow following condition - IE - ! - $data->post_type==="page"
    if(is_user_logged_in() && $data->post_status === "private" && $data->post_type==="post"){
      $visibility = 'public';
    }
    // graphql_debug( [ 
    //   'model_name' => "PostObject",
    //   'visibility' => $visibility,
    //   'model_name' => $model_name,
    //   'data' => $data,
    //   'owner' => $owner,
    //   'current_user' => $current_user
    // ]);
  }else{
    // graphql_debug( [ 
    //     'filter' => "graphql_object_visibility",
    //     'visibility' => $visibility,
    //     'model_name' => $model_name,
    //     'data' => $data,
    //     'owner' => $owner,
    //     'current_user' => $current_user
    //   ]);
  }
  // - 

  return $visibility;
}, 10, 5 );

//double check what is happening here, does it mean all logged in users will end calling this function on every bit of authenticated data inc mutations
add_filter( 'graphql_connection_should_execute', function( $should_execute) {
  $userLoggedIn = is_user_logged_in();
  $current_user = wp_get_current_user();
  graphql_debug( [ 
    'filter' => "graphql_connection_should_execute",
    'diagnostics' => $userLoggedIn || $should_execute,
    'userLoggedIn'=> $userLoggedIn,
    'current_user'=> $current_user
    // should be passed as argument '$resolver'=> $connection_resolver
  ]);

  if($userLoggedIn) {
    return true; //$userLoggedIn || $should_execute;
  } else {
    return $should_execute; //$userLoggedIn || $should_execute;
  }
} );


// add_filter( 'graphql_connection_query_args', function($stuff// , AbstractConnectionResolver $resolver
// ){
//   $userLoggedIn = is_user_logged_in();
//   graphql_debug( [ 
//     'filter' => "graphql_connection_query_args",
//     'stuff' => $stuff,
//     'userLoggedIn'=>$userLoggedIn
//     // 'source' => $source,
//     // 'args' => $args,
//     // 'context' => $context,
//     // 'info' => $info
//   ]);
//   return $userLoggedIn || $should_execute;
// } );

// - doesnt do anything
// add_filter('graphql_restricted_data_cap', function( $restricted_cap
// // , $model_name, $data, $visibility, $owner, $current_user 
// ){
//   graphql_debug( [ 
//     'filter' => "graphql_restricted_data_cap",
//     'restriction' => $restricted_cap
//   ]);
//   return $restricted_cap;
// });

// runs after should_execute
// add_filter('graphql_data_is_private', function($is_private, $modelName, $data){
//   // (is_user_logged_in() && $query_args["graphql_args"]["where"]["status"]==="private")
//   // if($modelName==="modelName") {

//     //     return false;
//     //   }
//     // }
    
//   // if($data["graphql_args"]["where"]["status"]==="private") {
//     graphql_debug( [ 
//       'filter_that_ran' => "graphql_data_is_private",
//       'is_private' => $is_private,
//       'modelName' => $modelName,
//       'data' => $data->ID
//     ]);
//   // }
//   return $is_private;
// }, 10, 5);

// //
// add_filter( 'graphql_connection_should_execute', function ($should_execute, $resolver){


//   $shud2 = true;

//   graphql_debug( [
//     'should execute'=>$shud2,
//     'resolver'=>$resolver
//     ] );


//   return $shud2;
// } );

//
// add_filter( 'graphql_data_is_private', function (
//   $is_private // , $data,  $visibility, $owner, $current_user 
//   ){

//   $isUser = is_user_logged_in();
//   graphql_debug( [ 'is_private' => $is_private ]);

//   return $is_private;
// });

// add_filter( 'graphql_post_object_connection_query_args',function(  $query_args
// , $source
// , $args
// , $context
// , $info
// ) {
//   wp_send_json([$query_args, $source
// , $args
// , $context
// , $info]);
//   return $query_args;
// });


// add_filter( 'graphql_post_object_connection_query_args',  function($query_args
// // $source, $args, $context, $info
// ){
//   graphql_debug( [ 
//     'filter' => "graphql_post_object_connection_query_args",
//     'query_args' => $query_args,
//     // 'source' => $source,
//     // 'args' => $args,
//     // 'context' => $context,
//     // 'info' => $info
//   ]);
//   return $query_args;
// });