<?php get_header(); ?>
<div class="content-header">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-auto mr-auto">
                <h1 class="m-0 title"> <?php echo esc_html(mlt_setting('site_name', 'MuaLikes.Net')); ?> </h1>
            </div>
        </div>
    </div>
</div>
<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="row">
                    <div class="col-lg-9">
                        <div class="card card-outline">
                            <div class="card-body p-3">
                                <?php if (!mlt_ready()) : ?>
                                    <div class="alert alert-warning">Vui lòng kích hoạt plugin <strong>MuaLikes Core</strong>.</div>
                                <?php endif; ?>
                                <?php foreach (mlt_ready() ? ML_Services::menu() : [] as $g) :
                                    if ($g['vip'] || !$g['items']) {
                                        continue;
                                    } ?>
                                    <h5 id="<?php echo esc_attr($g['anchor']); ?>" class="mb-4 text-service-dark-primary"> <img src="<?php echo esc_url($g['icon']); ?>" style="max-width: 25px;"> <?php echo esc_html($g['name']); ?></h5>
                                    <div class="row mb-2">
                                        <?php foreach ($g['items'] as $it) : ?>
                                            <div class="col-md-4 col-sm-6 col-12">
                                                <a href="<?php echo esc_url($it['url']); ?>">
                                                    <div class="info-box service-dark-primary mh-50">
                                                        <span class="info-box-icon">
                                                            <img src="<?php echo esc_url($it['icon']); ?>" style="max-width: 25px;">
                                                        </span>
                                                        <div class="info-box-content">
                                                            <span class="info-box-text"><?php echo esc_html($it['title']); ?></span>
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php get_template_part('template-parts/home-right'); ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php get_footer(); ?>
