<?php
if (is_home()) {
    require __DIR__ . '/home.php';
    return;
}
get_header();
mlt_content_header(is_singular() ? get_the_title() : get_bloginfo('name'), []);
?>
<div class="content">
    <div class="container-fluid">
        <div class="card card-primary card-outline"><div class="card-body">
            <?php if (have_posts()) : while (have_posts()) : the_post(); the_content(); endwhile; endif; ?>
        </div></div>
    </div>
</div>
<?php get_footer();
