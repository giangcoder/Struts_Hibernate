<?php get_header();
the_post();
mlt_content_header(get_the_title(), [['Blog bài viết', home_url('/blog/')]]);
[$toc, $content] = mlt_toc(apply_filters('the_content', get_the_content()));
?>
<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-9">
                <div class="invoice p-3 mb-3">
                    <span class="text-muted">Tác giả:</span> <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>"><?php the_author(); ?></a> |
                    <span class="text-muted">Đăng lúc:</span> <?php echo esc_html(get_the_date('d/m/Y')); ?>
                    <hr>
                    <div class="content">
                        <?php echo $toc; ?>
                        <?php echo $content; ?>
                    </div>
                    <?php $tags = get_the_tags(); if ($tags) : ?>
                    <div class="mt-4">
                        <span class="text-muted">Từ khóa:</span>
                        <?php foreach ($tags as $t) : ?>
                            <a href="<?php echo esc_url(get_tag_link($t)); ?>" title="<?php echo esc_attr($t->name); ?>"><span class="badge badge-primary d-inline-block"><?php echo esc_html($t->name); ?></span></a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php
                    $cats = wp_get_post_categories(get_the_ID());
                    $rel = new WP_Query(['post__not_in' => [get_the_ID()], 'category__in' => $cats, 'posts_per_page' => 6, 'ignore_sticky_posts' => true]);
                    if ($rel->have_posts()) : ?>
                        <hr>
                        <h5 class="mb-4">Bài viết liên quan</h5>
                        <div class="row">
                            <?php while ($rel->have_posts()) : $rel->the_post(); ?>
                                <div class="col-md-4">
                                    <div class="card mb-4 box-shadow">
                                        <div class="post-thumbnail">
                                            <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><img class="card-img-top" src="<?php echo esc_url(mlt_thumb()); ?>" alt="<?php the_title_attribute(); ?>"></a>
                                        </div>
                                        <div class="card-body p-3">
                                            <p class="card-text">
                                                <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>" class="text-dark"><?php the_title(); ?></a>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="btn-group">
                                                    <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>" class="btn btn-sm btn-primary">Xem thêm</a>
                                                </div>
                                                <small class="text-muted"><?php echo esc_html(get_the_date('d/m/Y')); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php get_template_part('template-parts/blog-sidebar'); ?>
        </div>
    </div>
</div>
<script>jQuery(function($){ $('body').on('click','.is-open',function(){$('.is-open').addClass('d-none');$('.toc_content').addClass('d-none');$('.is-close').addClass('d-block');}).on('click','.is-close',function(){$('.is-open').removeClass('d-none');$('.toc_content').removeClass('d-none');$('.is-close').removeClass('d-block');}); });</script>
<?php get_footer();
