(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState !== 'loading') {
            callback();
        } else {
            document.addEventListener('DOMContentLoaded', callback);
        }
    }

    ready(function () {
        var container = document.getElementById('schemaweave-locations');
        var addButton = document.getElementById('schemaweave-add-location');
        var template = document.getElementById('schemaweave-location-template');

        if (!container || !addButton || !template) {
            return;
        }

        function nextIndex() {
            var highest = -1;
            container.querySelectorAll('[data-location-row] input[name]').forEach(function (input) {
                var match = input.name.match(/\[locations\]\[(\d+)\]/);
                if (match) {
                    highest = Math.max(highest, parseInt(match[1], 10));
                }
            });
            return highest + 1;
        }

        function bindRemove(scope) {
            scope.querySelectorAll('[data-remove-location]').forEach(function (button) {
                if (button.dataset.schemaweaveBound === '1') {
                    return;
                }
                button.dataset.schemaweaveBound = '1';
                button.addEventListener('click', function () {
                    var row = button.closest('[data-location-row]');
                    if (row) {
                        row.remove();
                    }
                });
            });
        }

        bindRemove(container);

        addButton.addEventListener('click', function () {
            var index = nextIndex();
            var html = template.innerHTML.replace(/__INDEX__/g, String(index));
            var holder = document.createElement('div');
            holder.innerHTML = html.trim();
            var row = holder.firstElementChild;
            if (row) {
                container.appendChild(row);
                bindRemove(row);
                var firstInput = row.querySelector('input');
                if (firstInput) {
                    firstInput.focus();
                }
            }
        });
    });
}());
