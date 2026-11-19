<div class="col-lg-3">
    <div class="card card-category">
        <div class="card-header" style="background-color: rgba(0,0,0,.03);">Chuyên mục</div>
        <div class="list-group list-group-flush border-bottom">
            <?php foreach (get_categories(['hide_empty' => false, 'exclude' => [get_option('default_category')], 'orderby' => 'term_id']) as $c) : ?>
                <a href="<?php echo esc_url(get_category_link($c)); ?>" title="<?php echo esc_attr($c->name); ?>" class="list-group-item list-group-item-action"><?php echo esc_html($c->name); ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card card-tag">
        <div class="card-header" style="background-color: rgba(0,0,0,.03);">Từ khóa</div>
        <div class="card-body p-2">
            <?php foreach (get_tags(['number' => 60, 'orderby' => 'term_id', 'order' => 'DESC']) as $t) : ?>
                <a href="<?php echo esc_url(get_tag_link($t)); ?>" title="<?php echo esc_attr($t->name); ?>"><span class="badge badge-primary"><?php echo esc_html($t->name); ?></span></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
