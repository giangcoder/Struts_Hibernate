$(document).ready(function() {
    $('#post_link').on('change', function() {
        if (!$users.isLoggedIn) return;
        var _this = $(this),
            post_link = _this.val(),
            action;

        if (post_link.indexOf('http') > 0) {
            var index = post_link.indexOf('http');
            post_link = post_link.substr(index);
            _this.val(post_link);
        }

        if (post_link.match(/tiktok\.com/)) {
            if (post_link.match(/tiktok\.com\/([^\/]+)\/video\/([0-9]+)/)) {
                var qMatch = post_link.match(/\?(.*)/);
                if (qMatch) {
                    _this.val(post_link.replace('?' + qMatch[1], ''));
                }
                return;
            } else if (post_link.match(/tiktok\.com\/@([\w\-._]+)/)) {
                var qMatch = post_link.match(/\?(.*)/);
                if (qMatch) {
                    _this.val(post_link.replace('?' + qMatch[1], ''));
                }
                return;
            }
            // else {
            //     action = '/get-link-tiktok';
            // }
        }
        // if (post_link.match(/shp\.ee/) || post_link.match(/shope\.ee/)) {
        //     action = '/get-link-shopee';
        // }
        if (post_link.match(/facebook\.com\//)) {
            action = 'get-link-facebook';
        }
        if (!action) return;

        setTimeout(async function() {
            _this.prop('disabled', true).val('Đang lấy uid, vui lòng chờ!');
            $('#btn-create').prop('disabled', true);
            const {
                status,
                message
            } = await $.post('/server/graph-api?action=' + action, {
                post_link
            });
            $('#btn-create').prop('disabled', false);
            if (status == false) {
                _this.prop('disabled', false).val(post_link);
                toastr.error(message);
                return
            }
            toastr.success('Lấy uid thành công');
            _this.prop('disabled', false).val(message);
        }, 10);
    });
    $(document).on('click', 'input[name=serverId]', function() {
        const server_item = $(this).closest('.server-item');
        if (server_item.hasClass('active')) {
            return;
        }
        $('.server-item').removeClass('active');
        server_item.addClass('active');
        price = parseFloat($(this).data('price'));
        $('.server-note').slideUp(150);
        selfCalculatorPrice();
        const server_info = server_item.find('.server-note').text() || false;
        if (server_info != '') server_item.find('.server-note').slideDown(250);
        const max_post = parseInt($(this).data('max-post'));
        
        if (max_post == 1) {
            $('.max_post').removeClass('d-none');
            $('.select-max_post').prop("disabled", false);
        } else {
            $('.max_post').addClass('d-none');
            $('.select-max_post').prop("disabled", true);
        }
    }).on('submit', 'form[creater-orders]', async function(e) {
        e.preventDefault();
        var _this = $(this);
        if ($users.isLoggedIn) {
            Swal.fire({
                title: 'Xác nhận',
                text: 'Bạn có chắc chắn muốn tạo đơn ? Khi bấm "Đồng ý" đồng nghĩa với việc bạn đã đọc hết thông tin dịch vụ của server!',
                icon: 'question',
                showCancelButton: true,
                cancelButtonColor: '#d33',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Không',
                showLoaderOnConfirm: true,
                preConfirm: async() => {
                    const form_data = _this.serialize();
                    const action = _this.attr('action');
                    const button = _this.find('button[type=submit]');
                    const current_button = button.html();
                    button.prop('disabled', true).html('Đang xử lý ...');
                    const {
                        status,
                        message
                    } = await $.post('/server/orders?action=' + action, form_data);
                    button.prop('disabled', false).html(current_button);
                    if (status == false) {
                        notification({
                            title: "Opps",
                            html: message,
                            icon: 'warning'
                        });
                        return
                    }
                    Swal.fire({
                        title: 'Thành công',
                        html: 'Thông tin đơn hàng của bạn: <ul class="list-group list-group-flush">\
                          <li class="list-group-item d-flex justify-content-between align-items-center">\
                            ID đơn: <span>' + message.orderId + '</span>\
                          </li>\
                          <li class="list-group-item d-flex justify-content-between align-items-center">\
                            Đơn giá: <span>' + message.price + '</span>\
                          </li>\
                          <li class="list-group-item d-flex justify-content-between align-items-center">\
                            Tổng tiền: <span>' + numberFormat(message.amount) + '</span>\
                          </li>\
                          <li class="list-group-item d-flex justify-content-between align-items-center">\
                            Trạng thái: <span>' + numberFormat(message.status) + '</span>\
                          </li>\
                          <li class="list-group-item d-flex justify-content-between align-items-center">\
                            Thời gian : <span>' + numberFormat(message.created) + '</span>\
                          </li>\
                        </ul>',
                        icon: 'success'
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            });
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Thất bại',
                showDenyButton: true,
                denyButtonColor: '#d33',
                focusDeny: false,
                html: 'Vui lòng <a href="/auth/login">đăng nhập</a> hoặc <a href="/auth/register">đăng ký</a><br> để sử dụng dịch vụ!',
                confirmButtonText: '<a href="/auth/login" class="text-white">Đăng nhập</a>',
                denyButtonText: '<a href="/auth/register" class="text-white">Đăng ký</a>',
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/auth/login';
                } else if (result.isDenied) {
                    window.location.href = '/auth/register';
                }
            })
        }
    });
    $('#service-tabs-list').on('click', function() {
        const loaded = parseInt($(this).attr('data-loaded'));
        if (loaded <= 0) {
            $(this).attr('data-loaded', 'true');
            var dataTable = initDataTable($('#dataTable'), columns, [
                [0, "desc"]
            ], ajaxData, (typeof action_table !== 'undefined' ? action_table : 'order-list-vip'));
            $(document).on('submit', '.form-search', function(e) {
                e.preventDefault();
                dataTable.draw();
            });
        } else {
            $('#dataTable').DataTable().ajax.reload();
        }
    });
    $('.server-item:first').find('input[type=radio]').click();
    
    if ($('.nav-link.active').length > 0) {
        $('.nav-link.active').closest('.menu-item').addClass('menu-is-opening menu-open');
    }
});