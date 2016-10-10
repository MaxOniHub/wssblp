<?php
get_header();
?>
<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <div class="books-wrapper">
            <?php
            $query = new WP_Query(array('post_type' => 'book', 'paged' => $paged));

            if ($query->have_posts()) : ?>
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <?php $meta = get_post_meta(get_the_ID()); ?>
                    <div class="entry">
                        <div class="thumb">
                            <?php
                            if (has_post_thumbnail()): ?>
                                <a href="<?php the_permalink(); ?>"><?php the_post_thumbnail(); ?></a>
                            <?php endif; ?>
                        </div>
                        <div class="info">
                            <div class="header">
                                <div class="title">
                                    <h3><a href="<?php the_permalink(); ?>"><?php the_title() ?></a></h3>
                                </div>
                                <div class="author">
                                    <span>Author: </span><?= $meta['author'][0] ?></div>
                                </div>
                            <div class="left-col">
                                <p class="isbn_10"><span>isbn 10: </span><?= $meta['isbn_10'][0] ?></p>

                                <p class="isbn_13"><span>isbn 13: </span><?= $meta['isbn_13'][0] ?></p>

                                <p class="date_added"><span>Added: </span><?= $meta['date_added'][0] ?></p>
                            </div>
                            <div class="right-col">
                                <?php global $user_login; ?>

                                <?php if ($user_login) : ?>
                                    <?php include("_toggle_button.php"); ?>

                                    <?php include("_ratings.php"); ?>
                                <?php else: ?>
                                    <span>You must be logged</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <hr/>
                <?php endwhile;
                wp_reset_postdata(); ?>
            <?php else : ?>
                <!-- show 404 error here -->
            <?php endif; ?>

			<?php the_posts_pagination([
				'mid_size' => 2,
				'prev_text' => __('Back', 'textdomain'),
				'next_text' => __('Next', 'textdomain'),
			]); ?>
        </div>
    </main>
    <!-- .site-main -->
</div><!-- .content-area -->

<?php include('_review_modal.php'); ?>
<?php include('_check_out_modal.php'); ?>

<?php get_footer(); ?>
