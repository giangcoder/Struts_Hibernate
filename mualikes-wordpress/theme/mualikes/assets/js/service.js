var price = 0;
var calculator_amount = function(quantity = 0) {
    let uid = $('textarea[name=post_link_multiple]').length ? $('textarea[name=post_link_multiple]').val().split("\n") : '';
    let numberOrder = 1;
    let isCustomUid = $('#isCustomUid').is(":checked");
    if (uid.length > 0 && !$('.multiple-orders').hasClass('d-none') && !isCustomUid) {
        numberOrder = uid.length;
    }
    if ($('input[name=number_seeding]').length >= 1) {
        var number_seeding = $('input[name=number_seeding]').val();
    } else {
        var number_seeding = $('select[name=number_seeding]').val();
    }
    if (!number_seeding) {
        number_seeding = $('input[name=count]').val();
    }
    if (quantity > 0) {
        number_seeding = quantity;
    }
    
    let isDripFeed = $('#isDripFeed').is(":checked");

    let total_order = 1;
    if (isDripFeed) {
        total_order = $('input[name=total_order]').val();
    }
    var  service_id = 1;
    if ($('select[name=service_id]').find('option:selected').data('multiple') > 0) {
        service_id = $('select[name=service_id]').find('option:selected').data('multiple');
    }
    const qty_order = parseInt(number_seeding);
    const time_live = parseInt($('select[name=time_to_live]').val());
    const total_day = parseInt($('select[name=total_day]').val());
    let total_amount = (qty_order * price) * numberOrder * total_order * service_id;
    if (time_live) total_amount = total_amount * time_live * numberOrder;
    if (total_day) total_amount = total_amount * total_day * numberOrder;
    $('input[name=price]').val(parseFloat(price));
    $('.price').text(parseFloat(price) + 'đ');
    $('#total').text(VND(total_amount));
    interval_order_total();
};

var formatNumber = function(number) {
    return new Intl.NumberFormat().format(number)
}
function isNumeric(value) {
    return /^-?\d+$/.test(value);
}

function calculator_multiple_order() {
    let uid = $('textarea[name=post_link_multiple]').val().split("\n");
    let isCustomUid = $('#isCustomUid').is(":checked");
    var flag = true;
    var total = 0;
    if (isCustomUid) {
        for (let i = 0; i < uid.length; i++) {
            let quantity = uid[i].split("|")[1];
            if (isNumeric(quantity)) {
                total += parseInt(quantity);
            } else {
                flag = false;
                break;
            }
        }
        if (flag) {
            $('#uid-error').remove();
            $('#btn-create').prop('disabled', false);
            calculator_amount(total);
        } else {
            // Append error label only once if it does not exist
            if (!$('#uid-error').length) {
                $('textarea[name=post_link_multiple]').after('<label id="uid-error" class="error" for="uid">Vui lòng nhập đúng fomat bên dưới!</label>');
                $('#btn-create').prop('disabled', true);
            }
        }
    } else {
        // Remove error label in case it was added earlier
        $('#uid-error').remove();
        $('#btn-create').prop('disabled', false);
        calculator_amount();
        
    }

    if (uid.length > 50) {
        if (!$('#uid-error').length) {
            $('textarea[name=post_link_multiple]').after('<label id="uid-error" class="error" for="uid">Vui lòng nhập tối đa 50 link!</label>');
            $('#btn-create').prop('disabled', true);
        }
    }
    if (uid.length <= 50 && flag) {
        $('#uid-error').remove();
        $('#btn-create').prop('disabled', false);
        calculator_amount(total);
    }

}

function interval_order_total() {
    if ($('input[name=number_seeding]').length >= 1) {
        var number_seeding = $('input[name=number_seeding]').val();
    } else {
        var number_seeding = $('select[name=number_seeding]').val();
    }
    if (!number_seeding) {
        number_seeding = $('input[name=count]').val();
    }
    var total_order = $('input[name=total_order]').val();
    var totalQuantity = parseInt(number_seeding) * parseInt(total_order);
    $('#interval_order_total').text( '(Tổng số lượng nhận được: ' + formatNumber(totalQuantity) + ')');
    $('#number').text(formatNumber(totalQuantity));
};

