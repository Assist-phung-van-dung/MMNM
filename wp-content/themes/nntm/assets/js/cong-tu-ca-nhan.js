/**
 * Trang "Cộng tu của tôi": khai báo / cam kết xong trong modal (cong-tu-modal.js)
 * thì tải lại trang để mọi con số, biểu đồ, nhật ký được tính lại từ máy chủ —
 * không vá từng con số bằng JS rồi để chúng lệch nhau.
 */
( function () {
	'use strict';

	document.addEventListener( 'nntm-congtu:da-ghi', function () {
		window.location.reload();
	} );
}() );
