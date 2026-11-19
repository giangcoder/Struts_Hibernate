<?php get_header();
mlt_content_header('Tìm kiếm: ' . get_search_query(), [['Blog bài viết', home_url('/blog/')]]);
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
