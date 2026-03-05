document.addEventListener('DOMContentLoaded', function() {

    /**
     * Capitalizes the first letter of each word in a string.
     * This is non-destructive to existing capitalization within words (e.g., BuildCo).
     * Handles name capitalization.
     * @param {string} str The input string.
     * @returns {string} The title-cased string.
     */
    function toTitleCase(str) {
        if (!str) return '';
        return str.split(' ').map(word => {
            if (word.length > 0) {
                return word.charAt(0).toUpperCase() + word.slice(1);
            }
            return '';
        }).join(' ');
    }

    /**
     * Capitalizes the beginning of sentences in a string.
     * This is non-destructive and only up-cases where appropriate.
     * Handles description capitalization.
     * @param {string} str The input string.
     * @returns {string} The sentence-cased string.
     */
    function toSentenceCase(str) {
        if (!str) return '';
        let result = str.trim();
        // Capitalize the first letter of the whole string.
        result = result.charAt(0).toUpperCase() + result.slice(1);
        // Capitalize letters after a sentence-ending punctuation followed by space.
        result = result.replace(/([.?!])\s+([a-z])/g, (match, p1, p2) => `${p1} ${p2.toUpperCase()}`);
        return result;
    }

    // --- Date Smarts ---

    let lastEnteredDate = null;

    /**
     * Parses a string with custom rules into a Date object.
     * @param {string} value The string from the input field.
     * @param {Date|null} lastDate The last successfully entered date on the form.
     * @returns {Date|null} A valid Date object or null if parsing fails.
     */
    function parseSmartDate(value, lastDate) {
        let input = value.trim().toLowerCase();
        if (!input) return null;

        let baseDate = null;
        let modifier = null;

        // 1. Extract modifier if it exists (e.g., "+5d")
        const modifierMatch = input.match(/([+-])\s*(\d+)\s*([dmy])?$/);
        if (modifierMatch) {
            modifier = { op: modifierMatch[1], num: parseInt(modifierMatch[2], 10), unit: modifierMatch[3] || 'd' };
            input = input.substring(0, modifierMatch.index).trim();
        }

        // 2. Handle special shortcuts
        if (input === '.') {
            baseDate = lastDate ? new Date(lastDate.getTime()) : new Date();
        } else if (input === '..') {
            baseDate = lastDate ? new Date(lastDate.getTime()) : new Date();
            if (lastDate) baseDate.setDate(baseDate.getDate() + 1);
        } else if (input.length > 0) {
            let d, m, y;
            const now = new Date();
            
            // 3. Handle different formats
            const justDigits = input.replace(/[^0-9]/g, '');

            // Format: YYYYMMDD (e.g., 20260801)
            if (/^\d{8}$/.test(justDigits) && input.length === 8) {
                y = parseInt(justDigits.substring(0, 4), 10);
                m = parseInt(justDigits.substring(4, 6), 10) - 1;
                d = parseInt(justDigits.substring(6, 8), 10);
            } else {
                // Normalize separators to slashes (handles spaces, dots, dashes) e.g., "1 8 26" -> "1/8/26"
                const parts = input.replace(/[\s.-]+/g, '/').split('/');

                if (parts.length === 1 && !isNaN(parts[0])) {
                    const num = parts[0];
                    if (num.length <= 2) { // Format: d or dd (e.g., 15)
                        d = parseInt(num, 10); m = now.getMonth(); y = now.getFullYear();
                    } else if (num.length === 4) { // Format: ddmm (e.g., 0108)
                        d = parseInt(num.substring(0, 2), 10); m = parseInt(num.substring(2, 4), 10) - 1; y = now.getFullYear();
                    }
                } else if (parts.length === 2) { // Format: d/m or dd/mm (e.g., 1/8)
                    d = parseInt(parts[0], 10); m = parseInt(parts[1], 10) - 1; y = now.getFullYear();
                } else if (parts.length === 3) { // Format: d/m/y or d/m/yy (e.g., 1/8/26)
                    d = parseInt(parts[0], 10); m = parseInt(parts[1], 10) - 1; y = parseInt(parts[2], 10);
                    if (y < 100) y += 2000; // Handle 2-digit year
                }
            }

            // 4. Validate the parsed date
            if (d === undefined || m === undefined || y === undefined) return null;
            baseDate = new Date(y, m, d);
            if (baseDate.getFullYear() !== y || baseDate.getMonth() !== m || baseDate.getDate() !== d) return null;
        }

        if (!baseDate || isNaN(baseDate)) return null;

        // 5. Apply modifier
        if (modifier) {
            const op = modifier.op === '+' ? 1 : -1;
            if (modifier.unit === 'd') baseDate.setDate(baseDate.getDate() + (modifier.num * op));
            if (modifier.unit === 'm') baseDate.setMonth(baseDate.getMonth() + (modifier.num * op));
            if (modifier.unit === 'y') baseDate.setFullYear(baseDate.getFullYear() + (modifier.num * op));
        }
        return baseDate;
    }

    /**
     * Formats a Date object into DD/MM/YYYY.
     * @param {Date} date The date to format.
     * @returns {string}
     */
    function formatDate(date) {
        if (!(date instanceof Date) || isNaN(date)) return '';
        const d = String(date.getDate()).padStart(2, '0');
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const y = date.getFullYear();
        return `${d}/${m}/${y}`;
    }

    // --- Apply transformations on blur ---

    document.querySelectorAll('.smart-date').forEach(input => {
        input.addEventListener('blur', (e) => {
            const value = e.target.value.trim();
            if (value === '') {
                e.target.classList.remove('is-invalid');
                return;
            }
            const newDate = parseSmartDate(value, lastEnteredDate);
            if (newDate) {
                e.target.value = formatDate(newDate);
                lastEnteredDate = newDate;
                e.target.classList.remove('is-invalid');
            } else {
                e.target.classList.add('is-invalid');
            }
        });
    });

    // Title Case for names and project titles
    document.querySelectorAll('.smart-title-case').forEach(input => {
        input.addEventListener('blur', () => { input.value = toTitleCase(input.value); });
    });

    // Sentence Case for longer description fields
    document.querySelectorAll('.smart-sentence-case').forEach(input => {
        input.addEventListener('blur', () => { input.value = toSentenceCase(input.value); });
    });

    // --- Action buttons for inputs (tel, mailto) ---
    document.querySelectorAll('.input-with-action').forEach(wrapper => {
        const input = wrapper.querySelector('input');
        if (!input) return;

        const mailBtn = wrapper.querySelector('.mail-action');
        const telBtn = wrapper.querySelector('.tel-action');

        function updateActionButtons() {
            if (mailBtn) {
                mailBtn.style.display = input.value ? 'inline-block' : 'none';
                if (input.value) mailBtn.href = 'mailto:' + input.value;
            }
            if (telBtn) {
                telBtn.style.display = input.value ? 'inline-block' : 'none';
                if (input.value) telBtn.href = 'tel:' + input.value.replace(/\s/g, '');
            }
        }

        input.addEventListener('input', updateActionButtons);
        // Also run on blur to catch programmatic changes or autofill
        input.addEventListener('blur', updateActionButtons);
        // Initial state check
        updateActionButtons();
    });

    // --- Date Picker Integration (Calendar Button) ---
    document.querySelectorAll('.smart-date').forEach(input => {
        // 1. Ensure input is wrapped for positioning
        let wrapper = input.parentElement;
        if (!wrapper.classList.contains('input-with-action')) {
            wrapper = document.createElement('div');
            wrapper.className = 'input-with-action';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
        }

        // 2. Create Hidden Date Input (Native Picker)
        const dateInput = document.createElement('input');
        dateInput.type = 'date';
        // Hide it visually but keep it functional
        dateInput.style.position = 'absolute';
        dateInput.style.opacity = '0';
        dateInput.style.pointerEvents = 'none';
        dateInput.style.bottom = '0';
        dateInput.style.left = '0';
        dateInput.style.width = '0';
        dateInput.style.height = '0';
        dateInput.tabIndex = -1; 
        wrapper.appendChild(dateInput);

        // 3. Create Calendar Button
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn-action date-action';
        btn.innerHTML = '📅'; 
        btn.title = 'Open Calendar';
        wrapper.appendChild(btn);

        // 4. Event Handlers
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            // Sync current text value to date picker
            const parsedDate = parseSmartDate(input.value, null);
            if (parsedDate) {
                const y = parsedDate.getFullYear();
                const m = String(parsedDate.getMonth() + 1).padStart(2, '0');
                const d = String(parsedDate.getDate()).padStart(2, '0');
                dateInput.value = `${y}-${m}-${d}`;
            } else {
                dateInput.value = ''; 
            }
            // Trigger the picker
            if (dateInput.showPicker) dateInput.showPicker();
            else dateInput.click(); // Fallback
        });

        dateInput.addEventListener('change', () => {
            if (!dateInput.value) return;
            const [y, m, d] = dateInput.value.split('-');
            input.value = `${d}/${m}/${y}`;
            input.dispatchEvent(new Event('blur')); // Trigger smart validation
        });
    });
});