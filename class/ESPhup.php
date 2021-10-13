<?php

class ESPhup {
	
	private static $instance = null;
	protected static $post_types = array('project');
	protected static $taxs = array('project_category');
	
	/**
	 * create or get object instance 
	 * @return IsusAPI
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	public function __construct() {
		add_action('init', array($this, 'init'));
	}
	
	public function init() {
		add_filter( 'post_type_link', array($this, 'remove_slug'), 10, 3 );
		add_action( 'pre_get_posts', array($this, 'parse_request') );
		add_filter( 'request', array($this, 'change_term_request'), 1, 1 );
		add_filter( 'term_link', array($this, 'term_permalink'), 10, 3 );
	}
	
	public function remove_slug( $post_link, $post, $leavename ) {
		
		if ( ! in_array($post->post_type, self::$post_types) || 'publish' != $post->post_status ) {
			return $post_link;
		}
		
		$post_link = str_replace( '/' . $post->post_type . '/', '/', $post_link );
		
		return $post_link;
	}
	
	public function parse_request( $query ) {
		
		if ( ! $query->is_main_query() || 2 != count( $query->query ) || ! isset( $query->query['page'] ) ) {
			return;
		}
		
		if ( ! empty( $query->query['name'] ) ) {
			$query->set( 'post_type', array_merge( array( 'post', 'page' ), self::$post_types) );
		}
	}
	
	/**
	 * adapted from https://rudrastyh.com/wordpress/remove-taxonomy-slug-from-urls.html using code from aatospaja 
	 * @param array $query_vars
	 */
	function change_term_request($query_vars){
		
		$tax_names = self::$taxs; // specify you taxonomy name here, it can be also 'category' or 'post_tag'
		
		if( isset($query_vars['attachment']) ? $query_vars['attachment'] : null) :
		
		$include_children = true;
		$name = $query_vars['attachment'];
		
		else:
		
		if( isset($query_vars['name']) ? $query_vars['name'] : null) {
			$include_children = false;
			$name = $query_vars['name'];
		}
		
		endif;
		
		if (isset($name)):
		
		foreach ($tax_names as $tax_name) {
			
			$term = get_term_by('slug', $name, $tax_name);
			
			if ($term && !is_wp_error($term)):
			
			if( $include_children ) {
				
				unset($query_vars['attachment']);
				$parent = $term->parent;
				
				while( $parent ) {
					
					$parent_term = get_term( $parent, $tax_name);
					$name = $parent_term->slug . '/' . $name;
					$parent = $parent_term->parent;
					
				}
				
			} else {
				
				unset($query_vars['name']);
				
			}
			
			$query_vars[$tax_name] = $name;
			
			endif;
			
		}
		
		endif;
		
		return $query_vars;
		
	}
	
	
	
	function term_permalink( $url, $term, $taxonomy ){
		
		$taxonomy_slugs = self::$taxs;
		
		foreach ($taxonomy_slugs as $taxonomy_slug) {
			
			if ( stripos($url, $taxonomy_slug) === TRUE || $taxonomy == $taxonomy_slug ) {
				
				$url = str_replace('/' . $taxonomy_slug, '', $url);
				
			}
		}
		
		return $url;
	}
}

\ESPhup::get_instance();