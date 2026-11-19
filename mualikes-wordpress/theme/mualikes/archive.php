<?php get_header();
$ml_t = single_term_title('', false);
if (!$ml_t) {
    $ml_t = wp_strip_all_tags(get_the_archive_title());
}
mlt_content_header($ml_t, [['Blog bài viết', home_url('/blog/')]]);
?>
<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-9">
                <div>
                    <?php if (have_posts()) : while (have_posts()) : the_post();
                        get_template_part('template-parts/post-card');
                    endwhile; else : ?>
                        <div class="card"><div class="card-body">Chưa có bài viết.</div></div>
                    <?php endif; ?>
                    <?php mlt_pagination(); ?>
                </div>
            </div>
            <?php get_template_part('template-parts/blog-sidebar'); ?>
        </div>
    </div>
</div>
<?php get_footer();
