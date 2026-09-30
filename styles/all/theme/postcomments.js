/**
 * phpBB Post Comments Extension - Modern Interactive AJAX Engine
 *
 * @copyright (c) 2026 Meis@M <info@phpbb-seo.ir>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		var cfg = window.postcomments_cfg || {};

		// Helper to render comment HTML dynamically for newly posted or loaded comments
		function renderCommentHtml(c) {
			var editBtn = c.can_edit ? '<button type="button" class="postcomment-action-btn postcomment-btn-edit" title="' + (cfg.lang.edit || '') + '"><i class="icon fa-pencil fa-fw" aria-hidden="true"></i></button>' : '';
			var delBtn  = c.can_delete ? '<button type="button" class="postcomment-action-btn postcomment-btn-delete" title="' + (cfg.lang.delete || '') + '"><i class="icon fa-trash-o fa-fw" aria-hidden="true"></i></button>' : '';
			var actions = (editBtn || delBtn) ? '<div class="postcomment-actions">' + editBtn + delBtn + '</div>' : '';
			var editedTag = c.is_edited ? '<span class="postcomment-edited-tag" title="' + (cfg.lang.edited || '') + '">(' + (cfg.lang.edited || '') + ')</span>' : '';

			return '<div class="postcomment-item" id="postcomment-' + c.comment_id + '" data-comment-id="' + c.comment_id + '">' +
				'<div class="postcomment-avatar">' + c.avatar + '</div>' +
				'<div class="postcomment-main">' +
					'<div class="postcomment-header">' +
						'<span class="postcomment-author">' + c.username_full + '</span>' +
						'<span class="postcomment-bullet">&bull;</span>' +
						'<span class="postcomment-time" title="' + c.formatted_time + '">' + c.relative_time + '</span>' +
						editedTag +
						actions +
					'</div>' +
					'<div class="postcomment-body">' +
						'<div class="postcomment-text">' + c.comment_text + '</div>' +
						'<div class="postcomment-raw-text" style="display:none;">' + (c.comment_text_raw || '') + '</div>' +
					'</div>' +
					'<div class="postcomment-edit-box" style="display:none;">' +
						'<textarea class="postcomment-edit-textarea" rows="2"></textarea>' +
						'<div class="postcomment-edit-actions">' +
							'<button type="button" class="postcomment-btn-cancel-edit">' + (cfg.lang.cancel || '') + '</button>' +
							'<button type="button" class="postcomment-btn-save-edit">' + (cfg.lang.save || '') + '</button>' +
						'</div>' +
					'</div>' +
				'</div>' +
			'</div>';
		}

		// Toggle / Focus Comment Form from Post Action Bar Button or Bar Add Button
		$(document).on('click', '.postcomment-quick-btn, .postcomment-btn-toggle-form', function(e) {
			e.preventDefault();
			var postId = $(this).data('post-id');
			var $container = $('#postcomments-' + postId);
			if (!$container.length) return;

			var $formWrap = $container.find('.postcomments-form-wrap');
			var $addBtn = $container.find('.postcomments-btn-add');
			if ($formWrap.is(':visible')) {
				$formWrap.slideUp(200);
				$addBtn.removeClass('is-active');
			} else {
				$formWrap.slideDown(200, function() {
					$formWrap.find('.postcomments-textarea').focus();
				});
				$addBtn.addClass('is-active');

				// If triggered from top action bar and form is outside viewport, scroll into view
				if ($(this).hasClass('postcomment-quick-btn')) {
					var formTop = $formWrap.offset().top;
					var winTop = $(window).scrollTop();
					var winBottom = winTop + $(window).height();
					if (formTop < winTop || formTop > winBottom - 100) {
						$('html, body').animate({ scrollTop: formTop - 150 }, 250);
					}
				}
			}
		});

		// Prevent mousedown on action buttons from stealing focus / triggering layout shift
		$(document).on('mousedown', '.postcomments-btn-submit-post, .postcomments-btn-cancel-post, .postcomment-btn-save-edit, .postcomment-btn-cancel-edit', function(e) {
			e.preventDefault();
		});

		// Cancel New Comment Form (Clears and hides the form)
		$(document).on('click', '.postcomments-btn-cancel-post', function(e) {
			e.preventDefault();
			var $formWrap = $(this).closest('.postcomments-form-wrap');
			var $container = $(this).closest('.postcomments-container');
			var $textarea = $formWrap.find('.postcomments-textarea');
			$textarea.val('');
			$formWrap.find('.postcomments-btn-submit-post').prop('disabled', true);
			$formWrap.slideUp(200);
			$container.find('.postcomments-btn-add').removeClass('is-active');
		});

		// Live Character Count & Submit Button State
		$(document).on('input keyup paste change propertychange', '.postcomments-textarea', function() {
			var $textarea = $(this);
			var $formWrap = $textarea.closest('.postcomments-form-wrap');
			var $btnSubmit = $formWrap.find('.postcomments-btn-submit-post');
			var val = $.trim($textarea.val());

			$btnSubmit.prop('disabled', val.length === 0);

			if (cfg.max_length > 0) {
				var remaining = cfg.max_length - $textarea.val().length;
				var $counter = $formWrap.find('.postcomments-char-count');
				$counter.text(remaining);
				if (remaining < 0) {
					$counter.css('color', '#ef4444');
					$btnSubmit.prop('disabled', true);
				} else {
					$counter.css('color', '');
				}
			}
		});

		// Keyboard shortcut: Ctrl+Enter (or Cmd+Enter) to submit comment
		$(document).on('keydown', '.postcomments-textarea', function(e) {
			if ((e.ctrlKey || e.metaKey) && (e.keyCode === 13 || e.key === 'Enter')) {
				e.preventDefault();
				var $formWrap = $(this).closest('.postcomments-form-wrap');
				$formWrap.find('.postcomments-btn-submit-post:not(:disabled)').click();
			}
		});

		// Keyboard shortcut: Ctrl+Enter (or Cmd+Enter) to save inline edit
		$(document).on('keydown', '.postcomment-edit-textarea', function(e) {
			if ((e.ctrlKey || e.metaKey) && (e.keyCode === 13 || e.key === 'Enter')) {
				e.preventDefault();
				var $editBox = $(this).closest('.postcomment-edit-box');
				$editBox.find('.postcomment-btn-save-edit:not(:disabled)').click();
			}
		});

		// Submit New Comment via AJAX
		$(document).on('click', '.postcomments-btn-submit-post', function(e) {
			e.preventDefault();
			var $btn = $(this);
			if ($btn.hasClass('loading')) return;

			var $container = $btn.closest('.postcomments-container');
			var postId = $container.data('post-id');
			var $textarea = $container.find('.postcomments-textarea');
			var text = $.trim($textarea.val());

			if (!text) {
				$textarea.focus();
				return;
			}

			$btn.prop('disabled', true).addClass('loading');
			var origHtml = $btn.html();
			$btn.html('<i class="icon fa-spinner fa-spin fa-fw"></i>');

			$.ajax({
				url: cfg.url_add,
				type: 'POST',
				data: {
					post_id: postId,
					comment_text: text
				},
				dataType: 'json',
				success: function(resp) {
					if (resp && resp.success && resp.comment) {
						var html = renderCommentHtml(resp.comment);
						var $list = $container.find('.postcomments-list');
						var $newEl = $(html).hide();
						$list.append($newEl);
						$newEl.fadeIn(300);

						$textarea.val('');
						if (cfg.max_length > 0) {
							$container.find('.postcomments-char-count').text(cfg.max_length);
						}

						// Reset submit button state
						$container.find('.postcomments-btn-submit-post').prop('disabled', true);

						// Smoothly close form after successful submit
						$container.find('.postcomments-form-wrap').slideUp(200);
						$container.find('.postcomments-btn-add').removeClass('is-active');

						// Update count badge
						var newCount = resp.total_count || ($list.find('.postcomment-item').length);
						$container.attr('data-total-count', newCount);
						var $badge = $container.find('.postcomments-counter-badge');
						$badge.text(newCount).removeClass('badge-empty');
						$container.find('.postcomments-content').removeClass('postcomments-content-empty');
						$container.find('.postcomments-bar').removeClass('postcomments-empty');
					} else {
						alert(resp && resp.error ? resp.error : (cfg.lang.error_submit || ''));
					}
				},
				error: function(xhr) {
					var err = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : (cfg.lang.error_network || '');
					alert(err);
				},
				complete: function() {
					$btn.html(origHtml);
					$btn.prop('disabled', $.trim($textarea.val()).length === 0).removeClass('loading');
				}
			});
		});

		// Inline Edit: Start Editing
		$(document).on('click', '.postcomment-btn-edit', function(e) {
			e.preventDefault();
			var $item = $(this).closest('.postcomment-item');
			var rawText = $item.find('.postcomment-raw-text').text();
			var $body = $item.find('.postcomment-body');
			var $editBox = $item.find('.postcomment-edit-box');

			$editBox.find('.postcomment-edit-textarea').val(rawText);
			$body.slideUp(150);
			$editBox.slideDown(150, function() {
				$(this).find('.postcomment-edit-textarea').focus();
			});
		});

		// Inline Edit: Cancel
		$(document).on('click', '.postcomment-btn-cancel-edit', function(e) {
			e.preventDefault();
			var $item = $(this).closest('.postcomment-item');
			$item.find('.postcomment-edit-box').slideUp(150);
			$item.find('.postcomment-body').slideDown(150);
		});

		// Inline Edit: Save
		$(document).on('click', '.postcomment-btn-save-edit', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var $item = $btn.closest('.postcomment-item');
			var commentId = $item.data('comment-id');
			var $editBox = $item.find('.postcomment-edit-box');
			var newText = $.trim($editBox.find('.postcomment-edit-textarea').val());

			if (!newText) return;

			$btn.prop('disabled', true);
			var origHtml = $btn.html();
			$btn.html('<i class="icon fa-spinner fa-spin fa-fw"></i>');

			$.ajax({
				url: cfg.url_edit,
				type: 'POST',
				data: {
					comment_id: commentId,
					comment_text: newText
				},
				dataType: 'json',
				success: function(resp) {
					if (resp && resp.success && resp.comment) {
						$item.find('.postcomment-text').html(resp.comment.comment_text);
						$item.find('.postcomment-raw-text').text(resp.comment.comment_text_raw);

						// Ensure edited tag exists
						if (!$item.find('.postcomment-edited-tag').length) {
							$('<span class="postcomment-edited-tag" title="' + (cfg.lang.edited || '') + '">(' + (cfg.lang.edited || '') + ')</span>')
								.insertAfter($item.find('.postcomment-time'));
						}

						$editBox.slideUp(150);
						$item.find('.postcomment-body').slideDown(150);
					} else {
						alert(resp && resp.error ? resp.error : (cfg.lang.error_edit || ''));
					}
				},
				error: function(xhr) {
					var err = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : (cfg.lang.error_network || '');
					alert(err);
				},
				complete: function() {
					$btn.prop('disabled', false).html(origHtml);
				}
			});
		});

		// Delete Comment via AJAX
		$(document).on('click', '.postcomment-btn-delete', function(e) {
			e.preventDefault();
			var msg = cfg.lang.delete_confirm;
			if (!confirm(msg)) return;

			var $item = $(this).closest('.postcomment-item');
			var commentId = $item.data('comment-id');
			var $container = $item.closest('.postcomments-container');

			$.ajax({
				url: cfg.url_delete,
				type: 'POST',
				data: { comment_id: commentId },
				dataType: 'json',
				success: function(resp) {
					if (resp && resp.success) {
						$item.fadeOut(250, function() {
							$(this).remove();
							var remainingCount = resp.total_count !== undefined ? resp.total_count : $container.find('.postcomment-item').length;
							$container.attr('data-total-count', remainingCount);
							var $badge = $container.find('.postcomments-counter-badge');
							$badge.text(remainingCount);

							if (remainingCount === 0) {
								$badge.addClass('badge-empty');
								$container.find('.postcomments-content').addClass('postcomments-content-empty');
							}
						});
					} else {
						alert(resp && resp.error ? resp.error : (cfg.lang.error_delete || ''));
					}
				},
				error: function(xhr) {
					var err = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : (cfg.lang.error_network || '');
					alert(err);
				}
			});
		});

		// Load More Comments via AJAX
		$(document).on('click', '.postcomments-load-more-btn', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var postId = $btn.data('post-id');
			var offset = parseInt($btn.attr('data-offset'), 10) || 0;
			var $container = $('#postcomments-' + postId);

			$btn.prop('disabled', true);
			var origHtml = $btn.html();
			$btn.html('<i class="icon fa-spinner fa-spin fa-fw"></i> ' + (cfg.lang.loading || ''));

			$.ajax({
				url: cfg.url_load,
				type: 'GET',
				data: {
					post_id: postId,
					offset: offset,
					limit: 10
				},
				dataType: 'json',
				success: function(resp) {
					if (resp && resp.success && resp.comments) {
						var $list = $container.find('.postcomments-list');
						$.each(resp.comments, function(idx, c) {
							// Avoid duplicates
							if (!$('#postcomment-' + c.comment_id).length) {
								var html = renderCommentHtml(c);
								$list.prepend($(html).hide().fadeIn(250));
							}
						});

						var newOffset = offset + resp.comments.length;
						var totalCount = parseInt($container.attr('data-total-count'), 10) || 0;
						var remaining = Math.max(0, totalCount - newOffset);

						$btn.attr('data-offset', newOffset);
						$btn.attr('data-remaining', remaining);

						if (remaining <= 0 || resp.comments.length === 0) {
							$btn.closest('.postcomments-load-more-wrap').slideUp(200);
						} else {
							var loadMoreText = (cfg.lang.load_more || '').replace('%d', remaining);
							$btn.find('span').text(loadMoreText);
						}
					}
				},
				complete: function() {
					$btn.prop('disabled', false).html(origHtml);
				}
			});
		});
	});
})(jQuery);
