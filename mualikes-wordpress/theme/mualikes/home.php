<?php get_header();
?>
<div class="content-header">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-auto mr-auto"><h1 class="m-0 title">Blog <?php echo esc_html(mlt_setting('site_name', 'MuaLikes.Net')); ?></h1></div>
            <div class="col-auto">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url()); ?>" title="Trang chủ">Trang chủ</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Blog bài viết</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>
<?php
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
