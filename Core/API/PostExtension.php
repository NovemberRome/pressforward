<?php
namespace PressForward\Core\API;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;

use PressForward\Controllers\Metas;
use PressForward\Core\API\APIWithMetaEndpoints;

use WP_Ajax_Response;

class PostExtension extends APIWithMetaEndpoints implements HasActions, HasFilters {

	protected $basename;

	function __construct( Metas $metas ){
		$this->metas = $metas;
		$this->post_type = 'post';
		$this->level = 'post';
	}


	public function action_hooks() {
		$actions = array(
			array(
				'hook' => 'rest_api_init',
				'method' => 'register_rest_post_read_meta_fields',
			)
		);
		return $actions;
	}

	public function filter_hooks() {
		$filter = array(
			array(
				'hook' => 'rest_prepare_'.$this->post_type,
				'method' => 'add_rest_post_links',
				'priority'  => 10,
				'args' => 3
			)
		);
		return $filter;
	}

	public function rest_api_init_extension_hook( $action ){
		return array(
				'hook' => 'rest_api_init',
				'method' => $action,
			);
	}

	public function rest_api_init_extension_hook_read_only( $action ){
		return array(
				'hook' => 'rest_api_init',
				'method' => function(){
						$this->register_rest_post_read_field($action, true);
					},
			);
	}

	public function register_rest_post_read_field($key, $action = false){
		//http://v2.wp-api.org/extending/modifying/
		if (!$action) { $action = array( $this, $key.'_response' ); }
		if ( true === $action ){ $action = array( $this, 'meta_response' ); }
		register_rest_field( 'post',
	        $key,
	        array(
	            'get_callback'    => $action,
	            'update_callback' => null,
	            'schema'          => null,
	        )
	    );
	}

	public function add_rest_post_links( $data, $post, $request ){
		//http://v2.wp-api.org/extending/linking/
		//https://1fix.io/blog/2015/06/26/adding-fields-wp-rest-api/
		$data->add_links( array(
			'test_author' => array(
					'href' => rest_url( '/wp/v2/users/42' ),
				)
			)
		);
		return $data;
	}

	public function register_rest_post_read_meta_fields(){
		foreach ( $this->valid_metas() as $key ){
			$this->register_rest_post_read_field( $key, true );
		}
	}

	public function meta_response($object, $field_name, $request ){
		$response = $this->metas->get_post_pf_meta( $object[ 'id' ], $field_name, true );
		if ( empty($response) || is_wp_error( $response ) ){
			return 'false';
		} else {
			return $response;
		}
	}

	public function item_id(){
		$this->register_rest_post_read_field('item_id', true);
	}
}
