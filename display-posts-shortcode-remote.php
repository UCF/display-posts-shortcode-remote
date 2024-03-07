<?php
/**
 * @package   Display Posts Shortcode - Remote
 * @category  Extension
 * @author    Steven A. Zahm
 * @license   GPL-2.0+
 * @link      https://connections-pro.com
 * @copyright 2018 Steven A. Zahm
 *
 * @wordpress-plugin
 * Plugin Name:       Display Posts Shortcode - Remote
 * Plugin URI:        https://connections-pro.com/
 * Description:       An extension for the Display Posts Shortcode plugin which adds a shortcode for displaying posts from a remote WordPress site.
 * Version:           1.1.1
 * Author:            Steven A. Zahm
 * Author URI:        https://connections-pro.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       display-posts-shortcode-remote
 * Domain Path:       /languages
 */

/**
 * Forked by Ramin Farhadi
 * since 0.1.0
 */

if ( ! class_exists( 'Display_Posts_Remote' ) ) {

	final class Display_Posts_Remote {

		const VERSION = '1.1.1';

		/**
		 * @var Display_Posts_Remote Stores the instance of this class.
		 *
		 * @since 1.0
		 */
		private static $instance;

		/**
		 * @var string The absolute path this this file.
		 *
		 * @since 1.0
		 */
		private $file = '';

		/**
		 * @var string The URL to the plugin's folder.
		 *
		 * @since 1.0
		 */
		private $url = '';

		/**
		 * @var string The absolute path to this plugin's folder.
		 *
		 * @since 1.0
		 */
		private $path = '';

		/**
		 * @var string The basename of the plugin.
		 *
		 * @since 1.0
		 */
		private $basename = '';

		/**
		 * A dummy constructor to prevent the class from being loaded more than once.
		 *
		 * @since 1.0
		 */
		public function __construct() { /* Do nothing here */ }

		/**
		 * The main Connection Form plugin instance.
		 *
		 * @since 1.0
		 *
		 * @return self
		 */
		public static function instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof self ) ) {

				self::$instance = $self = new self;

				$self->file     = __FILE__;
				$self->url      = plugin_dir_url( $self->file );
				$self->path     = plugin_dir_path( $self->file );
				$self->basename = plugin_basename( $self->file );

				$self->includes();
				$self->hooks();
				//$self->registerJavaScripts();
				//$self->registerCSS();
			}

			return self::$instance;
		}

		/**
		 * Include the dependencies.
		 *
		 * @since 1.0
		 */
		private function includes() {

			require_once( 'includes/class.wp-rest-post.php');
			require_once( 'includes/class.wp-rest-add-featured-image.php');
		}

		/**
		 * Register the plugins actions/filters.
		 *
		 * @since 1.0
		 */
		private function hooks() {

			add_shortcode( 'display-posts-remote', array( __CLASS__, 'shortcode' ) );
		}

		/**
		 * Get the plugin's bas URL.
		 *
		 * @since 1.0
		 */
		public function getURL() {

			return $this->url;
		}

		/**
		 * Register the plugin's JavaScript.
		 *
		 * @since 1.0
		 */
		private function registerJavaScripts() {
		}

		/**
		 * Enqueue the plugin's JavaScript.
		 *
		 * @since 1.0
		 */
		public static function enqueueJS() {
		}

		/**
		 * Register the plugin's CSS.
		 *
		 * @since 1.0
		 */
		private function registerCSS() {
		}

		/**
		 * Enqueue the plugin's JavaScript.
		 *
		 * @since 1.0
		 */
		public static function enqueueCSS() {
		}

		/**
		 * Cache the REST response.
		 *
		 * @since 1.0
		 *
		 * @param string    $url
		 * @param array     $response
		 * @param float|int $timeout
		 */
		protected function setCache( $url, $response, $timeout = DAY_IN_SECONDS ) {

			set_transient( $this->cacheKey( $url ), $response, $timeout );
		}

		/**
		 * Get cached REST response.
		 *
		 * @since 1.0
		 *
		 * @param string $url
		 *
		 * @return array|false
		 */
		protected function getCache( $url ) {

			if ( is_array( $response = get_transient( $this->cacheKey( $url ) ) ) ) {

				return $response;
			}

			return FALSE;
		}

		/**
		 * Clear cache.
		 *
		 * @since 1.0
		 *
		 * @param string $url
		 */
		public function clearCache( $url ){

			delete_transient( $this->cacheKey( $url ) );
		}

		/**
		 * Create cache key based on URL.
		 *
		 * @since 1.0
		 *
		 * @param string $url
		 *
		 * @return string
		 */
		protected function cacheKey( $url ) {

			return md5( preg_replace( '(^https?://)', '', $url ) );
		}

		/**
		 * Query a remote site's posts.
		 *
		 * @since 1.0
		 *
		 * @param array $untrusted
		 *
		 * @return array|WP_Error
		 */
		public function getPosts( $untrusted ) {
			
				$defaults = array(
					'url'           => '',
					'category_id'	=> '',
					'per_page'      => 10,
					'order'         => 'DESC',
					'orderby'       => 'date',
					'cache_timeout' => DAY_IN_SECONDS,
					'excerpt_length'        => false,
					'excerpt_more'          => false,
					'excerpt_more_link'     => false,
					'include_excerpt' 		=> false,
					'include_excerpt_dash'  => true,
				);
		
			$atts = shortcode_atts( $defaults, $untrusted );

			$atts['url'] = esc_url( filter_var( $atts['url'], FILTER_SANITIZE_URL ) );

			if ( 0 >= strlen( $atts['url'] ) ) {

				return new WP_Error(
					'invalid_url',
					__( 'Remote site URL must be provided.', 'display-posts-shortcode-remote' ),
					$atts['url']
				);
			}
			// Getting the excerpt atts
			$excerpt_length        = (int) $atts['excerpt_length'];
			$excerpt_more          = sanitize_text_field( $atts['excerpt_more'] );
			$excerpt_more_link     = filter_var( $atts['excerpt_more_link'], FILTER_VALIDATE_BOOLEAN );
			$include_excerpt       = filter_var( $atts['include_excerpt'], FILTER_VALIDATE_BOOLEAN );
			$include_excerpt_dash  = filter_var( $atts['include_excerpt_dash'], FILTER_VALIDATE_BOOLEAN );


			$url = trailingslashit( $atts['url'] ) . 'wp-json/wp/v2/posts';
			$url = add_query_arg( '_embed' , '', $url );

			if ( ! empty( $atts['category_id'] ) ) {

				if ( is_array( $atts['category_id'] ) ) {

					$atts['category_id'] = implode( ',', $atts['category_id'] );
				}

				$url = add_query_arg( 'categories', $atts['category_id'], $url );
			}

			$url = add_query_arg(
				array(
					'per_page' => $atts['per_page'],
					'order'    => $atts['order'],
					'orderby'  => $atts['orderby'],
				),
				$url
			);

			if ( 0 >= $atts['cache_timeout'] ) {

				$this->clearCache( $url );
			}

			if ( FALSE === $response = $this->getCache( $url ) ) {

				$response = wp_safe_remote_get( $url );

				if ( ! is_wp_error( $response ) && 0 < $atts['cache_timeout'] ) {

					/*
					 * NOTE: cache will be saved during Gutenberg autosaves via the REST API.
					 */
					$this->setCache( $url, $response, $atts['cache_timeout'] );
				}
			}

			if ( is_wp_error( $response ) ) {

				return $response;
			}

			$posts = json_decode( wp_remote_retrieve_body( $response ) );
						
			if ( JSON_ERROR_NONE !== json_last_error() ) {

				return new WP_Error(
					'invalid_response',
					json_last_error_msg(),
					$posts
				);
			}
	
			return $posts;	
		}

		/**
		 * The shortcode default options/values.
		 *
		 * @since 1.0
		 *
		 * @return array
		 */
		public function getDefaults() {

			return array(
				'category_id'           => '',
				'content_class'         => 'content',
				'date_format'           => '(n/j/Y)',
				'include_content'       => FALSE,
				'include_date'          => FALSE,
				'include_date_modified' => FALSE,
				'include_link'          => TRUE,
				'include_title'         => TRUE,
				'image_size'            => 'thumbnail',
				'no_posts_message'      => __( 'No posts to display.', 'display-posts-shortcode-remote' ),
				'order'                 => 'desc',
				'orderby'               => 'date',
				'posts_per_page'        => 10,
				'title'                 => '',
				'url'                   => '',
				'wrapper'               => 'ul',
				'wrapper_class'         => 'display-posts-listing',
				'cache_timeout'         => DAY_IN_SECONDS,
			);
		}

		/**
		 * Parse and sanitize the user supplied shortcode values.
		 *
		 * @since 1.0
		 *
		 * @param array $untrusted The user defined shortcode attributes.
		 *
		 * @return array
		 */
		public function parseShortcodeAtts( $untrusted ) {

			$defaults = Display_Posts_Remote()->getDefaults();
			$atts     = shortcode_atts( $defaults, $untrusted, 'display-posts-remote' );

			$restSupportOrderby = array(
				'author',
				'date',
				'id',
				'include',
				'modified',
				'parent',
				'relevance',
				'slug',
				'include_slugs',
				'title',
			);

			$atts['category_id']           = wp_parse_id_list( $atts['category_id'] );
			$atts['content_class']         = array_map( 'sanitize_html_class', ( explode( ' ', $atts['content_class'] ) ) );
			$atts['date_format']           = sanitize_text_field( $atts['date_format'] );
			$atts['include_content']       = self::toBoolean( $atts['include_content'] );
			$atts['include_date']          = self::toBoolean( $atts['include_date'] );
			$atts['include_date_modified'] = self::toBoolean( $atts['include_date_modified'] );
			$atts['include_link']          = self::toBoolean( $atts['include_link'] );
			$atts['include_title']         = self::toBoolean( $atts['include_title'] );
			$atts['image_size']            = sanitize_key( $atts['image_size'] );
			$atts['no_posts_message']      = sanitize_text_field( $atts['no_posts_message'] );
			$atts['order']                 = in_array( strtolower( $atts['order'] ), array( 'asc', 'desc' ) ) ? strtolower( sanitize_key( $atts['order'] ) ) : 'desc';
			$atts['orderby']               = in_array( strtolower( $atts['orderby'] ), $restSupportOrderby ) ? strtolower( sanitize_key( $atts['orderby'] ) ) : 'date';
			$atts['posts_per_page']        = filter_var(
				$atts['posts_per_page'],
				FILTER_VALIDATE_INT,
				array(
					'options' => array(
						'min_range' => 1,
						'max_range' => 100,
						'default'   => 10,
					),
				)
			);
			$atts['title']                 = sanitize_text_field( $atts['title'] );
			$atts['url']                   = filter_var( $atts['url'], FILTER_SANITIZE_URL );
			$atts['wrapper']               = sanitize_text_field( $atts['wrapper'] );
			$atts['wrapper_class']         = array_map( 'sanitize_html_class', explode( ' ', $atts['wrapper_class'] ) );

			$atts['cache_timeout']         = absint( $atts['cache_timeout'] );
			

			// Map shortcode option to REST API Parameter.
			$atts['per_page']              = $atts['posts_per_page'];

			// Excerpt
			$atts['excerpt_length']         = isset($untrusted['excerpt_length'])   	? (int) $untrusted['excerpt_length'] 										: $atts['excerpt_length'];
			$atts['excerpt_more']           = isset($untrusted['excerpt_more']) 		? sanitize_text_field($untrusted['excerpt_more']) 							: $atts['excerpt_more'];
			$atts['excerpt_more_link']      = isset($untrusted['excerpt_more_link']) 	? filter_var($untrusted['excerpt_more_link'], FILTER_VALIDATE_BOOLEAN) 		: $atts['excerpt_more_link'];
			$atts['include_excerpt']        = isset($untrusted['include_excerpt']) 		? filter_var($untrusted['include_excerpt'], FILTER_VALIDATE_BOOLEAN) 		: $atts['include_excerpt'];
			$atts['include_excerpt_dash']   = isset($untrusted['include_excerpt_dash']) ? filter_var($untrusted['include_excerpt_dash'], FILTER_VALIDATE_BOOLEAN) 	: $atts['include_excerpt_dash'];

			return $atts;
		}

		/**
		 * Callback for the `display-posts-remote` shortcode.
		 *
		 * @since 1.0
		 *
		 * @param array  $untrusted
		 * @param string $content
		 * @param string $tag
		 *
		 * @return string
		 */
		public static function shortcode( $untrusted, $content, $tag = 'display-posts-remote' ) {

			$self = Display_Posts_Remote();

			$html = '';
			$atts = $self->parseShortcodeAtts( $untrusted );

			$result = $self->getPosts( $atts );

			if ( is_wp_error( $result ) ) {

				return '<div>' . $result->get_error_message() . '</div>';
			}

			if ( empty( $result ) || ! is_array( $result ) ) {

				/**
				 * Filter content to display if no posts match the current query.
				 *
				 * @since 1.1
				 *
				 * @param string $no_posts_message Content to display, returned via {@see wpautop()}.
				 */
				return apply_filters( 'display_posts_shortcode_no_results', wpautop( $atts['no_posts_message'] ) );
			}

			// Set up html elements used to wrap the posts.
			// Default is ul/li, but can also be ol/li and div/div.
			if ( ! in_array( $atts['wrapper'], array( 'ul', 'ol', 'div' ) ) ) {

				$atts['wrapper'] = 'ul';
			}

			if ( ! in_array( 'display-posts-listing', $atts['wrapper_class'] )) {
				$atts['wrapper_class'][] = 'display-posts-listing row';
			}
			 $wrapper_class = implode( ' ', $atts['wrapper_class'] ) ;
			
		
			$itemElement = 'div' === $atts['wrapper'] ? 'div' : 'li';

			foreach ( $result as $data ) {

				$image = $date = $postContent = '';

				$post = new Display_Posts_Remote_Post( $data );
				$excerpt = '';


				if ( $atts['include_title'] && $atts['include_link'] ) {

					$title = '<span class="title h4"><a class="text-decoration-none stretched-link" href="' . esc_url( $post->get_permalink() ) . '">' . esc_attr( strip_tags( $post->get_the_title() ) ) . '</a></span>';

				} elseif ( $atts['include_title'] ) {

					$title = '<span class="title h4">' . esc_attr( strip_tags( $post->get_the_title() ) ) . '</span>';

				} else {

					$title = '';
				}

				$imageAttributes = $self->getImageAttributes( $atts );

				if ( $atts['image_size'] && $post->has_post_thumbnail() && $atts['include_link'] ) {

					$image = '<a class="image pb-4" href="' . esc_url( $post->get_permalink() ) . '">' . $post->get_the_post_thumbnail( $atts['image_size'], $imageAttributes ) . '</a> ';

				} elseif ( $atts['image_size'] && $post->has_post_thumbnail() ) {

					$image = '<span class="image pb-4">' . $post->get_the_post_thumbnail( $atts['image_size'], $imageAttributes ) . '</span> ';

				} elseif ( $post->has_featured_media() && $atts['include_link'] ) {

					$image = '<a class="image pb-4" href="' . esc_url( $post->get_permalink() ) . '">' . $post->get_the_post_thumbnail( 'full', $imageAttributes ) . '</a> ';

				} elseif ( $post->has_featured_media() ) {

					$image = '<span class="image pb-4">' . $post->get_the_post_thumbnail( 'full', $imageAttributes ) . '</span> ';

				}

				if ( $atts['include_date'] ) {

					$date = ' <span class="date">' . $post->get_the_date( $atts['date_format'] ) . '</span>';

				} elseif ( $atts['include_date_modified'] ) {

					$date = ' <span class="date">' . $post->get_the_modified_date( $atts['date_format'] ) . '</span>';
				}

				if ( $atts['include_content'] ) {

					$postContent = '<div class="' . implode( ' ', $atts['content_class'] ) . '">' . $post->get_the_content() . '</div>';
				}
				// If excerpt is filled 
				if ( $atts['include_excerpt'] ) {
					
					$excerpt = isset($data->excerpt->rendered) ? wpautop($data->excerpt->rendered) : '';
					$excerpt = wp_trim_words(strip_shortcodes($excerpt), $atts['excerpt_length']);
					if ($atts['include_excerpt_dash']) {
						$excerpt = '<span class="excerpt-dash">-</span>' . $excerpt;
					}
					
				}
				 // If excerpt isn't provided, create it from content
				 if (empty($excerpt) && $atts['include_excerpt']) {
					$content = isset($data->content->rendered) ? wpautop($data->content->rendered) : '';
					$excerpt = wp_trim_words(strip_shortcodes($content), $atts['excerpt_length']);
					if ($atts['excerpt_more']) {
						$excerpt .= ' ' . $atts['excerpt_more'];
					}
				}
		

				/**
				 * Filter the HTML markup for output via the shortcode.
				 *
				 * Use the same filter name and pass the same values so existing filters work on DPS Remote.
				 *
				 * @since 1.0
				 *
				 * @param string $html          The shortcode's HTML output.
				 * @param array  $original_atts Original attributes passed to the shortcode.
				 * @param string $image         HTML markup for the post's featured image element.
				 * @param string $title         HTML markup for the post's title element.
				 * @param string $date          HTML markup for the post's date element.
				 * @param string $excerpt       HTML markup for the post's excerpt element.
				 * @param string $inner_wrapper Type of container to use for the post's inner wrapper element.
				 * @param string $content       The post's content.
				 * @param string $class         Space-separated list of post classes to supply to the $inner_wrapper element.
				 * @param string $author        HTML markup for the post's author.
				 * @param string $category_display_text
				 */
				$html .= apply_filters(
					'display_posts_shortcode_output',
					"<{$itemElement} class=\"listing-item col-lg-6 mb-3 px-1\"><div class=\"h-100 p-3 mr-1\">{$image}{$title}{$date}{$postContent}{$excerpt}</div></{$itemElement}>" . PHP_EOL,
					array(), // $original_atts
					$image,
					$title,
					$date,
					$excerpt, // $excerpt
					$itemElement, // $inner_wrapper
					$postContent, // $content
					array( 'listing-item' ), // $class
					'', // $author
					''  // $category_display_text
				);
			}

			if ( 0 < strlen( $atts['title'] ) ) {

				/**
				 * Filter the shortcode output title tag element.
				 *
				 * @since 1.1
				 *
				 * @param string $tag           Type of element to use for the output title tag. Default 'h2'.
				 * @param array  $original_atts Original attributes passed to the shortcode.
				 */
				$titleTag = apply_filters( 'display_posts_shortcode_title_tag', 'h2', $atts );

				$heading  = '<' . $titleTag . ' class="display-posts-title">' . $atts['title'] . '</' . $titleTag . '>' . "\n";

				$html = $heading . $html;
			}

			$open  = "<{$atts['wrapper']} class='$wrapper_class'>" . PHP_EOL;
			$close = "</{$atts['wrapper']}>" . PHP_EOL;

			return $open . $html . $close;
		}

		/**
		 * Return the image attributes that should be applied ti the image tags.
		 *
		 * This is primarily to support DPS Pinch Zoomer add on.
		 *
		 * @since 1.0
		 *
		 * @param array $atts
		 *
		 * @return array
		 */
		public function getImageAttributes( $atts = array() ) {

			$attributes = array();

			/*
			 * Support DPS Pinch Zoomer.
			 */
			if ( function_exists( 'Display_Posts_Pinch_Zoomer' ) ) {

				$options    = Display_Posts_Pinch_Zoomer()->shortcodeAtts( $atts );
				$attributes = Display_Posts_Pinch_Zoomer()->postImageAttributes( $options );
			}

			return $attributes;
		}

		/**
		 * Converts the following strings: yes/no; true/false and 0/1 to boolean values.
		 * If the supplied string does not match one of those values the method will return NULL.
		 *
		 * @since 1.0
		 *
		 * @param string|int|bool $value
		 *
		 * @return bool
		 */
		public static function toBoolean( &$value ) {

			// Already a bool, return it.
			if ( is_bool( $value ) ) return $value;

			$value = filter_var( strtolower( $value ), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );

			if ( is_null( $value ) ) {

				$value = FALSE;
			}

			return $value;
		}
	}

	/**
	 * @since 1.0
	 *
	 * @return Display_Posts_Remote
	 */
	function Display_Posts_Remote() {

		return Display_Posts_Remote::instance();
	}

	Display_Posts_Remote();
}
