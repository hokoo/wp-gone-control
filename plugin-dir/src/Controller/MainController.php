<?php

namespace iTRON\WPGoneControl\Controller;

use WP_Post;
use WP_User;
use iTRON\WPGoneControl\Database;

class MainController {
	private $database;
	private $template_controller;

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

	public function activate() {
		$this->database->activate();
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
		if ( ! is_wp_error( $term_link ) ) {
			$this->database->store_url( $term_link, 'term', (int) $term_obj->term_id );
		}
	}

	public function handle_user_remove( $user_id ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return;
		}

		$is_public = apply_filters( 'wp_gone_control_is_user_public', $this->user_is_public( $user ), $user );
		if ( ! $is_public ) {
			return;
		}

		$author_url = get_author_posts_url( $user->ID );
		if ( $author_url ) {
			$this->database->store_url( $author_url, 'user', (int) $user_id );
		}
	}

	private function post_is_public( WP_Post $post ) {
		$post_type = get_post_type_object( $post->post_type );
		$statuses  = apply_filters( 'wp_gone_control_post_statuses', array( 'publish' ) );

		if ( ! $post_type || ! $post_type->public ) {
			return false;
		}

		return in_array( $post->post_status, (array) $statuses, true );
	}

	private function user_is_public( WP_User $user ) {
		return true;
	}
}
