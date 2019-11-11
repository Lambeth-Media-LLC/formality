<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Formality
 * @subpackage Formality/admin
 */

class Formality_Gutenberg {

	private $formality;
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $formality       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $formality, $version ) {
		$this->formality = $formality;
		$this->version = $version;
	}

	public function register_blocks() {
  	wp_register_script(
  		'formality_blocks-js',
  		plugin_dir_url(__DIR__) . 'dist/gutenberg/blocks.build.js',
  		array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-plugins', 'wp-edit-post' ),
  		null
  	);
    
    register_block_type('formality/widget', array(
  		'editor_script' => 'formality_blocks-js',
  		'render_callback' => array( $this, 'formality_widget_block_handler'),
  		'attributes' => [
  			'id' => [ 'default' => 0, 'type' => 'integer' ],
  			'include_bg' => [ 'default' => false, 'type' => 'boolean'],
  			'is_sidebar' => [ 'default' => false, 'type' => 'boolean']
  		]
  	));

	}

  public function formality_widget_block_handler($atts) {
    if(isset($atts['id']) && $atts['id']) {
      $shortcode_attr = ' id="'.$atts['id'].'"';
      if(isset($atts['include_bg'])&&$atts['include_bg']) { $shortcode_attr .= ' include_bg="'.$atts['include_bg'].'"'; }
    	return do_shortcode('[formality'.$shortcode_attr.']');
  	}
  	return '';
  }

	public function block_categories($categories, $post) {
  	return array_merge(
  	  array(
  			array(
  				'slug' => 'formality',
  				'title' => __( 'Input fields', 'formality'),
  			),
  		),
  		array(
  			array(
  				'slug' => 'formality_nav',
  				'title' => __( 'Layout elements', 'formality'),
  			),
  		),
  		$categories
  	);
  }
  
  public function filter_blocks($allowed_block_types, $post) {
    $formality_blocks = array(
      'formality/text',
      'formality/textarea',
      'formality/email',
      'formality/select',
      'formality/step',
      'formality/message',
      'formality/number',
      'formality/switch',
      'formality/multiple'
      //'core/paragraph'
    );
    if ( $post->post_type !== 'formality_form' ) {
      return $allowed_block_types;
    }
    return $formality_blocks;
  }
    
  public function rest_api() {
    $fields = array(
      '_formality_type' => 'string',
      '_formality_color1' => 'string',
      '_formality_color2' => 'string',
      '_formality_fontsize' => 'integer',
      '_formality_logo' => 'string',
      '_formality_logo_id' => 'integer',
      '_formality_logo_height' => 'integer',
      '_formality_bg' => 'string',
      '_formality_bg_id' => 'integer',
      '_formality_overlay_opacity' => 'integer',
      '_formality_template' => 'string',
      '_formality_position' => 'string',
      '_formality_credits' => 'string',
      '_formality_credits_url' => 'string',
      '_formality_disable_credits' => 'boolean',
      '_formality_custom_credits' => 'string',
      '_formality_thankyou' => 'string',   
      '_formality_thankyou_message' => 'string',   
      '_formality_error' => 'string',   
      '_formality_error_message' => 'string',   
      '_formality_email' => 'string',
      '_formality_send_text' => 'string',
    );
    foreach($fields as $field => $type) {
      register_meta(
        'post', $field,
        array(
          'object_subtype' => 'formality_form',
          'show_in_rest' => true,
          'single' => true,
          'type' => $type,
          'sanitize_callback' => 'sanitize_text_field',
          'auth_callback' => function() { 
            return current_user_can('edit_posts');
          }
        )
      );
    }
    //old method
    register_rest_route( 'formality/v1', '/options', array(
      'methods'  => 'POST',
      'callback' => [$this, 'form_meta_update'],
      'args'	 => array(
				'id' => array( 'sanitize_callback' => 'absint', ),
			),
    ));
  }
  
  public function form_meta_update( $data ) {
    //$key = $data['key'];
    $keys = array_unique($data['keys']);
    $return = false;
  	foreach($keys as $key) {
  	  $return = update_post_meta( $data['id'], $key, $data[$key] );
    }
    return $return;
  }
  
  public function prevent_classic_editor($can_edit, $post) {
  	if ('formality_form' === $post) return true;
  	return $can_edit;
  }
  
}
