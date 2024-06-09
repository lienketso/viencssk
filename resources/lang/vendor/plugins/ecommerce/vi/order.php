<?php

return [
    'statuses' => [
        'pending' => 'Chưa xử lý',
        'processing' => 'Đang xử lý',
        'completed' => 'Hoàn thành',
        'canceled' => 'Bị huỷ',
    ],
    'name' => 'Đơn hàng',
    'incomplete_order' => 'Đơn hàng chưa hoàn tất',
    'order_id' => 'Mã đơn hàng',
    'customer_label' => 'Khách hàng',
    'amount' => 'Tổng cộng',
    'shipping_amount' => 'Phí vận chuyển',
    'payment_method' => 'Phương thức thanh toán',
    'payment_status_label' => 'Trạng thái thanh toán',
    'manage_orders' => 'Quản lý đơn hàng',
    'order_intro_description' => 'Một khi cửa hàng của bạn có đơn đặt hàng, đây sẽ là nơi bạn xử lý và theo dõi những đơn đặt hàng đó.',
    'create_new_order' => 'Tạo đơn hàng',
    'manage_incomplete_orders' => 'Quản lý đơn hàng chưa hoàn tất',
    'incomplete_orders_intro_description' => 'Đơn hàng chưa hoàn tất là đơn hàng được tạo khi khách hàng thêm sản phẩm vào giỏ hàng, tiến hành điền thông tin mua hàng nhưng không hoàn tất quá trình thanh toán.',
    'cannot_send_order_recover_to_mail' => 'Không tìm thấy email nên không thể gửi email khôi phục cho khách hàng.',
    'menu' => 'Đơn hàng',
    'order_information' => 'Thông tin đơn hàng',
    'create' => 'Tạo đơn hàng',
    'cancel_error' => 'Đơn hàng đang được giao hoặc đã hoàn thành',
    'cancel_success' => 'Bạn hủy đơn hàng thành công',
    'product_id' => 'Mã sản phẩm',
    'tax_amount' => 'Số tiền thuế',
    'invoice_for_order' => 'Hóa đơn cho đơn hàng',
    'created' => 'Đã tạo',
    'invoice' => 'Hóa đơn',
    'return' => 'Return',
    'total_refund_amount' => 'Tổng số tiền hoàn lại',
    'total_amount_can_be_refunded' => 'Tổng số tiền có thể được hoàn lại',
    'refund_reason' => 'Lý do hoàn tiền (tùy chọn)',
    'products' => 'product(s)',
    'default' => 'Default',
    'system' => 'Hệ thống',
    'new_order_from' => 'Đơn hàng mới :order_id từ :customer',
    'confirmation_email_was_sent_to_customer' => 'Email xác nhận đã được gửi tới khách hàng',
    'address_name_required' => 'Trường tên là bắt buộc.',
    'address_phone_required' => 'The phone field is required.',
    'address_email_required' => 'The email field is required.',
    'address_email_unique' => 'Khách hàng có email đó đã tồn tại, vui lòng chọn email khác hoặc đăng nhập bằng email này!',
    'address_state_required' => 'Trường Quận Huyện là bắt buộc.',
    'address_city_required' => 'Trường Thành phố bắt buộc nhập.',
    'address_country_required' => 'Trường Quốc gia bắt buộc nhập.',
    'address_address_required' => 'Trường địa chỉ là bắt buộc.',
    'create_order_from_payment_page' => 'Đơn hàng đã được tạo từ trang thanh toán',
    'order_was_verified_by' => 'Đơn hàng đã được xác minh bởi %user_name%',
    'new_order' => 'Đơn hàng mới :order_id',
    'payment_was_confirmed_by' => 'Thanh toán đã được xác nhận (số tiền :money) bởi %user_name%',
    'edit_order' => 'Sửa đơn hàng :code',
    'confirm_order_success' => 'Xác nhận đơn hàng hành công!',
    'error_when_sending_email' => 'Có lỗi khi gửi email',
    'sent_confirmation_email_success' => 'Đã gửi email xác nhận thành công!',
    'order_was_sent_to_shipping_team' => 'Đơn hàng đã được gửi đến đơn vị vận chuyển',
    'by_username' => 'bởi %user_name%',
    'shipping_was_created_from' => 'Vận chuyển được tạo từ đơn đặt hàng %order_id%',
    'shipping_was_canceled_success' => 'Vận chuyển đã được hủy thành công!',
    'shipping_was_canceled_by' => 'Vận chuyển đã bị hủy bởi %user_name%',
    'update_shipping_address_success' => 'Cập nhật địa chỉ giao hàng thành công!',
    'order_was_canceled_by' => 'Đơn hàng đã bị hủy bởi %user_name%',
    'confirm_payment_success' => 'Xác nhận thanh toán thành công!',
    'refund_amount_invalid' => 'Số tiền hoàn lại phải thấp hơn hoặc bằng :price',
    'number_of_products_invalid' => 'Số lượng sản phẩm hoàn trả không hợp lệ!',
    'cannot_found_payment_for_this_order' => 'Không thể tìm thấy thanh toán cho đơn hàng này!',
    'refund_success_with_price' => 'Hoàn tiền thành công :price',
    'refund_success' => 'Hoàn tiền thành công!',
    'order_is_not_existed' => 'Đơn hàng không tồn tại!',
    'reorder' => 'Đặt hàng lại',
    'sent_email_incomplete_order_success' => 'Đã gửi email nhắc nhở đơn hàng chưa hoàn thành thành công!',
    'applied_coupon_success' => 'Phiếu giảm giá áp dụng ":code" thành công!',
    'new_order_notice' => 'Bạn có <span class="bold">:count</span> Đơn hàng mới(s)',
    'view_all' => 'Xem tất cả',
    'cancel_order' => 'Hủy đơn hàng',
    'order_canceled' => 'Đơn hàng đã hủy',
    'order_was_canceled_at' => 'Đơn hàng đã bị hủy lúc',
    'completed' => 'Đã hoàn thành',
    'uncompleted' => 'Chưa hoàn thành',
    'sku' => 'SKU',
    'warehouse' => 'Kho',
    'sub_amount' => 'Tổng cộng',
    'coupon_code' => 'Mã giảm giá: ":code"',
    'shipping_fee' => 'Phí vận chuyển',
    'tax' => 'Thuế',
    'refunded_amount' => 'Số tiền được hoàn lại',
    'amount_received' => 'Số tiền thực tế nhận được',
    'download_invoice' => 'Tải hóa đơn',
    'add_note' => 'Thêm lưu ý...',
    'order_was_confirmed' => 'Đơn hàng đã được xác nhận',
    'confirm_order' => 'Xác nhận đơn hàng',
    'confirm' => 'Xác nhận',
    'order_was_canceled' => 'Đơn hàng đã bị hủy',
    'pending_payment' => 'Chờ thanh toán',
    'payment_was_accepted' => 'Thanh toán :money được chấp nhận',
    'payment_was_refunded' => 'Thanh toán đã được hoàn lại',
    'confirm_payment' => 'Xác nhận thanh toán',
    'refund' => 'Hoàn tiền',
    'all_products_are_not_delivered' => 'Tất cả các sản phẩm không được giao',
    'delivery' => 'Giao hàng',
    'history' => 'Lịch sử',
    'order_number' => 'Số đơn hàng',
    'from' => 'từ',
    'status' => 'Trạng thái',
    'successfully' => 'Thành công',
    'transaction_type' => 'Loại giao dịch',
    'staff' => 'Nhân viên',
    'refund_date' => 'Ngày hoàn lại tiền',
    'n_a' => 'N\\A',
    'payment_date' => 'Ngày thanh toán',
    'payment_gateway' => 'Cổng thanh toán',
    'transaction_amount' => 'Số tiền giao dịch',
    'resend' => 'Gửi lại',
    'default_store' => 'Cửa hàng mặc định',
    'update_address' => 'Cập nhật địa chỉ',
    'have_an_account_already' => 'Đã có tài khoản rồi',
    'dont_have_an_account_yet' => 'Vẫn chưa có tài khoản',
    'mark_payment_as_confirmed' => 'Đánh dấu <span>:method</span> là đã xác nhận',
    'resend_order_confirmation' => 'Gửi lại xác nhận đơn hàng',
    'resend_order_confirmation_description' => 'Email xác nhận sẽ được gửi tới <strong>:email</strong>?',
    'send' => 'Gửi',
    'update' => 'Cập nhật',
    'cancel_shipping_confirmation' => 'Hủy xác nhận vận chuyển?',
    'cancel_shipping_confirmation_description' => 'Hủy xác nhận vận chuyển?',
    'cancel_order_confirmation' => 'Hủy xác nhận đơn hàng?',
    'cancel_order_confirmation_description' => 'Bạn có chắc chắn muốn hủy đơn hàng này không? Hành động này không thể quay lại',
    'confirm_payment_confirmation_description' => 'Xử lý bởi <strong>:method</strong>. Bạn có nhận được thanh toán ngoài hệ thống không? Khoản thanh toán này sẽ không được lưu vào hệ thống và không thể hoàn lại',
    'save_note' => 'Lưu ghi chú',
    'order_note' => 'Ghi chú đơn hàng',
    'order_note_placeholder' => 'Lưu ý về đơn hàng, ví dụ: thời gian hoặc hướng dẫn vận chuyển.',
    'order_amount' => 'Số tiền đơn hàng',
    'additional_information' => 'Thông tin thêm',
    'notice_about_incomplete_order' => 'Thông báo về đơn hàng chưa đầy đủ',
    'notice_about_incomplete_order_description' => 'Email nhắc nhở về đơn hàng chưa hoàn thành sẽ được gửi tới <strong>:email</strong>?',
    'incomplete_order_description_1' => 'Đơn đặt hàng chưa hoàn tất là khi khách hàng tiềm năng đặt các mặt hàng vào giỏ hàng của họ và đi đến trang thanh toán nhưng sau đó không hoàn tất giao dịch.',
    'incomplete_order_description_2' => 'Nếu bạn đã liên hệ với khách hàng và họ muốn tiếp tục mua hàng, bạn có thể giúp họ hoàn tất đơn hàng theo link:',
    'send_an_email_to_recover_this_order' => 'Gửi email cho khách hàng để khôi phục đơn hàng này',
    'see_maps' => 'Xem bản đồ',
    'one_or_more_products_dont_have_enough_quantity' => 'Một hoặc nhiều sản phẩm không đủ số lượng!',
    'payment_info' => 'Thông tin thanh toán',
    'payment_method_refund_automatic' => 'Khách hàng của bạn được hoàn tiền tự động bằng phương thức :method .',
    'order' => 'Đơn hàng',
    'create_a_new_product' => 'Thêm sản phẩm mới',
    'out_of_stock' => 'Hết hàng',
    'products_available' => 'Các sản phẩm có sẵn',
    'no_products_found' => 'Không tìm thấy sản phẩm nào!',
    'note' => 'Ghi chú',
    'note_for_order' => 'Ghi chú cho đơn hàng...',
    'add_discount' => 'Thêm giảm giá',
    'discount' => 'Giảm giá',
    'add_shipping_fee' => 'Thêm phí vận chuyển',
    'shipping' => 'Vận chuyển',
    'total_amount' => 'Tổng thanh toán',
    'confirm_payment_and_create_order' => 'Xác nhận thanh toán và tạo đơn hàng',
    'paid' => 'Thanh toán ngay',
    'pay_later' => 'Trả tiền sau',
    'customer_information' => 'Thông tin khách hàng',
    'create_new_customer' => 'Tạo khách hàng mới',
    'no_customer_found' => 'Không tìm thấy khách hàng!',
    'customer' => 'Khách hàng',
    'orders' => 'đơn hàng(s)',
    'shipping_address' => 'Địa chỉ giao hàng',
    'see_on_maps' => 'Tìm trên bản đồ',
    'price' => 'Giá',
    'sku_optional' => 'SKU (optional)',
    'with_storehouse_management' => 'Với quản lý kho?',
    'quantity' => 'Số lượng',
    'allow_customer_checkout_when_this_product_out_of_stock' => 'Cho phép khách hàng thanh toán khi sản phẩm này hết hàng?',
    'address' => 'Địa chỉ',
    'phone' => 'Số điện thoại',
    'country' => 'Quốc gia',
    'state' => 'Quận / Huyện',
    'city' => 'Thành phố',
    'zip_code' => 'Zip code',
    'discount_based_on' => 'Giảm giá dựa trên',
    'or_coupon_code' => 'Hoặc mã giảm giá',
    'description' => 'Mô tả',
    'how_to_select_configured_shipping' => 'Cách lựa chọn cấu hình vận chuyển?',
    'please_add_customer_information_with_the_complete_shipping_address_to_see_the_configured_shipping_rates' => 'Vui lòng thêm thông tin khách hàng cùng địa chỉ giao hàng đầy đủ để xem mức phí vận chuyển được định cấu hình',
    'free_shipping' => 'Miễn phí vận chuyển',
    'custom' => 'Custom',
    'email' => 'Email',
    'create_order' => 'Tạo đơn hàng',
    'close' => 'Close',
    'confirm_payment_is_paid_for_this_order' => 'Xác nhận thanh toán được thanh toán cho đơn hàng này',
    'payment_status_of_the_order_is_paid_once_the_order_has_been_created_you_cannot_change_the_payment_method_or_status' => 'Trạng thái thanh toán của đơn hàng là Đã thanh toán. Khi đơn hàng đã được tạo, bạn không thể thay đổi phương thức hoặc trạng thái thanh toán',
    'select_payment_method' => 'Chọn phương thức thanh toán',
    'cash_on_delivery_cod' => 'Thanh toán khi giao hàng (COD)',
    'bank_transfer' => 'Chuyển khoản',
    'paid_amount' => 'Số tiền thanh toán',
    'confirm_that_payment_for_this_order_will_be_paid_later' => 'Xác nhận rằng khoản thanh toán cho đơn hàng này sẽ được thanh toán sau',
    'payment_status_of_the_order_is_pending_once_the_order_has_been_created_you_cannot_change_the_payment_method_or_status' => 'Trạng thái thanh toán của đơn hàng là Đang chờ xử lý. Khi đơn hàng đã được tạo, bạn không thể thay đổi phương thức hoặc trạng thái thanh toán',
    'pending_amount' => 'Số tiền đang chờ xử lý',
    'update_email' => 'Update email',
    'save' => 'Save',
    'cancel' => 'Cancel',
    'create_a_new_order' => 'Tạo đơn hàng mới',
    'search_or_create_new_product' => 'Tìm kiếm hoặc tạo một sản phẩm mới',
    'search_or_create_new_customer' => 'Tìm kiếm hoặc tạo khách hàng mới',
    'discount_description' => 'Mô tả giảm giá',
    'cant_select_out_of_stock_product' => 'Không thể chọn sản phẩm hết hàng!',
];