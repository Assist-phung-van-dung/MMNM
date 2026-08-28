/**
 * Thêm/bớt câu hỏi và đáp án cho meta box "Nghi Quỹ — Quyền xem & Bộ câu hỏi".
 *
 * PHP dựng sẵn hai <template>: một khối câu hỏi và một dòng đáp án, trong đó
 * chỉ số câu hỏi để là __i__ và chỉ số đáp án để là __j__. JS chỉ việc nhân bản
 * rồi thay hai chỗ giữ chỗ đó — không dựng HTML bằng chuỗi ở đây, để markup chỉ
 * có một nguồn duy nhất là PHP.
 */
( function () {
	'use strict';

	var root = document.querySelector( '[data-nntm-quiz-admin]' );

	if ( ! root ) {
		return;
	}

	var list = root.querySelector( '[data-nntm-quiz-list]' );
	var questionTemplate = root.querySelector( '[data-nntm-quiz-question-template]' );
	var answerTemplate = root.querySelector( '[data-nntm-quiz-answer-template]' );

	if ( ! list || ! questionTemplate || ! answerTemplate ) {
		return;
	}

	/** Thay chỗ giữ chỗ trong thuộc tính name của mọi ô nhập bên trong. */
	function apDatChiSo( scope, cauIndex, dapIndex ) {
		var fields = scope.querySelectorAll( '[name]' );

		for ( var i = 0; i < fields.length; i++ ) {
			var name = fields[ i ].getAttribute( 'name' );

			if ( null !== cauIndex ) {
				name = name.replace( '__i__', String( cauIndex ) );
			}
			if ( null !== dapIndex ) {
				name = name.replace( '__j__', String( dapIndex ) );
			}

			fields[ i ].setAttribute( 'name', name );
		}

		if ( null !== dapIndex ) {
			var radio = scope.querySelector( '[data-nntm-quiz-field="dung"]' );
			if ( radio && '__j__' === radio.value ) {
				radio.value = String( dapIndex );
			}
		}
	}

	/** Chỉ số câu hỏi kế tiếp — luôn lớn hơn mọi chỉ số đang có. */
	function chiSoCauMoi() {
		var cac = list.querySelectorAll( '[data-nntm-quiz-question]' );
		var max = -1;

		for ( var i = 0; i < cac.length; i++ ) {
			var n = parseInt( cac[ i ].getAttribute( 'data-index' ), 10 );
			if ( ! isNaN( n ) && n > max ) {
				max = n;
			}
		}

		return max + 1;
	}

	/** Chỉ số đáp án kế tiếp trong một câu hỏi. */
	function chiSoDapMoi( question ) {
		var cac = question.querySelectorAll( '[data-nntm-quiz-answer] [data-nntm-quiz-field="dung"]' );
		var max = -1;

		for ( var i = 0; i < cac.length; i++ ) {
			var n = parseInt( cac[ i ].value, 10 );
			if ( ! isNaN( n ) && n > max ) {
				max = n;
			}
		}

		return max + 1;
	}

	function themCauHoi() {
		var index = chiSoCauMoi();
		var node = questionTemplate.content.cloneNode( true );
		var question = node.querySelector( '[data-nntm-quiz-question]' );

		question.setAttribute( 'data-index', String( index ) );
		apDatChiSo( question, index, null );

		list.appendChild( node );
	}

	function themDapAn( question ) {
		var cauIndex = question.getAttribute( 'data-index' );
		var dapIndex = chiSoDapMoi( question );
		var node = answerTemplate.content.cloneNode( true );
		var row = node.querySelector( '[data-nntm-quiz-answer]' );

		apDatChiSo( row, cauIndex, dapIndex );

		question.querySelector( '[data-nntm-quiz-answers]' ).appendChild( node );
	}

	root.addEventListener( 'click', function ( event ) {
		var target = event.target;

		if ( target.closest( '[data-nntm-quiz-add-question]' ) ) {
			event.preventDefault();
			themCauHoi();
			return;
		}

		var addAnswer = target.closest( '[data-nntm-quiz-add-answer]' );
		if ( addAnswer ) {
			event.preventDefault();
			themDapAn( addAnswer.closest( '[data-nntm-quiz-question]' ) );
			return;
		}

		var removeAnswer = target.closest( '[data-nntm-quiz-remove-answer]' );
		if ( removeAnswer ) {
			event.preventDefault();
			var question = removeAnswer.closest( '[data-nntm-quiz-question]' );
			var rows = question.querySelectorAll( '[data-nntm-quiz-answer]' );

			/* Giữ tối thiểu hai dòng để câu hỏi còn hợp lệ. */
			if ( rows.length <= 2 ) {
				return;
			}

			removeAnswer.closest( '[data-nntm-quiz-answer]' ).remove();
			return;
		}

		var removeQuestion = target.closest( '[data-nntm-quiz-remove-question]' );
		if ( removeQuestion ) {
			event.preventDefault();
			removeQuestion.closest( '[data-nntm-quiz-question]' ).remove();
		}
	} );
} )();
