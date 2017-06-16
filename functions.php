<?php
/**
 * Expound functions and definitions
 *
 * @package Expound
 */

/**
 * Set the content width based on the theme's design and stylesheet.
 */
if ( ! isset( $content_width ) )
	$content_width = 700; /* pixels */

/*
 * Load Jetpack compatibility file.
 */
require( get_template_directory() . '/inc/jetpack.php' );

if ( ! function_exists( 'expound_setup' ) ) :
/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which runs
 * before the init hook. The init hook is too late for some features, such as indicating
 * support post thumbnails.
 */
function expound_setup() {

	/**
	 * Custom template tags for this theme.
	 */
	require( get_template_directory() . '/inc/template-tags.php' );

	/**
	 * Custom functions that act independently of the theme templates
	 */
	require( get_template_directory() . '/inc/extras.php' );

	/**
	 * Customizer additions
	 */
	require( get_template_directory() . '/inc/customizer.php' );

	/**
	 * Make theme available for translation
	 * Translations can be filed in the /languages/ directory
	 * If you're building a theme based on Mag, use a find and replace
	 * to change 'expound' to the name of your theme in all the template files
	 */
	load_theme_textdomain( 'expound', get_template_directory() . '/languages' );

	/**
	 * Add default posts and comments RSS feed links to head
	 */
	add_theme_support( 'automatic-feed-links' );

	/**
	 * Editor styles for the win
	 */
	add_editor_style( 'css/editor-style.css' );

	/**
	 * Enable support for Post Thumbnails on posts and pages
	 *
	 * @link http://codex.wordpress.org/Function_Reference/add_theme_support#Post_Thumbnails
	 */
	add_theme_support( 'post-thumbnails' );
	set_post_thumbnail_size( 220, 126, true );
	add_image_size( 'expound-featured', 460, 260, true );
	add_image_size( 'expound-mini', 50, 50, true );

	/**
	 * This theme uses wp_nav_menu() in one location.
	 */
	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'expound' ),
		'social' => __( 'Social Menu', 'expound' ),
	) );

	/**
	 * Enable support for Post Formats
	 */
	add_theme_support( 'post-formats', array( 'aside', 'image', 'video', 'quote', 'link' ) );

	/**
	 * Enable support for Custom Background
	 */
	add_theme_support( 'custom-background', array(
		'default-color' => '333333',
	) );
}
endif; // expound_setup
add_action( 'after_setup_theme', 'expound_setup' );


/**
 * BuddyPress styles if BP is installed
 */
if ( function_exists( 'buddypress' ) ) {
	require( get_template_directory() . '/inc/buddypress.php' );
}

/**
 * Register widgetized area and update sidebar with default widgets
 */
function expound_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Sidebar', 'expound' ),
		'id'            => 'sidebar-1',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h1 class="widget-title">',
		'after_title'   => '</h1>',
	) );
}
add_action( 'widgets_init', 'expound_widgets_init' );

/**
 * Enqueue scripts and styles
 */
