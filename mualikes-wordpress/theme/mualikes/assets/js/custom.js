var notification = function(args) {
    Swal.fire({
        title: args.title ? args.title : 'Thông báo hệ thống',
        html: args.html ? args.html : '',
        icon: args.icon ? args.icon : 'info',
    }).then(function() {
        if (args.reload) {
            window.location.reload();
        } else if (args.href) {
            window.location.href = args.href;
        }
    });
};
var VND = function(amount) {
    return amount.toLocaleString('vi-VN', {
        style: 'currency',
        currency: 'VND'
    });
};
var menu_active = function() {
    const nav_links = $('.nav-link');
    for (const nav of nav_links) {
        const url = $(nav).attr('href') || '';
        if (url == location.href) $(nav).addClass('active');

    }
}
$(document).ready(function() {
    menu_active();
    $('#form-register').submit(async function(e) {
        e.preventDefault();
        const form_data = $(this).serialize();
        const button = $(this).find('button[type=submit]');
        button.prop('disabled', true).text('Đang xử lý ....');
        const {
            status,
            message
        } = await $.post('/server/graph-api?action=register-account', form_data);
        if (status == false) {
            notification({
                title: "Opps",
                html: message,
                icon: 'warning'
            });
            button.prop('disabled', false).text('Đăng ký');
            return
        }
        notification({
            html: message,
            icon: 'success',
            reload: true
        });
    });
    $('#form-forgot-password').submit(async function(e) {
        e.preventDefault();
        const form_data = $(this).serialize();
        const button = $(this).find('button[type=submit]');
        button.prop('disabled', true).text('Đang xử lý ....');
        const {
            status,
            message
        } = await $.post('/server/graph-api?action=forgot-password', form_data);
        if (status == false) {
            notification({
                title: "Opps",
                html: message,
                icon: 'warning'
            });
            button.prop('disabled', false).text('Đăng ký');
            return
        }
        notification({
            html: message,
            icon: 'success',
            reload: true
        });
    });
    $('#form-login-account').submit(async function(e) {
        e.preventDefault();
        const form_data = $(this).serialize();
        const button = $(this).find('button[type=submit]');
        button.prop('disabled', true).text('Đang xử lý ....');
        const {
            status,
            message
        } = await $.post('/server/graph-api?action=form-login-account', form_data);
        if (status == false) {
            notification({
                title: "Opps",
                html: message,
                icon: 'warning'
            });
            button.prop('disabled', false).text('Đăng nhập');
            return
        }
        notification({
            html: message,
            icon: 'success',
            reload: true
        });
    });
    $('#form-token').submit(async function(e) {
        e.preventDefault();
        const form_data = $(this).serialize();
        const button = $(this).find('button[type=submit]');
        button.prop('disabled', true);
        const {
            status,
            message
        } = await $.post('/server/graph-api?action=change-api-token', form_data);
        if (status == false) {
            notification({
                title: "Opps",
                html: message,
                icon: 'warning'
            });
            button.prop('disabled', false);
            return
        }
        $('#api-token').val(message);
    });
    $('#form-change-password').submit(async function(e) {
        e.preventDefault();
        const form_data = $(this).serialize();
        const button = $(this).find('button[type=submit]');
        button.prop('disabled', true);
        const {
            status,
            message
        } = await $.post('/server/graph-api?action=change-password', form_data);
        if (status == false) {
            notification({
                title: "Opps",
                html: message,
                icon: 'warning'
            });
            button.prop('disabled', false);
            return
        }
        notification({
            html: message,
            icon: 'success',
            reload: true
        });
    });
    $('form[server-send]').submit(async function(e) {
        e.preventDefault();
        const form_data = $(this).serialize();
        const action = $(this).attr('action');
        const button = $(this).find('button[type=submit]');
        const current_button = button.html();
        button.prop('disabled', true).html('Đang xử lý ...');
        const {
            status,
            message
        } = await $.post('/server/graph-api?action=' + action, form_data);
        button.prop('disabled', false).html(current_button);
        if (status == false) {
            notification({
                title: "Opps",
                html: message,
                icon: 'warning'
            });
            return
        }
        if (action == 'payment-cryptomus') {
            window.open(message, '_blank').focus();
            return;
        }
        notification({
            html: message,
            icon: 'success',
            reload: true
        });
    });
    $('.active-affiliate').click(async function() {
        $(this).prop('disabled', true);
        const {
            status,
            message
        } = await $.post('/server/graph-api?action=active-affiliate');
        if (status == false) {
            notification({
                title: "Opps",
                html: message,
                icon: 'warning'
            });
            $(this).prop('disabled', false);
            return
        }
        notification({
            html: message,
            icon: 'success',
            reload: true
        });
    });
    $('.scroll-docs').click(function(event) {
        event.preventDefault(); // Ngăn chặn hành động mặc định của link
        const scroll_ele = $(this).attr('href');
        const target_offset = $(scroll_ele).offset().top;
        const window_height = $(window).height();
        const target_height = $(scroll_ele).outerHeight();
        const scroll_position = target_offset - (window_height / 2) + (target_height / 2);
        $('html, body').animate({
            scrollTop: scroll_position
        }, 1000);
    });
    
    $('#form-add-domain').submit(async function(e) {
        e.preventDefault();
        const form_data = $(this).serialize();
        const button = $(this).find('button[type=submit]');
        button.prop('disabled', true);
        const {
            status,
            message
        } = await $.post('/server/graph-api?action=form-add-domain', form_data);
        if (status == false) {
            notification({
                title: "Opps",
                html: message,
                icon: 'warning'
            });
            button.prop('disabled', false);
            return
        }
        notification({
            html: message,
            icon: 'success',
            reload: true
        });
    });
});