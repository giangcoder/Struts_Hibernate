jQuery(document).ready(function($) {
    $(document).on('click', '.btn-resume', function() {
        var url = $(this).data('url');
        Swal.fire({
            title: 'Xác nhận',
            text: 'Bạn có chắc chắn muốn tiếp tục chạy lại đơn ?',
            icon: 'question',
            showCancelButton: true,
            cancelButtonColor: '#d33',
            confirmButtonText: 'Đồng ý',
            cancelButtonText: 'Không',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    type: 'POST',
                    url: url,
                    dataType: 'json',
                    success: function(response) {
                        Swal.fire(
                            response.success ? 'Thành công' : 'Thất bại',
                            response.message,
                            response.success ? 'success' : 'error'
                        );
                        $('#dataTable').DataTable().ajax.reload(null, false);
                    },
                    error: function(xhr, status, error) {
                        var response = JSON.parse(xhr.responseText);
                        Swal.fire(
                            'Có lỗi xảy ra',
                            response.message,
                            'error'
                        );
                        $('#dataTable').DataTable().ajax.reload(null, false);
                    }
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        });
    });

    $(document).on('click', '.btn-guarantee', function() {
        var orderId = $(this).data('order');
        Swal.fire({
            title: 'Xác nhận',
            html: 'Vui lòng kiểm tra thật kỹ đơn hàng trước khi bấm bảo hành. Nếu đơn hàng không đủ điều kiện bảo hành bị Từ chối quá <strong>' + maxGuaranteeCanceled + ' lần</strong> sẽ bị mất nút Tạo bảo hành. Bạn có chắc chắn muốn tạo bảo hành không ?',
            icon: 'warning',
            showCancelButton: true,
            cancelButtonColor: '#d33',
            confirmButtonText: 'Đồng ý',
            cancelButtonText: 'Không',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    type: 'POST',
                    url: '/server/graph-api?action=guarantee-order',
                    data: {
                        orderId
                    },
                    dataType: 'json',
                    success: function(response) {
                        Swal.fire(
                            response.status ? 'Thành công' : 'Thất bại',
                            response.message,
                            response.status ? 'success' : 'error'
                        );
                        $('#dataTable').DataTable().ajax.reload(null, false);
                    },
                    error: function(xhr, status, error) {
                        var response = JSON.parse(xhr.responseText);
                        Swal.fire(
                            'Có lỗi xảy ra',
                            response.message,
                            'error'
                        );
                        $('#dataTable').DataTable().ajax.reload(null, false);
                    }
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        });
    });

    $(document).on('click', '.btn-cancel', function() {
        var orderId = $(this).data('orders');
        Swal.fire({
            title: 'Bạn có chắc chắn hủy ?',
            html: `<h4>Các dịch vụ có thể hoàn tiền </h4>
            <p>Vip Like: v1, 2, 3, 4, 5</p>
            <p>Vip Comment: tất cả</p>
            <p>Vip Share: v1</p>
            <p>Vip Live: tất cả (v2 huỷ k hoàn tiền)</p>
            <p>Vip Instagram, Tiktok: tất cả</p>
            <p><span class="text-danger">Lưu ý: huỷ đơn trừ 7 ngày phí</span></p>`,
            icon: 'question',
            showCancelButton: true,
            cancelButtonColor: '#d33',
            confirmButtonText: 'Đồng ý',
            cancelButtonText: 'Không',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    type: 'POST',
                    url: '/server/graph-api?action=refund',
                    data: {
                        orderId
                    },
                    dataType: 'json',
                    success: function(response) {
                        Swal.fire(
                            response.success ? 'Thành công' : 'Thất bại',
                            response.message,
                            response.success ? 'success' : 'error'
                        );
                        $('#dataTable').DataTable().ajax.reload(null, false);
                    },
                    error: function(xhr, status, error) {
                        var response = JSON.parse(xhr.responseText);
                        Swal.fire(
                            'Có lỗi xảy ra',
                            response.message,
                            'error'
                        );
                        $('#dataTable').DataTable().ajax.reload(null, false);
                    }
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        });
    });

    $(document).on('click', '.btn-modal-server', function() {
        var url = $(this).data('url');
        $.get(url, function(data) {
            $('#modal-server-content').html(data);
            $('[data-toggle="tooltip"]').tooltip();
            $('body').css('position', '');
        });
    });

    $('#modalServer').on('hidden.bs.modal', function() {
        $('#modal-server-content').html('<div class="text-center p-3">\
          <div class="spinner-border" role="status">\
            <span class="sr-only">Loading...</span>\
          </div>\
        </div>');
    });
    
    $(document).on('click', '.order-multiple-detail', async function(e) {
        e.preventDefault();
        var orderId = $(this).data('orderid'); // ensure the button has data-orderid attribute
        var {
            status,
            message
        } = await $.post('/server/graph-api', {
            action : 'orders-multiple',
            orderId : orderId,
        });
        if (status) {
            $('#orderDetailModal .modal-body').html(message);
            $('#orderDetailModal').modal('show');
        }
    });

    $(document).on('click', '.popup-orders-multiple .page-link', async function(e) {
        e.preventDefault();
        var page = $(this).data('page');
        var href = $(this).attr('href');
        var orderId = href.split('orderId=')[1];
        orderId = orderId.slice(0, -1);
        var {
            status,
            message
        } = await $.post('/server/graph-api', {
            action : 'orders-multiple',
            orderId : orderId,
            page : page,
        });
        if (status) {
            $('#orderDetailModal .modal-body').html(message);
        }
    });

    $(document).on('click', '.copy', function(e) {
        e.preventDefault();
        var textToCopy = $(this).data('clipboard-text') || $(this).text().trim();
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textToCopy)
                .then(function() {
                })
                .catch(function() {
                });
        } else {
            var tempInput = $("<textarea>");
            $("body").append(tempInput);
            tempInput.val(textToCopy).select();
            document.execCommand("copy");
            tempInput.remove();
        }
    });
    
    $(document).on('click', '.copy', function(e) {
        e.preventDefault();
        var textToCopy = $(this).data('clipboard-text') || $(this).text().trim();
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textToCopy)
                .then(function() {
                })
                .catch(function() {
                });
        } else {
            var tempInput = $("<textarea>");
            $("body").append(tempInput);
            tempInput.val(textToCopy).select();
            document.execCommand("copy");
            tempInput.remove();
        }
    });
    
    $(document).on('click', '.see-uid', function() {
        var uid = $(this).data('id');
        document.getElementById('uidDetailContent').innerText = uid;
        $('#uidDetailModal').modal('show');
    });
    
    $(document).on('click', '.btn-renew', function() {
        var orderId = $(this).data('orders');
        var server = $(this).data('server');
        var days = $(this).data('days') || 30; // Default to 30 days if not specified
        Swal.fire({
            title: 'Nhập số ngày muốn gia hạn',
            html: `<input class="swal2-input" name="days" value="30" min="30" placeholder="Số ngày" type="number" style="max-width: 100% !important; display: flex;">`,
            icon: 'question',
            showCancelButton: true,
            cancelButtonColor: '#d33',
            confirmButtonText: 'Đồng ý',
            cancelButtonText: 'Không',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    type: 'POST',
                    url: '/server/graph-api?action=renew',
                    data: {
                        orderId,
                        days,
                        server
                    },
                    dataType: 'json',
                    success: function(response) {
                        Swal.fire(
                            response.status ? 'Thành công' : 'Thất bại',
                            response.message,
                            response.status ? 'success' : 'error'
                        );
                        $('#dataTable').DataTable().ajax.reload(null, false);
                    },
                    error: function(xhr, status, error) {
                        var response = JSON.parse(xhr.responseText);
                        Swal.fire(
                            'Có lỗi xảy ra',
                            response.message,
                            'error'
                        );
                        $('#dataTable').DataTable().ajax.reload(null, false);
                    }
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        });
    });
});