function expound_scripts() {
	// Don't forget to bump the version numbers in style.css and editor-style.css
	wp_enqueue_style( 'expound-style', get_stylesheet_uri(), array(), 20140129 );

	wp_enqueue_script( 'expound-navigation', get_template_directory_uri() . '/js/navigation.js', array(), '20120206', true );

	wp_enqueue_script( 'expound-skip-link-focus-fix', get_template_directory_uri() . '/js/skip-link-focus-fix.js', array(), '20130115', true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	if ( is_singular() && wp_attachment_is_image() ) {
		wp_enqueue_script( 'expound-keyboard-image-navigation', get_template_directory_uri() . '/js/keyboard-image-navigation.js', array( 'jquery' ), '20120202' );
	}

	if ( has_nav_menu( 'social' ) ) {
		wp_enqueue_style( 'expound-genericons', get_template_directory_uri() . '/css/genericons.css', array(), '20140127' );
	}
}
add_action( 'wp_enqueue_scripts', 'expound_scripts' );

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Additional helper post classes
 */
function expound_post_class( $classes ) {
	if ( has_post_thumbnail() )
		$classes[] = 'has-post-thumbnail';
	return $classes;
}
add_filter('post_class', 'expound_post_class' );

/**
 * Ignore and exclude featured posts on the home page.
 */
function expound_pre_get_posts( $query ) {
	if ( ! $query->is_main_query() || is_admin() )
		return;

	if ( $query->is_home() ) { // condition should be (almost) the same as in index.php
		$query->set( 'ignore_sticky_posts', true );

		$exclude_ids = array();
		$featured_posts = expound_get_featured_posts();

		if ( $featured_posts->have_posts() )
			foreach ( $featured_posts->posts as $post )
				$exclude_ids[] = $post->ID;

		$query->set( 'post__not_in', $exclude_ids );
	}
}
add_action( 'pre_get_posts', 'expound_pre_get_posts' );

if ( ! function_exists( 'expound_get_featured_posts' ) ) :
/**
 * Returns a new WP_Query with featured posts.
 */
function expound_get_featured_posts() {
	global $wp_query;

	// Default number of featured posts
	$count = apply_filters( 'expound_featured_posts_count', 5 );

	// Jetpack Featured Content support
	$sticky = apply_filters( 'expound_get_featured_posts', array() );
	if ( ! empty( $sticky ) ) {
		$sticky = wp_list_pluck( $sticky, 'ID' );

		// Let Jetpack override the sticky posts count because it has an option for that.
		$count = count( $sticky );
	}

	if ( empty( $sticky ) )
		$sticky = (array) get_option( 'sticky_posts', array() );

	if ( empty( $sticky ) ) {
		return new WP_Query( array(
			'posts_per_page' => $count,
			'ignore_sticky_posts' => true,
		) );
	}

	$args = array(
		'posts_per_page' => $count,
		'post__in' => $sticky,
		'ignore_sticky_posts' => true,
	);

	return new WP_Query( $args );
}
endif;

if ( ! function_exists( 'expound_get_related_posts' ) ) :
/**
 * Returns a new WP_Query with related posts.
 */
function expound_get_related_posts() {
	$post = get_post();

	// Support for the Yet Another Related Posts Plugin
	if ( function_exists( 'yarpp_get_related' ) ) {
		$related = yarpp_get_related( array( 'limit' => 3 ), $post->ID );
		return new WP_Query( array(
			'post__in' => wp_list_pluck( $related, 'ID' ),
			'posts_per_page' => 3,
			'ignore_sticky_posts' => true,
			'post__not_in' => array( $post->ID ),
		) );
	}

	$args = array(
		'posts_per_page' => 3,
		'ignore_sticky_posts' => true,
		'post__not_in' => array( $post->ID ),
	);

	// Get posts from the same category.
	$categories = get_the_category();
	if ( ! empty( $categories ) ) {
		$category = array_shift( $categories );
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'category',
				'field' => 'id',
				'terms' => $category->term_id,
			),
		);
	}

	return new WP_Query( $args );
}
endif;

/**
 * Footer credits.
 */
function riopost_display_credits() {
	$text = '<a href="http://riopost.com.br/" rel="generator">' . 'the rio post' . '</a>';
	$text .= '<span class="sep"> | </span>';
	$text .= ' ® 2017<br>';
	$text .= 'Todos os direitos reservados.';
	echo apply_filters( 'expound_credits_text', $text );
}
add_action( 'riopost_credits', 'riopost_display_credits' );

/**
 * Decrease caption width for non-full-width images. Pixelus perfectus!
 */
function expound_shortcode_atts_caption( $attr ) {
	global $content_width;

	if ( isset( $attr['width'] ) && $attr['width'] < $content_width )
		$attr['width'] -= 4;

	return $attr;
}
add_filter( 'shortcode_atts_caption', 'expound_shortcode_atts_caption' );

// Caixa do autor no fim do post
function wpb_author_info_box( $content ) {

	global $post;

	// Detect if it is a single post with a post author
	if ( is_single() && isset( $post->post_author ) ) {
		// Get author's display name 
		$display_name = get_the_author_meta( 'display_name', $post->post_author );

		// If display name is not available then use nickname as display name
		if ( empty( $display_name ) )
			$display_name = get_the_author_meta( 'nickname', $post->post_author );

		// Get author's biographical information or description
		$user_description = get_the_author_meta( 'user_description', $post->post_author );

		// Get author's website URL 
		$user_website = get_the_author_meta('url', $post->post_author);

		// Get link to the author archive page
		$user_posts = get_author_posts_url( get_the_author_meta( 'ID' , $post->post_author));

		if ( $display_name != 'Colaborador' &&
			( ! empty( $user_description ) ) ) {
			 
			if ( ! empty( $display_name ) )
				$author_details = '<p class="author_name">Sobre ' . $display_name . '</p>';

			if ( ! empty( $user_description ) ) {
				// Author avatar and bio
				$author_details .= '<p class="author_details">' . get_avatar( get_the_author_meta('user_email') , 90 )
					. nl2br( $user_description ) . '</p>';
			}

			$author_details .= '<p class="author_links"><a href="'. $user_posts .'">Veja outras postagens de ' . $display_name
				. '</a>';  

			// Check if author has a website in their profile
			if ( ! empty( $user_website ) ) {
				// Display author website link
				$author_details .= ' | <a href="' . $user_website .'" target="_blank" rel="nofollow">Site pessoal</a></p>';

			} else { 
				// if there is no author website then just close the paragraph
				$author_details .= '</p>';
			}

			// Pass all this info to post content  
			$content = $content . '<footer class="author_bio_section" >' . $author_details . '</footer>';
		}
	}
	return $content;
}
// Adiciona a caixa no filtro do post content 
add_action( 'the_content', 'wpb_author_info_box' );
// Habilita codigo HTML na caixa do autor 
remove_filter('pre_user_description', 'wp_filter_kses');
