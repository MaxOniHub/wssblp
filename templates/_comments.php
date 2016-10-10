<?php

if ( post_password_required() ) {
	return;
}
?>

<div id="comments" class="comments-area">

	<?php if ( have_comments() ) : ?>
		<h2 class="comments-title">
			<?php
			printf( _nx( 'One thought on &ldquo;%2$s&rdquo;', '%1$s thoughts on &ldquo;%2$s&rdquo;', get_comments_number(), 'comments title', 'twentyfifteen' ),
				number_format_i18n( get_comments_number() ), get_the_title() );
			?>
		</h2>

		<div class="comment-list">
			<?php
			$args = [
				'post_id'	  => get_the_ID(),
				'avatar_size' => 32,
			];
		 ?>
			<?php if (have_comments()) : while (have_comments()) : the_comment(); ?>

					<?php
						$comment_id = $comment->comment_ID;
						$user_id = $comment->user_id;
						$post_id = $comment->comment_post_ID;
						$comment_time = $comment->comment_date;
						$rating = get_user_rating_from_history($comment_id, $user_id, $post_id, $comment_time);
					?>

				<div id="comment-<?php comment_ID(); ?>">
					<article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
						<footer class="comment-meta">
							<div class="comment-author vcard">
								<?php function_exists('get_user_vote') ? get_user_vote($rating) : ''; ?>
								<?php if ( 0 != $args['avatar_size'] ) echo get_avatar( $comment, $args['avatar_size'] ); ?>
								<?php printf( __( '%s <span class="says">says:</span>' ), sprintf( '<b class="fn">%s</b>', get_comment_author_link() ) ); ?>
							</div><!-- .comment-author -->

							<div class="comment-metadata">
								<a href="<?php echo esc_url( get_comment_link( $comment->comment_ID, $args ) ); ?>">
									<time datetime="<?php comment_time( 'c' ); ?>">
										<?php printf( _x( '%1$s at %2$s', '1: date, 2: time' ), get_comment_date(), get_comment_time() ); ?>
									</time>
								</a>
								<?php edit_comment_link( __( 'Edit' ), '<span class="edit-link">', '</span>' ); ?>
							</div><!-- .comment-metadata -->

							<?php if ( '0' == $comment->comment_approved ) : ?>
								<p class="comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation.' ); ?></p>
							<?php endif; ?>
						</footer><!-- .comment-meta -->
						<div class="comment-content">
							<?php comment_text(); ?>
						</div><!-- .comment-content -->

					</article>

				</div>

			<?php endwhile; ?>

			<?php else : ?>

				<div <?php post_class(); ?> id="post-<?php the_ID(); ?>">
					<h1>Not Found</h1>
				</div>

			<?php endif; ?>
		</div><!-- .comment-list -->

	<?php endif; // have_comments() ?>

	<?php
	// If comments are closed and there are comments, let's leave a little note, shall we?
	if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) :
		?>
		<p class="no-comments">Comments are closed</p>
	<?php endif; ?>

	<?php /*comment_form();*/ ?>

</div><!-- .comments-area -->