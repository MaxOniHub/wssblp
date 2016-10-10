<?php if (isset($meta)) : ?>
    <!--output rating-->
    <?php if (function_exists('the_ratings')) : ?>
		<?php echo expand_ratings_template('<div class="rating">%RATINGS_IMAGES%</div>', get_the_ID()) ?>
	<?php endif; ?>
<?php endif; ?>
