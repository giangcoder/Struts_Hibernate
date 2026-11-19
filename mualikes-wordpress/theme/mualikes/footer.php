        </div>
        <footer class="main-footer">
            <div class="row justify-content-md-center">
                <div class="col-12 col-lg-4 text-md-left text-center mb-2 mb-md-0">
                    <strong>Copyright &copy; <?php echo esc_html(date_i18n('Y')); ?> <a href="<?php echo esc_url(home_url()); ?>"><?php echo esc_html(mlt_setting('footer_copyright', 'MuaLikes.Net')); ?></a>.</strong> All rights reserved.
                </div>
                <div class="col-12 col-lg-4 text-center mb-2 mb-md-0">
                    <a href="<?php echo esc_url(home_url('/gioi-thieu/')); ?>">Giới thiệu</a> |
                    <a href="<?php echo esc_url(home_url('/lien-he/')); ?>">Liên hệ</a> |
                    <a href="<?php echo esc_url(home_url('/chinh-sach-bao-mat/')); ?>">Chính sách bảo mật</a> |
                    <a href="<?php echo esc_url(home_url('/dieu-khoan-dich-vu/')); ?>">Điều khoản dịch vụ</a> |
                    <a href="<?php echo esc_url(home_url('/docs/document/')); ?>">API Document</a>
                </div>
                <div class="col-12 col-lg-4 text-center text-md-right">
                    <?php echo esc_html(mlt_setting('footer_design', 'Design by MuaLikes.Net')); ?>
                </div>
            </div>
        </footer>
    </div>
    <div class="contact-button-group">
        <a role="button" class="contact-head" href="javascript:void(0)">
            <i class="fas fa-headphones-alt contact-head-icon"></i>
            <i class="fas fa-times contact-head-icon d-none"></i>
            <div class="contact-head-label">Hỗ trợ</div>
        </a>
        <?php foreach ((array) mlt_setting('support_links', []) as $l) : ?>
            <a role="button" class="contact-button messenger-button button-closed" href="<?php echo esc_url($l['url']); ?>" target="_blank" rel="nofollow">
                <img aria-hidden="true" class="button-icon" src="<?php echo esc_url(mlt_img($l['icon'])); ?>" style="max-width: 24px;" />
                <div class="contact-label"><?php echo esc_html($l['label']); ?></div>
            </a>
        <?php endforeach; ?>
    </div>
    <?php if (mlt_setting('google_translate', 1)) : ?>
    <div class="modal fade" id="modalLanguage" tabindex="-1" role="dialog" aria-labelledby="modalLanguageLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLanguageLabel">Chọn ngôn ngữ</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body text-center">
                    <div id="google_translate_element"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="modal fade" id="uidDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Chi tiết</h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
                <div class="modal-body"><pre id="uidDetailContent" style="white-space:pre-wrap;word-break:break-all"></pre></div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="orderDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Chi tiết đơn hàng</h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
                <div class="modal-body"></div>
            </div>
        </div>
    </div>
    <?php wp_footer(); ?>
</body>

</html>
