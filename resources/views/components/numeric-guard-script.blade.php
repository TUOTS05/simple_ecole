<script>
    // Empêche la saisie de caractères non numériques dans les champs
    // de type "number" et "tel" (téléphone) sur toute l'application.
    //
    // Note : Firefox n'empêche pas visuellement de taper des lettres dans un
    // <input type="number"> (il vide juste .value côté validation), donc un
    // simple nettoyage de .value après coup ne suffit pas. On bloque donc la
    // frappe elle-même (keydown) et le collage (paste), ce qui fonctionne de
    // façon fiable sur tous les navigateurs.
    (function () {
        var CONTROL_KEYS = [
            'Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
            'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'
        ];

        function allowsNegative(input) {
            var minAttr = input.getAttribute('min');
            return !(minAttr !== null && minAttr !== '' && parseFloat(minAttr) >= 0);
        }

        function isNumberKeyAllowed(event, input) {
            if (event.ctrlKey || event.metaKey || event.altKey) {
                return true;
            }
            if (CONTROL_KEYS.indexOf(event.key) !== -1) {
                return true;
            }
            if (event.key.length !== 1) {
                return true;
            }
            if (/^[0-9]$/.test(event.key)) {
                return true;
            }
            if (event.key === '.') {
                return input.value.indexOf('.') === -1;
            }
            if (event.key === '-') {
                return allowsNegative(input) && input.selectionStart === 0 && input.value.indexOf('-') === -1;
            }
            return false;
        }

        function isPhoneKeyAllowed(event) {
            if (event.ctrlKey || event.metaKey || event.altKey) {
                return true;
            }
            if (CONTROL_KEYS.indexOf(event.key) !== -1) {
                return true;
            }
            if (event.key.length !== 1) {
                return true;
            }
            return /^[0-9+\-\s()]$/.test(event.key);
        }

        function cleanNumberText(text, input) {
            var negative = allowsNegative(input) && text.charAt(0) === '-';
            var cleaned = text.replace(/[^0-9.]/g, '');

            var firstDot = cleaned.indexOf('.');
            if (firstDot !== -1) {
                cleaned = cleaned.slice(0, firstDot + 1) + cleaned.slice(firstDot + 1).replace(/\./g, '');
            }

            return (negative ? '-' : '') + cleaned;
        }

        function insertAtCursor(input, text) {
            var start = input.selectionStart || 0;
            var end = input.selectionEnd || 0;
            var current = input.value;
            var next = current.slice(0, start) + text + current.slice(end);

            input.value = next;
            var caret = start + text.length;
            if (typeof input.setSelectionRange === 'function') {
                input.setSelectionRange(caret, caret);
            }
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }

        document.addEventListener('keydown', function (event) {
            var target = event.target;
            if (!target || target.tagName !== 'INPUT') {
                return;
            }

            if (target.type === 'number' && !isNumberKeyAllowed(event, target)) {
                event.preventDefault();
            } else if (target.type === 'tel' && !isPhoneKeyAllowed(event)) {
                event.preventDefault();
            }
        }, true);

        document.addEventListener('paste', function (event) {
            var target = event.target;
            if (!target || target.tagName !== 'INPUT') {
                return;
            }
            if (target.type !== 'number' && target.type !== 'tel') {
                return;
            }

            var pasted = (event.clipboardData || window.clipboardData).getData('text');
            var cleaned = target.type === 'number'
                ? cleanNumberText(pasted, target)
                : pasted.replace(/[^0-9+\-\s()]/g, '');

            event.preventDefault();
            insertAtCursor(target, cleaned);
        }, true);

        // Filet de sécurité pour les cas non couverts par keydown/paste
        // (autocomplétion, glisser-déposer, saisie vocale...).
        document.addEventListener('input', function (event) {
            var target = event.target;
            if (!target || target.tagName !== 'INPUT') {
                return;
            }

            if (target.type === 'number') {
                var cleaned = cleanNumberText(target.value, target);
                if (cleaned !== target.value) {
                    target.value = cleaned;
                }
            } else if (target.type === 'tel') {
                var cleanedPhone = target.value.replace(/[^0-9+\-\s()]/g, '');
                if (cleanedPhone !== target.value) {
                    target.value = cleanedPhone;
                }
            }
        }, true);
    })();
</script>
