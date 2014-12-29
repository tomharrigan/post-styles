<?php
/*
 * Post Styles
 *
 * @package   McNinja_Post_Styles
 * @author    TomHarrigan
 * @license   GPL-2.0+
 * @link      http://thomasharrigan.com/mcninja-post-styles
 */


class McNinja_Post_Styles {

	public function __construct() {
		add_post_type_support( 'post', 'post-styles' );
		add_action( 'init', array( $this, 'create_style_taxonomies' ), 0 );
		add_action( 'admin_init', array( $this, 'settings_api_init' ) );
		add_action( 'after_setup_theme', array( $this, 'load_plugin_textdomain' ) );
		add_filter( 'post_class', array( $this, 'my_class_names' ) );
		add_action( 'add_meta_boxes', array( $this, 'stylesbox' ) );
		add_action( 'save_post', array( $this, 'post_style_meta_box_save_postdata' ) );
		add_filter( 'request', array( $this, '_post_style_request' ) );
		add_filter( 'term_link', array( $this, '_post_style_link' ), 10, 3 );
		add_filter( 'get_post_style', array( $this, '_post_style_get_term' ) );
		add_filter( 'get_terms', array( $this, '_post_style_get_terms' ), 10, 3 );
		add_filter( 'wp_get_object_terms', array( $this, '_post_style_wp_get_object_terms' ) );
		add_filter( 'the_content', array( $this, 'style_formatting' ) );
		add_filter( 'the_excerpt', array( $this, 'excerpt_style_formatting' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		$this->add_chat_detection_style( 'IM', '#^([^:]+):#', '#[:]#' );
		$this->add_chat_detection_style( 'Skype', '#(\[.+?\])\s([^:]+):#', '#[:]#' );
	}

	protected static $instance = null;

	static $option_name_enabled = 'mcninja_post_styles';

	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}
    
    /**
	 * Add a checkbox field to Settings > Reading
	 * for enabling Post Style formatting.
	 *
	 * @uses add_settings_field, __, register_setting
	 * @action admin_init
	 * @return null
	 */
	function settings_api_init() {

		// Add the setting field [mcninja-post-styles] and place it in Settings > Reading
		add_settings_field( self::$option_name_enabled, '<span id="infinite-transporter-options">' . __( 'Enable Post Style formatting', 'Post style' ) . '</span>', array( $this, 'mcninja_post_styles_setting_html' ), 'reading' );
		register_setting( 'reading', self::$option_name_enabled, 'esc_attr' );
	}

	/**
	 * HTML code to display a checkbox true/false option
	 * for the mcninja_post_styles setting.
	 */
	function mcninja_post_styles_setting_html() {

		echo '<label><input name="mcninja_post_styles" type="checkbox" value="1" ' . checked( 1, get_option( self::$option_name_enabled ), false ) . ' /> ' . __( 'Format and display your posts with style', 'jetpack' ) . '</br><small>' . sprintf( __( '(On non-single post pages, your posts will display content based on the chosen Post Style for that post.)', 'Post style' ) ) . '</small>' . '</label>';
	}

	// Add specific CSS class by filter
	public function my_class_names($classes) {
		global $post;
		if ( post_type_supports( $post->post_type, 'post-styles' ) ) {
			$post_style = $this->get_post_style( $post );
			if( ! is_single() ) {
				if ( $post_style && ! is_wp_error( $post_style )  ) {
					$classes[] = 'post-style-' . sanitize_html_class( $post_style );
					$classes[] = 'post-format-' . sanitize_html_class( $post_style );
				} else {
					$classes[] = 'post-style-standard post-format-standard';
				}
			}
		}
		
		// return the $classes array
		return $classes;
	}

	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'Post style',
			FALSE,
			dirname( plugin_basename( __FILE__ ) ) . '/languages/'
		);

	}

	public function create_style_taxonomies() {
		register_taxonomy( 'post_style', 'post', array(
			'public' => true,
			'hierarchical' => false,
			'labels' => array(
				'name' => _x( 'Post Style', 'Post style' ),
				'singular_name' => _x( 'Post Style', 'Post style' ),
			),
			'query_var' => true,
			'rewrite' => 'type',
			'show_ui' => false,
			'_builtin' => false,
			'show_in_nav_menus' => true,
		) );
	}

	public function stylesbox( $post_type ) {
		if( $post_type == 'post' ) {
			add_meta_box( 'stylediv', _x( 'Post Style', 'Post style' ), array( $this, 'post_style_meta_box' ), null, 'side', 'default', 0 );
		}
	}

	/**
	 * Display post style form elements.
	 *
	 * @param object $post
	 */
	public function post_style_meta_box( $post ) {
		if ( post_type_supports( $post->post_type, 'post-styles' ) ) :
		
		wp_nonce_field( 'post_style_meta_box', 'post_style_meta_box_nonce' );
		
		$post_styles = array_keys( $this->get_post_style_strings() );
		
		array_shift( $post_styles );
		
		if ( is_array( $post_styles ) ) :
			$post_style =  $this->get_post_style( $post->ID );

			if ( ! $post_style )
				$post_style = '0';
			// Add in the current one if it isn't there yet, in case the current theme doesn't support it
			if ( $post_style && ! in_array( $post_style, $post_styles ) )
				$post_style = '0';
		?>
		<div id="post-styles-select">
			<input type="radio" name="post_style" class="post-style" id="post-style-0" value="0" <?php checked( $post_style, '0' ); ?> /> <label for="post-style-0" class="post-style-icon post-style-standard"><?php echo $this->get_post_style_string( 'standard' ); ?></label>
			<?php foreach ( $post_styles as $style ) : ?>
			<br /><input type="radio" name="post_style" class="post-style" id="post-style-<?php echo esc_attr( $style ); ?>" value="<?php echo esc_attr( $style ); ?>" <?php checked( $post_style, $style ); ?> /> <label for="post-style-<?php echo esc_attr( $style ); ?>" class="post-style-icon post-style-<?php echo esc_attr( $style ); ?>"><?php echo esc_html( $this->get_post_style_string( $style ) ); ?></label>
			<?php endforeach; ?><br />
		</div>
		<?php endif; endif;
	}

	/**
	 * When the post is saved, saves our custom data.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function post_style_meta_box_save_postdata( $post_id ) {
	  /*
	   * We need to verify this came from the our screen and with proper authorization,
	   * because save_post can be triggered at other times.
	   */

	  // Check if our nonce is set.
	  if ( ! isset( $_POST['post_style_meta_box_nonce'] ) )
	    return $post_id;

	  $nonce = $_POST['post_style_meta_box_nonce'];

	  // Verify that the nonce is valid.
	  if ( ! wp_verify_nonce( $nonce, 'post_style_meta_box' ) )
	      return $post_id;

	  // If this is an autosave, our form has not been submitted, so we don't want to do anything.
	  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
	      return $post_id;

	  // Check the user's permissions.
	  if ( ! current_user_can( 'edit_post', $post_id ) )
	        return $post_id;
	  
	  /* OK, its safe for us to save the data now. */
	  if ( isset( $_POST['post_style'] ) )
			$this->set_post_style( $post_id, $_POST['post_style'] );
	  
	}

	public function get_post_style( $post = null ) {

		if ( ! $post = get_post( $post )  )
			return false;

		if ( ! post_type_supports( $post->post_type, 'post-styles' ) )
			return get_post_type();

		$_style = get_the_terms( $post->ID, 'post_style' );
		if ( empty( $_style ) )
			return get_post_type();

		$style = array_shift( $_style );
		return str_replace('post-style-', '', $style->slug );
	}

	/**
	 * Check if a post has any of the given styles, or any style.
	 *
	 * @uses has_term()
	 *
	 * @param string|array $style Optional. The style or styles to check.
	 * @param object|int $post Optional. The post to check. If not supplied, defaults to the current post if used in the loop.
	 * @return bool True if the post has any of the given styles (or any style, if no style specified), false otherwise.
	 */
	public function has_post_style( $style = array(), $post = null ) {
		$prefixed = array();

		if ( $style ) {
			foreach ( (array) $style as $single ) {
				$prefixed[] = 'post-style-' . sanitize_key( $single );
			}
		}

		return has_term( $prefixed, 'post_style', $post );
	}

	/**
	 * Assign a style to a post
	 *
	 * @param int|object $post The post for which to assign a style.
	 * @param string $style A style to assign. Use an empty string or array to remove all styles from the post.
	 * @return mixed WP_Error on error. Array of affected term IDs on success.
	 */
	public function set_post_style( $post, $style ) {
		$post = get_post( $post );

		if ( empty( $post ) )
			return new WP_Error( 'invalid_post', __( 'Invalid post' ) );

		if ( ! empty( $style ) ) {
			$style = sanitize_key( $style );
			if ( 'standard' === $style || ! in_array( $style, $this->get_post_style_slugs() ) )
				$style = '';
			else
				$style = 'post-style-' . $style;
		}

		return wp_set_post_terms( $post->ID, $style, 'post_style' );
	}

	/**
	 * Returns an array of post style slugs to their translated and pretty display versions
	 *
	 * @return array The array of translated post style names.
	 */
	public function get_post_style_strings() {
		$strings = array(
			'aside' => _x( 'Aside', 'Post style' ),
			'image' => _x( 'Image', 'Post style' ),
			'video' => _x( 'Video', 'Post style' ),
			'audio' => _x( 'Audio', 'Post style' ),
			'playlist' => _x( 'Playlist', 'Post style' ),
			'quote' => _x( 'Quote', 'Post style' ),
			'link' => _x( 'Link', 'Post style' ),
			'link-list' => _x( 'List', 'Post style' ),
			'gallery' => _x( 'Gallery', 'Post style' ),
			'embed' => _x( 'Embed', 'Post style' ),
			'no-photo' => _x( 'No Photo', 'Post style' ),
			'chat' => _x( 'Chat', 'Post style' ),
		);
		$strings = apply_filters( 'post_style_strings', $strings );
		return array( 'standard' => _x( 'Standard', 'Post style' ) ) + $strings;
	}

	/**
	 * Retrieves an array of post style slugs.
	 *
	 * @uses get_post_style_slugs()
	 *
	 * @return array The array of post style slugs.
	 */
	public function get_post_style_slugs() {
		$slugs = array_keys( $this->get_post_style_strings() );
		return array_combine( $slugs, $slugs );
	}

	/**
	 * Returns a pretty, translated version of a post style slug
	 *
	 * @uses get_post_style_strings()
	 *
	 * @param string $slug A post style slug style name.
	 */
	public function get_post_style_string( $slug ) {
		$strings = $this->get_post_style_strings();
		if ( !$slug )
			return $strings['standard'];
		else
			return ( isset( $strings[$slug] ) ) ? $strings[$slug] : '';
	}

	/**
	 * Returns a link to a post style index.
	 *
	 * @param string $style The post style slug.
	 * @return string The post style term link.
	 */
	public function get_post_style_link( $style ) {
		$term = get_term_by( 'slug', 'post-style-' . $style, 'post_style' );
		if ( ! $term || is_wp_error( $term ) )
			return false;
		return get_term_link( $term );
	}

	/**
	 * Filters the request to allow for the style prefix.
	 *
	 * @access private
	 */
	function _post_style_request( $qvs ) {
		if ( ! isset( $qvs['post_style'] ) )
			return $qvs;
		$slugs = $this->get_post_style_slugs();
		if ( isset( $slugs[ $qvs['post_style'] ] ) )
			$qvs['post_style'] = 'post-style-' . $slugs[ $qvs['post_style'] ];
		$tax = get_taxonomy( 'post_style' );
		if ( ! is_admin() )
			$qvs['post_type'] = $tax->object_type;
		return $qvs;
	}

	/**
	 * Filters the post style term link to remove the style prefix.
	 *
	 * @access private
	 */
	function _post_style_link( $link, $term, $taxonomy ) {
		global $wp_rewrite;
		if ( 'post_style' != $taxonomy )
			return $link;
		if ( $wp_rewrite->get_extra_permastruct( $taxonomy ) ) {
			return str_replace( "/{$term->slug}", '/' . str_replace( 'post-style-', '', $term->slug ), $link );
		} else {
			$link = remove_query_arg( 'post_style', $link );
			return add_query_arg( 'post_style', str_replace( 'post-style-', '', $term->slug ), $link );
		}
	}

	/**
	 * Remove the post style prefix from the name property of the term object created by get_term().
	 *
	 * @access private
	 */
	function _post_style_get_term( $term ) {
		if ( isset( $term->slug ) ) {
			$term->name = $this->get_post_style_string( str_replace( 'post-style-', '', $term->slug ) );
		}
		return $term;
	}

	/**
	 * Remove the post style prefix from the name property of the term objects created by get_terms().
	 *
	 * @access private
	 */
	function _post_style_get_terms( $terms, $taxonomies, $args ) {
		if ( in_array( 'post_style', (array) $taxonomies ) ) {
			if ( isset( $args['fields'] ) && 'names' == $args['fields'] ) {
				foreach( $terms as $order => $name ) {
					$terms[$order] = $this->get_post_style_string( str_replace( 'post-style-', '', $name ) );
				}
			} else {
				foreach ( (array) $terms as $order => $term ) {
					if ( isset( $term->taxonomy ) && 'post_style' == $term->taxonomy ) {
						$terms[$order]->name = $this->get_post_style_string( str_replace( 'post-style-', '', $term->slug ) );
					}
				}
			}
		}
		return $terms;
	}

	/**
	 * Remove the post style prefix from the name property of the term objects created by wp_get_object_terms().
	 *
	 * @access private
	 */
	function _post_style_wp_get_object_terms( $terms ) {
		foreach ( (array) $terms as $order => $term ) {
			if ( isset( $term->taxonomy ) && 'post_style' == $term->taxonomy ) {
				$terms[$order]->name = $this->get_post_style_string( str_replace( 'post-style-', '', $term->slug ) );
			}
		}
		return $terms;
	}

	function excerpt_style_formatting( $excerpt ) {
		global $post;
		$enabled = get_option( self::$option_name_enabled ) ? true : false;
		if( $this->has_post_style() && !is_single() && apply_filters( 'post_style_formatting', $enabled )) {
			$excerpt = $this->style_formatting( $post->post_content );
			return do_shortcode( $excerpt );
		}
		return $excerpt;
	}

	function style_formatting( $content ) {

		$enabled = get_option( self::$option_name_enabled ) ? true : false;

		if( ( ! is_single() ) && apply_filters( 'post_style_formatting', $enabled ) ) {

			$style = get_post_style();

			switch ( $style ) {
				case 'chat':
					$content = $this->get_the_post_style_chat( $content );
					break;
				case 'quote':
					$content = $this->get_content_quote( $content );
					break;
				case 'link-list':
					preg_match_all( '/(\<(ul|ol).*\<\/(ul|ol)\>)/is', get_the_content(), $matches );
					$content = $matches[1][0];
					break;
				case 'gallery':
					$galleries = get_post_galleries( get_the_ID() );
					if ( isset( $galleries[0] ) ) {
						$content = $galleries[0];
					}
					break;
				case 'playlist':
					$content= $this->get_style_shortcode( 'playlist', $content );
					break;
				case 'audio':
					$audio = $this->get_style_shortcode( 'audio', $content );
					if( $audio ) {
						$content = $audio;
						break;
					}
				case 'video':
					$video = $this->get_style_shortcode( 'video', $content );
					if( $video ) {
						$content = $video;
						break;
					}
				case 'embed':
					$meta = get_post_custom();
				    foreach( $meta as $key => $value ){
				        if( false !== strpos( $key, 'oembed' ) )
				            return $value[0];
				    }
					$output = preg_match_all( '/(\<iframe.*\<\/iframe\>)/is', get_the_content(), $matches );

					if( ! empty($matches[1][0] ) )
						$content = $matches[1][0];
					break;
				case 'link':
					preg_match_all( '/(\<a[^\>]*\>[^\<]*\<\/a\>)/is', get_the_content(), $matches );
					if( ! empty( $matches[1][0] ) )
						$content = $matches[1][0];
					break;
				case 'no-photo':
				case 'aside':
				default:
					break;
			}
			return apply_filters( 'post_style_content_formatting', $content, $style );
		}

		return $content;
	}

	/**
	 * Add chat detection support to the `get_content_chat()` chat parser.
	 *
	 * @since 2.0
	 *
	 * @global array $_wp_chat_parsers
	 *
	 * @param string $name Unique identifier for chat style. Example: IRC
	 * @param string $newline_regex RegEx to match the start of a new line, typically when a new "username:" appears
	 *	The parser will handle up to 3 matched expressions
	 *	$matches[0] = the string before the user's message starts
	 *	$matches[1] = the time of the message, if present
	 *	$matches[2] = the author/username
	 *	OR
	 *	$matches[0] = the string before the user's message starts
	 *	$matches[1] = the author/username
	 * @param string $delimiter_regex RegEx to determine where to split the username syntax from the chat message
	 */
	function add_chat_detection_style( $name, $newline_regex, $delimiter_regex ) {
		global $_wp_chat_parsers;

		if ( empty( $_wp_chat_parsers ) )
			$_wp_chat_parsers = array();

		$_wp_chat_parsers = array( $name => array( $newline_regex, $delimiter_regex ) ) + $_wp_chat_parsers;
	}

	/**
	 * Deliberately interpret passed content as a chat transcript that is optionally
	 * followed by commentary
	 *
	 * If the content does not contain username syntax, assume that it does not contain
	 * chat logs and return
	 *
	 * Example:
	 *
	 * One stanza of chat:
	 * Scott: Hey, let's chat!
	 * Helen: No.
	 *
	 * $stanzas = array(
	 *     array(
	 *         array(
	 *             'time' => '',
	 *             'author' => 'Scott',
	 *             'messsage' => "Hey, let's chat!"
	 *         ),
	 *         array(
	 *             'time' => '',
	 *             'author' => 'Helen',
	 *             'message' => 'No.'
	 *         )
	 *     )
	 * )
	 *
	 * @since 2.0
	 *
	 * @param string $content A string which might contain chat data, passed by reference.
	 * @return array A chat log as structured data
	 */
	function get_content_chat( &$content ) {
		global $_wp_chat_parsers;

		$trimmed = strip_tags( trim( $content ) );
		if ( empty( $trimmed ) )
			return array();

		$matched_parser = false;
		foreach ( $_wp_chat_parsers as $parser ) {
			@list( $newline_regex, $delimiter_regex ) = $parser;
			if ( preg_match( $newline_regex, $trimmed ) ) {
				$matched_parser = $parser;
				break;
			}
		}

		if ( false === $matched_parser )
			return array();

		$last_index = 0;
		$stanzas = $data = $stanza = array();
		$author = $time = '';
		$lines = explode( "\n", make_clickable( $trimmed ) );
		$found = false;
		$found_index = 0;

		foreach ( $lines as $index => $line ) {
			if ( ! $found )
				$found_index = $index;

			$line = trim( $line );

			if ( empty( $line ) && $found ) {
				if ( ! empty( $author ) ) {
					$stanza[] = array(
						'time'    => $time,
						'author'  => $author,
						'message' => join( ' ', $data )
					);
				}

				$stanzas[] = $stanza;

				$stanza = $data = array();
				$author = $time = '';
				if ( ! empty( $lines[$index + 1] ) && ! preg_match( $delimiter_regex, $lines[$index + 1] ) )
					break;
				else
					continue;
			}

			$matched = preg_match( $newline_regex, $line, $matches );
			if ( ! $matched )
				continue;

			$found = true;
			$last_index = $index;
			$author_match = empty( $matches[2] ) ? $matches[1] : $matches[2];
			// assume username syntax if no whitespace is present
			$no_ws = $matched && ! preg_match( '#[\r\n\t ]#', $author_match );
			// allow script-like stanzas
			$has_ws = $matched && preg_match( '#[\r\n\t ]#', $author_match ) && empty( $lines[$index + 1] ) && empty( $lines[$index - 1] );
			if ( $matched && ( ! empty( $matches[2] ) || ( $no_ws || $has_ws ) ) ) {
				if ( ! empty( $author ) ) {
					$stanza[] = array(
						'time'    => $time,
						'author'  => $author,
						'message' => join( ' ', $data )
					);
					$data = array();
				}

				$time = empty( $matches[2] ) ? '' : $matches[1];
				$author = $author_match;
				$data[] = trim( str_replace( $matches[0], '', $line ) );
			} elseif ( preg_match( '#\S#', $line ) ) {
				$data[] = $line;
			}
		}

		if ( ! empty( $author ) ) {
			$stanza[] = array(
				'time'    => $time,
				'author'  => $author,
				'message' => trim( join( ' ', $data ) )
			);
		}

		if ( ! empty( $stanza ) )
			$stanzas[] = $stanza;

		return $stanzas;
	}

	/**
	 * Output HTML for a given chat's structured data. Themes can use this as a
	 * template tag in place of the_content() for Chat post style templates.
	 *
	 * @since 2.0
	 *
	 * @return HTML
	 */
	function get_the_post_style_chat( $content ) {
		$output  = '<dl class="chat">';
		$stanzas = $this->get_content_chat( $content );
		if ( empty( $stanzas ) )
			return array();
		foreach ( $stanzas as $stanza ) {
			foreach ( $stanza as $row ) {
				$time = '';
				if ( ! empty( $row['time'] ) )
					$time = sprintf( '<time class="chat-timestamp">%s</time>', esc_html( $row['time'] ) );

				$output .= sprintf(
					'<dt class="chat-author chat-author-%1$s vcard">%2$s <cite class="fn">%3$s</cite>: </dt>
						<dd class="chat-text">%4$s</dd>
					',
					esc_attr( sanitize_title_with_dashes( $row['author'] ) ), // Slug.
					$time,
					esc_html( $row['author'] ),
					$row['message']
				);
			}
		}

		$output .= '</dl><!-- .chat -->';

		return $output;
	}

	/**
	 * Get the first <blockquote> from the $content string passed by reference.
	 *
	 * If $content does not have a blockquote, assume the whole string
	 * is the quote.
	 *
	 * @since 2.0
	 *
	 * @param string $content A string which might contain chat data, passed by reference.
	 * @param string $replace (optional) Content to replace the quote content with.
	 * @return string The quote content.
	 */
	function get_content_quote( $content ) {
		if ( empty( $content ) )
			return '';

		if ( ! preg_match( '/(<blockquote[^>]*>.+?<\/blockquote>)/is', $content, $matches ) ) {
			return $content;
		}

		return $matches[1];
	}

	/**
	 * Get a quote from the post content.
	 *
	 * @since 2.0
	 *
	 * @uses get_content_quote()
	 * @uses apply_filters() Calls 'quote_source_style' filter to allow changing the typographical mark added to the quote source (em-dash prefix, by default)
	 *
	 * @param object $post (optional) A reference to the post object, falls back to get_post().
	 * @return string The quote html.
	 */
	function get_the_post_style_quote( $content ) {
		$quote = $this->get_content_quote( $content, true );

		if ( ! empty( $quote ) ) {
			$quote = sprintf( '<figure class="quote">%s</figure>', wpautop( $quote ) );
		}

		return $quote;
	}

	/**
	 * Outputs the post style quote.
	 *
	 * @since 2.0
	 */
	function the_post_style_quote() {
		echo get_the_post_style_quote();
	}

	function get_style_shortcode( $shortcode, $content ) {
		$pattern = get_shortcode_regex();
		if ( preg_match_all( '/'. $pattern .'/s', $content, $matches )
			&& array_key_exists( 2, $matches )
        	&& in_array( $shortcode, $matches[2] ) ) {

			$atts = shortcode_parse_atts($matches[3][0]);
	    	return call_user_func( 'wp_' . $shortcode . '_shortcode', $atts );
	    }
		return false;
	}

	function enqueue() {
		wp_enqueue_style( 'post-styles-styles', plugins_url( 'post-styles.css', __FILE__ ), array(), '122814' );
	}
}