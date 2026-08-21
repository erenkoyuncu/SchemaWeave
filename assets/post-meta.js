(function ($) {
    'use strict';

    function collectMeta(root) {
        var data = {
            disabled: root.querySelector('[name="schemaweave_meta[disabled]"]') && root.querySelector('[name="schemaweave_meta[disabled]"]').checked ? 1 : 0,
            type: root.querySelector('[name="schemaweave_meta[type]"]') ? root.querySelector('[name="schemaweave_meta[type]"]').value : 'auto',
            page_type: root.querySelector('[name="schemaweave_meta[page_type]"]') ? root.querySelector('[name="schemaweave_meta[page_type]"]').value : 'auto',
            description: root.querySelector('[name="schemaweave_meta[description]"]') ? root.querySelector('[name="schemaweave_meta[description]"]').value : '',
            image: root.querySelector('[name="schemaweave_meta[image]"]') ? root.querySelector('[name="schemaweave_meta[image]"]').value : '',
            brand: root.querySelector('[name="schemaweave_meta[brand]"]') ? root.querySelector('[name="schemaweave_meta[brand]"]').value : '',
            faq: []
        };

        root.querySelectorAll('[data-schemaweave-faq-row]').forEach(function (row) {
            var question = row.querySelector('input[name*="[question]"]');
            var answer = row.querySelector('textarea[name*="[answer]"]');
            data.faq.push({
                question: question ? question.value : '',
                answer: answer ? answer.value : ''
            });
        });

        return data;
    }

    function nextFaqIndex(root) {
        var highest = -1;
        root.querySelectorAll('[data-schemaweave-faq-row] [name]').forEach(function (field) {
            var match = field.name.match(/\[faq\]\[(\d+)\]/);
            if (match) {
                highest = Math.max(highest, parseInt(match[1], 10));
            }
        });
        return highest + 1;
    }

    function bindFaqRemove(scope) {
        scope.querySelectorAll('[data-schemaweave-remove-faq]').forEach(function (button) {
            if (button.dataset.schemaweaveBound === '1') {
                return;
            }
            button.dataset.schemaweaveBound = '1';
            button.addEventListener('click', function () {
                var row = button.closest('[data-schemaweave-faq-row]');
                if (row) {
                    row.remove();
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var root = document.querySelector('[data-schemaweave-editor]');
        if (!root || typeof SchemaWeaveEditor === 'undefined') {
            return;
        }

        var faqList = root.querySelector('[data-schemaweave-faq-list]');
        var faqTemplate = root.querySelector('[data-schemaweave-faq-template]');
        var addFaq = root.querySelector('[data-schemaweave-add-faq]');

        bindFaqRemove(root);

        if (faqList && faqTemplate && addFaq) {
            addFaq.addEventListener('click', function () {
                var index = nextFaqIndex(root);
                var html = faqTemplate.innerHTML.replace(/__INDEX__/g, String(index));
                var holder = document.createElement('div');
                holder.innerHTML = html.trim();
                var row = holder.firstElementChild;
                if (row) {
                    faqList.appendChild(row);
                    bindFaqRemove(row);
                    var firstInput = row.querySelector('input');
                    if (firstInput) {
                        firstInput.focus();
                    }
                }
            });
        }

        var imageInput = root.querySelector('[data-schemaweave-image-input]');
        var mediaButton = root.querySelector('[data-schemaweave-media]');
        var clearImage = root.querySelector('[data-schemaweave-clear-image]');
        var mediaFrame = null;

        if (mediaButton && imageInput && window.wp && wp.media) {
            mediaButton.addEventListener('click', function () {
                if (mediaFrame) {
                    mediaFrame.open();
                    return;
                }

                mediaFrame = wp.media({
                    title: SchemaWeaveEditor.mediaTitle,
                    button: { text: SchemaWeaveEditor.mediaButton },
                    multiple: false,
                    library: { type: 'image' }
                });

                mediaFrame.on('select', function () {
                    var attachment = mediaFrame.state().get('selection').first().toJSON();
                    if (attachment && attachment.url) {
                        imageInput.value = attachment.url;
                    }
                });

                mediaFrame.open();
            });
        }

        if (clearImage && imageInput) {
            clearImage.addEventListener('click', function () {
                imageInput.value = '';
            });
        }

        var previewButton = root.querySelector('[data-schemaweave-preview]');
        var previewWrap = root.querySelector('[data-schemaweave-preview-wrap]');
        var previewOutput = root.querySelector('[data-schemaweave-preview-output]');

        if (previewButton && previewWrap && previewOutput) {
            previewButton.addEventListener('click', function () {
                var original = previewButton.textContent;
                previewButton.disabled = true;
                previewButton.textContent = SchemaWeaveEditor.previewing;
                previewWrap.hidden = false;
                previewOutput.textContent = SchemaWeaveEditor.previewing;

                $.post(SchemaWeaveEditor.ajaxUrl, {
                    action: 'schemaweave_preview',
                    nonce: SchemaWeaveEditor.previewNonce,
                    post_id: root.getAttribute('data-post-id'),
                    meta: JSON.stringify(collectMeta(root))
                }).done(function (response) {
                    if (response && response.success && response.data) {
                        previewOutput.textContent = response.data.json || '';
                    } else {
                        previewOutput.textContent = SchemaWeaveEditor.previewError;
                    }
                }).fail(function (xhr) {
                    var message = SchemaWeaveEditor.previewError;
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        message = xhr.responseJSON.data.message;
                    }
                    previewOutput.textContent = message;
                }).always(function () {
                    previewButton.disabled = false;
                    previewButton.textContent = original;
                });
            });
        }
    });
}(jQuery));
