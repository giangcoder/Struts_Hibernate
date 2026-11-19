<?php $cats = get_the_category(); $c = $cats ? $cats[0] : null; ?>
<div class="card flex-md-row mb-4 box-shadow">
    <div class="col-md-3 my-auto p-0">
        <div class="post-thumbnail">
            <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
                <img src="<?php echo esc_url(mlt_thumb()); ?>" alt="<?php the_title_attribute(); ?>"></a>
        </div>
    </div>
    <div class="card-body my-auto">
        <?php if ($c) : ?>
        <div class="mb-2">
            <a href="<?php echo esc_url(get_category_link($c)); ?>" title="<?php echo esc_attr($c->name); ?>"><span class="badge badge-primary d-inline-block"><?php echo esc_html($c->name); ?></span></a>
        </div>
        <?php endif; ?>
        <h3 class="mb-2">
            <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>" class="text-dark"><?php the_title(); ?></a>
        </h3>
        <div class="card-text mb-2">
            <p><?php echo esc_html(wp_trim_words(get_the_excerpt() ?: wp_strip_all_tags(get_the_content()), 30, '')); ?> ....
            <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">Đọc Tiếp »</a></p>
        </div>
        <span class="text-muted"><?php echo esc_html(get_the_date('d/m/Y')); ?></span>
    </div>
</div>
