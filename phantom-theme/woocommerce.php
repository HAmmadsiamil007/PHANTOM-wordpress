<?php
defined( 'ABSPATH' ) || exit;
get_header( 'shop' ); ?>
<div class="container mt-5 mb-5">
    <div class="row">
        <div class="col-lg-9">
            <?php woocommerce_content(); ?>
        </div>
        <div class="col-lg-3">
            <?php do_action( 'woocommerce_sidebar' ); ?>
        </div>
    </div>
</div>
<?php get_footer( 'shop' ); ?>