$(document).ready(function() {
    var flag = false;
    $(document).on('click', 'input[name=serverId]', function() {
        const server_item = $(this).closest('.server-item');
        if (server_item.hasClass('active')) {
            return;
        }
        if ($(this).val() == 572 || $(this).val() == 764 | $(this).val() == 765) {
            $('.comments').addClass('d-none');
        } else if ($(this).val() == 573) {
            $('.comments').removeClass('d-none');
        }
        var segment = $(location).attr('pathname').split('/').filter(Boolean).pop();
        if (segment == 'dich-vu-danh-gia-google-maps') {
            if ($(this).val() == 440) {
                $('.reviews').removeClass('d-none');
                $('.comments').addClass('d-none');
            } else {
                $('.reviews').addClass('d-none');
                $('.comments').removeClass('d-none');
            }
        }
        $('.server-item').removeClass('active');
        server_item.addClass('active');
        price = parseFloat($(this).data('price'));
        const min = $(this).data('min');
        const max = $(this).data('max');
        if ($("#comments").is(":visible")) {
            var number = $("#comments").val().split("\n").length;
            $("input[name=number_seeding]").val(number);
        }
        const multi_reactions = $(this).data('multi');
        const speed = $(this).data('speed');
        const reactions = $(this).data('reactions') || false;
        $('.isDripFeed').addClass('d-none');
        if ($(this).data('dripfeed') == 1) {
            $('.isDripFeed').removeClass('d-none');
        }
        $('.isVideoByTime').addClass('d-none');
        if ($(this).data('video-by-time') == 1) {
            $('.isVideoByTime').removeClass('d-none');
        }
        $('input[name=number_seeding]').attr({
            min: min,
            max: max
        }).val(min);
        $('#quantity-min').text(formatNumber(min));
        $('#quantity-max').text(formatNumber(max));
        $('.multi-reaction-container').slideUp(100);
        $('.reaction-container').slideUp(100);
        if (reactions) {
            const arr_reactions = reactions.split(',');
            const contai_reaction = $('.reactions');
            for (const container of contai_reaction) {
                const reactions = $(container).find('input');
                for (const item of reactions) {
                    const current_value = $(item).val();
                    (arr_reactions.includes(current_value) ? $(item).prop('disabled', false).closest('label').show() : $(item).prop('disabled', true).closest('label').hide());
                }
            }
            (multi_reactions ? $('.multi-reaction-container').slideDown(350) : $('.reaction-container').slideDown(350))
        }
        if (speed) $('.speed-order').slideDown(350);
        calculator_amount();
        $('.server-note').slideUp(150);
        var qty_order = parseInt($('input[name=number_seeding]').val());
        const total_order = parseInt($('input[name=total_order]').val());
        if ($('.isDripFeed').is(':visible')) {
            qty_order = qty_order * total_order;
        }
        $('#number').text(qty_order);
        const server_info = server_item.find('.server-note').text() || false;
        if (server_info != ' ') server_item.find('.server-note').slideDown(250);
        if (!$(".comments").is(":visible") || flag) {
            var comment =  $(this).data('comment');
            if (comment == 1) {
                $('.comments').removeClass('d-none');
                flag = true;
            } else {
                $('.comments').addClass('d-none');
            }
        }
    }).on('input', 'input[name=number_seeding], input[name=count]', function() {
        var qty_order = parseInt($(this).val());
        const min = parseInt($(this).data('min'));
        const max = parseInt($(this).data('max'));
        if (qty_order < min) {
            $(this).val(min);
        } else if (qty_order > max) {
            $(this).val(max);
        }
        const total_order = parseInt($('input[name=total_order]').val());
        if ($('.isDripFeed').is(':visible')) {
            qty_order = qty_order * total_order;
        }
        $('#number').text(numberFormat(qty_order));
        calculator_amount();
        
    }).on('input', 'textarea[name=comments], textarea[name=content]', function() {
        const comments = $(this).val().split("\n");
        const amount = comments.length;
        $('#amount').text(amount);
        $('#number').text(amount);
        const total_order = parseInt($('input[name=total_order]').val());
        if ($('.isDripFeed').is(':visible')) {
            amount = amount * total_order;
        }
        $('input[name=number_seeding]').val(amount);
        var segment = $(location).attr('pathname').split('/').filter(Boolean).pop();
        if (segment == 'dich-vu-danh-gia-google-maps') {
          $('input[name=count]').val(amount);
        }
        calculator_amount();
    }).on('change', 'select[name=time_to_live], select[name=number_seeding], select[name=total_day]', function() {
        calculator_amount();
    });
    $('#form-create').validate({
        rules: {
            'post_link': {
                required: true,
            },
            'fanpage_id': {
                required: true,
            },
            'server_id': {
                required: true,
            },
            'reaction': {
                required: true,
            },
            'reaction[]': {
                required: true,
            },
            'reactions[]': {
                required: true,
            },
            'speed': {
                required: true
            },
            '_speed': {
                required: true
            },
            'comments': {
                required: true,
            },
            'content': {
                required: true,
            },
            'reviews': {
                required: true,
                reviews: true,
            },
            'number_seeding': {
                required: true,
            },
            'time_to_live': {
                required: true
            },
            'post_per_day': {
                required: true
            },
            'total_day': {
                required: true
            },
            'total_post': {
                required: true,
                min: 7,
            },
            'price': {
                required: true,
                min: 50
            },
        },
        messages: {
            'post_link': {
                required: 'Vui lòng nhập trường này!',
            },
            'fanpage_id': {
                required: 'Vui lòng nhập trường này!',
            },
            'server_id': {
                required: 'Vui lòng chọn server!',
            },
            'reaction': {
                required: 'Vui lòng chọn cảm xúc!',
            },
            'reaction[]': {
                required: 'Vui lòng chọn cảm xúc!',
            },
            'reactions[]': {
                required: 'Vui lòng chọn cảm xúc!',
            },
            'speed': {
                required: 'Vui lòng chọn tốc độ!',
            },
            '_speed': {
                required: 'Vui lòng chọn tốc độ!',
            },
            'comments': {
                required: 'Vui lòng nhập bình luận!'
            },
            'content': {
                required: 'Vui lòng nhập chia sẻ!'
            },
            'reviews': {
                required: 'Vui lòng nhập đánh giá!'
            },
            'number_seeding': {
                required: 'Vui lòng nhập số lượng cần tăng!',
                min: 'Vui lòng nhập số lượng lớn hơn hoặc bằng {0}.',
                max: 'Vui lòng nhập số lượng nhỏ hơn hoặc bằng {0}.',
            },
            'time_to_live': {
                required: 'Vui lòng chọn thời gian duy trì mắt!',
            },
            'post_per_day': {
                required: 'Vui lòng nhập trường này!',
                min: 'Vui lòng nhập số lượng lớn hơn hoặc bằng {0}.',
                max: 'Vui lòng nhập số lượng nhỏ hơn hoặc bằng {0}.',
            },
            'total_day': {
                required: 'Vui lòng nhập trường này!',
                min: 'Vui lòng nhập số lượng lớn hơn hoặc bằng {0}.',
                max: 'Vui lòng nhập số lượng nhỏ hơn hoặc bằng {0}.',
            },
            'total_post': {
                required: 'Vui lòng nhập trường này!',
                min: 'Vui lòng nhập số lượng lớn hơn hoặc bằng {0}.',
            },
            'price': {
                required: 'Vui lòng nhập giá tiền mỗi tương tác!',
                min: 'Vui lòng nhập số lượng lớn hơn hoặc bằng {0}.'
            },
        },
        submitHandler: function() {
            if ($users.isLoggedIn) {
                Swal.fire({
                    title: 'Xác nhận',
                    html: `
                        <p>Bạn có chắc chắn muốn tạo đơn ? Khi bấm "Đồng ý" đồng nghĩa với việc bạn đã đọc hết thông tin dịch vụ của server!</p>
                        <div style="display:flex;justify-content: center;">
                            <input type="checkbox" checked id="agreeCheckbox" style="width: 17px; height: 20px; margin-right: 4px; margin-top: 1px; opacity: 1;
    position: relative;"/>
                            <label>
                                Chấp nhận <a href="/dieu-khoan-dich-vu">Điều khoản</a>
                            </label>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Đồng ý',
                    cancelButtonText: 'Không',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        const checkbox = document.getElementById('agreeCheckbox');
                        if (!checkbox.checked) {
                            Swal.showValidationMessage('Bạn cần đồng ý với điều khoản trước khi tiếp tục!');
                            return false;
                        }
                        var form = $('#form-create');
                        var formData = form.serialize();
                        return $.ajax({
                            type: 'POST',
                            url: '/server/graph-api?action=order-service',
                            data: formData,
                            dataType: 'json',
                            success: function(response) {
                                if (response.status) {
                                    Swal.fire({
                                        title: 'Thành công',
                                        html: 'Thông tin đơn hàng của bạn: <ul class="list-group list-group-flush">\
                                              <li class="list-group-item d-flex justify-content-between align-items-center">\
                                                ID đơn: <span>' + response.message.orderId + '</span>\
                                              </li>\
                                              <li class="list-group-item d-flex justify-content-between align-items-center">\
                                                Đơn giá: <span>' + response.message.price + '</span>\
                                              </li>\
                                              <li class="list-group-item d-flex justify-content-between align-items-center">\
                                                Tổng tiền: <span>' + numberFormat(response.message.amount) + '</span>\
                                              </li>\
                                              <li class="list-group-item d-flex justify-content-between align-items-center">\
                                                Trạng thái: <span>' + numberFormat(response.message.status) + '</span>\
                                              </li>\
                                              <li class="list-group-item d-flex justify-content-between align-items-center">\
                                                Thời gian : <span>' + numberFormat(response.message.created) + '</span>\
                                              </li>\
                                              <li class="list-group-item">\
                                                <a target="_blank" href="/auth/order-list">Xem lịch sử đơn hàng >></a>\
                                              </li>\
                                            </ul>',
                                        icon: 'success'
                                    });
                                } else Swal.fire('Thất bại', response.message, 'error').then(() => {
                                    if (response.message == 'Vui lòng tải lại trang để cập nhập giá') {
                                        location.reload();
                                    }
                                  });
                            },
                            error: function(xhr, status, error) {
                                var response = JSON.parse(xhr.responseText);
                                Swal.fire(
                                    'Có lỗi xảy ra',
                                    response.message,
                                    'error'
                                )
                            }
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
        }
    });
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
        if (post_link.match(/instagram\.com/)) {
            if (post_link.includes("profilecard")) {
                var result = post_link.split("profilecard")[0];
                $('#post_link').val(result);
            }
        }
        // if (post_link.match(/shp\.ee/) || post_link.match(/shope\.ee/)) {
        //     action = '/get-link-shopee';
        // }
        if (post_link.match(/facebook\.com\//)) {
            action = 'get-link-facebook';
            $('#originalLink').val(post_link);
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
    $('#service-tabs-list').on('click', function() {
        const loaded = parseInt($(this).attr('data-loaded'));
        if (loaded <= 0) {
            $(this).attr('data-loaded', 'true');
            var dataTable = initDataTable($('#dataTable'), columns, [
                [0, "desc"]
            ], ajaxData, 'order-list');
            $(document).on('submit', '.form-search', function(e) {
                e.preventDefault();
                dataTable.draw();
            });
        } else {
            $('#dataTable').DataTable().ajax.reload();
        }
    });
    $('#tab-payment-history').on('click', function() {
        const loaded = parseInt($(this).attr('data-loaded'));
        if (loaded <= 0) {
            $(this).attr('data-loaded', 'true');
            var dataTable = initDataTable($('#dataTable'), columns, [
                [0, "desc"]
            ], ajaxData, 'payment-history');
            $(document).on('submit', '.form-search', function(e) {
                e.preventDefault();
                dataTable.draw();
            });
        } else {
            $('#dataTable').DataTable().ajax.reload();
        }
    });
    $('.server-item:first').find('input[type=radio]').click();

    $('body').on('click', '.is-open', function () {
        $('.is-open').addClass('d-none');
        $('.toc_content').addClass('d-none');
        $('.is-close').addClass('d-block');
    })
    $('body').on('click', '.is-close', function () {
        $('.is-open').removeClass('d-none');
        $('.toc_content').removeClass('d-none');
        $('.is-close').removeClass('d-block');
    })

    $(".content h2").each(function(index, element) {
        var content = $(".toc");
        if (index == 0) {
            $(element).before(content);
        }
        
    });
    
    if ($('.nav-link.active').length > 0) {
        $('.nav-link.active').closest('.menu-item').addClass('menu-is-opening menu-open');
    }
    
    $('#page').on('change', function() {
        if (!$users.isLoggedIn) return;
        var _this = $(this),
            post_link = _this.val(),
            action;
        if (post_link.match(/facebook\.com\//)) {
            action = 'get-username';
            $('#originalLink').val(post_link);
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
    
    $(document).on("change", "#isMultipleOrders", function() {
        let isChecked = $(this).is(":checked");
        if(isChecked) {
            $('.multiple-orders').removeClass('d-none');
            $('.single-order').addClass('d-none');

        } else {
            $('.multiple-orders').addClass('d-none');
            $('.single-order').removeClass('d-none');
        }
        calculator_multiple_order();
    });

    $(document).on("change", "#isCustomUid", function() {
        let isChecked = $(this).is(":checked");
        if(isChecked) {
            $('.quantity-order').addClass('d-none');
            calculator_multiple_order();
        } else {
            $('.quantity-order').removeClass('d-none');
            calculator_multiple_order();
        }
    });

    $(document).on("input", "textarea[name=post_link_multiple]", function() {
        let uid = $(this).val().split("\n");
        $('#total_order').text(uid.length);
        calculator_multiple_order();
    });
    
    $('.total_order').on('change', function() {
        calculator_multiple_order();
    });
    
    $(document).on("change", "#isDripFeed", function() {
        let isChecked = $(this).is(":checked");
        if(isChecked) {
            $('.drip-feed-container').removeClass('d-none');
        } else {
            $('.drip-feed-container').addClass('d-none');
        }
        calculator_multiple_order();
        interval_order_total();
    });
    
    $(document).on("change", ".isVideoByTime", function() {
        calculator_amount();
    });
    
    $(document).on("input", "#owner_link", function() {
        let fullUrl = $(this).val().trim();
        if (fullUrl.includes('?')) {
            let cleanUrl = fullUrl.split('?')[0];
            setTimeout(() => {
                $(this).val(cleanUrl);
            }, 500);
        }
    });
});



//#link
async function getFbInfoFromUsername(username) {
  try {
    if (username.toString().match(/^\d+$/)) {
      return [true, {
        id: username,
        name: 'Tên khách hàng'
      }]
    }

    let action = "get-username";
    $("#link").prop("disabled", true).val("Đang lấy uid, vui lòng chờ!");
    const data = await $.post(
        "/server/graph-api?action=" + action,
        {
        post_link: username,
        }
    );
    if (data.status == true) return [true, data.message];
    return [false, data.message];
  } catch (e) {
    return [false, 'Lỗi mạng, vui lòng thử lại']
  }
}
async function getProfileInfo(input, silent = true) {
  let link;

  if (input.includes("facebook.com") || input.includes("fb.com")) {
    if (
      input.includes("profile.php") &&
      !(typeof type !== "undefined" && type === "review")
    ) {
      let match = input.match(/id=(\d+)/);
      if (match) {
        return [
          true,
          {
            id: match[1],
            name: "Tên khách hàng",
          },
        ];
      }
    } else {
      if (input.match(/groups\/(\d+)/)) {
        return [
          true,
          {
            id: input.match(/groups\/(\d+)/)[1],
            name: "Tên Nhóm",
          },
        ];
      }

      let username = "";
      if (input.includes("/posts/")) {
        username = explode_by("facebook.com/", "/posts/", input);
      } else if (input.includes("/videos/")) {
        username = explode_by("facebook.com/", "/videos/", input);
      } else {
        let regex = /(facebook|fb)\.com\/(.*)/gm;
        let m;
        while ((m = regex.exec(input)) !== null) {
          if (m.index === regex.lastIndex) {
            regex.lastIndex++;
          }
          m.forEach((match, groupIndex) => {
            if (groupIndex === 2) username = match;
          });
        }
      }
      if (!username) return [false, "Không nhận ra link"];

      return await getFbInfoFromUsername(username);
    }
  } else if (input.includes("instagram")) {
    if (!input.includes("/p/")) {
      link = explode_by("instagram.com/", "/", input);

      return [
        true,
        {
          id: link,
          name: "Tên khách hàng",
        },
      ];
    } else {
      return [false, "Không nhận ra link"];
    }
  }
}
$(document).on("change", "#link", function() {
    getProfileInfo($(this).val(), false).then(response => {
        let [success, message] = response;
        var id = message
        if (message.id) {
          id = message.id;
        }
        if (!success) {
            $("#link").prop("disabled", false).val('');
            toastr.error(message);
            return;
        }
        $("#link").prop("disabled", false).val('');
        $("#link").val(id);
        toastr.success("Cập nhật link thành công");

  });
});