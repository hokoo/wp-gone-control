<?php

namespace iTRON\WPGoneControl\Controller;

use WP_Post;
use WP_User;
use iTRON\WPGoneControl\Database;

if ( ! defined( 'ABSPATH' ) ) exit;

class MainController {
	private Database $database;
	private TemplateController $template_controller;

	public function __construct( Database $database, TemplateController $template_controller ) {
		$this->database            = $database;
		$this->template_controller = $template_controller;
	}

	public function register() {
		add_action( 'wp_trash_post', array( $this, 'handle_post_remove' ), 10 );
		add_action( 'before_delete_post', array( $this, 'handle_post_remove' ), 10 );
		add_action( 'pre_delete_term', array( $this, 'handle_term_remove' ), 10, 2 );
		add_action( 'delete_user', array( $this, 'handle_user_remove' ), 10, 1 );
		add_action( 'template_redirect', array( $this->template_controller, 'maybe_send_410' ), 0 );
	}

	public function handle_post_remove( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		if ( ! $this->post_is_public( $post ) ) {
			return;
		}

		$permalink = get_permalink( $post );
		if ( $permalink ) {
			$this->database->store_url( $permalink, 'post', (int) $post_id );
		}
	}

	public function handle_term_remove( $term, $taxonomy ) {
		$term_obj = get_term( $term, $taxonomy );
		if ( ! $term_obj || is_wp_error( $term_obj ) ) {
			return;
		}

		$taxonomy_obj = get_taxonomy( $taxonomy );
		if ( ! $taxonomy_obj || ! $taxonomy_obj->public ) {
			return;
		}

		$term_link = get_term_link( $term_obj, $taxonomy );
		if ( is_wp_error( $term_link ) ) {
			return;
		}

		if ( ! $this->is_term_public( (int) $term_obj->term_id, $taxonomy ) ) {
			return;
		}

		$this->database->store_url( $term_link, 'term', (int) $term_obj->term_id );
	}

	private function is_term_public( $term_id, $taxonomy ): bool {
		return apply_filters( 'itron/gone-control/is-term-public', true, $term_id, $taxonomy );
	}

	public function handle_user_remove( $user_id ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return;
		}

		if ( ! $this->user_is_public( $user ) ) {
			return;
		}

		$author_url = get_author_posts_url( $user->ID );
		if ( $author_url ) {
			$this->database->store_url( $author_url, 'user', (int) $user_id );
		}
	}

	private function post_is_public( WP_Post $post ): bool {
		$post_type = get_post_type_object( $post->post_type );
		$statuses  = apply_filters( 'itron/gone-control/post-statuses', [ 'publish' ] );

		if ( ! $post_type || ! $post_type->public ) {
			return false;
		}

		$public = in_array( $post->post_status, (array) $statuses, true );
		return apply_filters( 'itron/gone-control/is-post-public', $public, $post );
	}

	private function user_is_public( WP_User $user ): bool {
		// Attention: return boolean when filtering this!
		return apply_filters( 'itron/gone-control/is-user-public', true, $user );
	}
}
