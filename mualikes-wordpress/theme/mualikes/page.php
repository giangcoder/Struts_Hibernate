<?php get_header();
the_post();
mlt_content_header(get_the_title(), []);
?>
<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-primary card-outline">
                    <div class="card-body">
                        <?php the_content(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php get_footer();
