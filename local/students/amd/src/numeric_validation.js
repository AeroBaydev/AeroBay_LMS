document.addEventListener('DOMContentLoaded', function() {
    var contactNumberInput = document.getElementById('id_contact_number');
    var ctcInput = document.getElementById('id_ctc');

    var applyNumericInputHandler = function(input, maxLength) {
        if (input) {
            input.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '');
                if (this.value.length > maxLength) {
                    this.value = this.value.slice(0, maxLength);
                }
            });
        }
    };

    applyNumericInputHandler(contactNumberInput, 10);
    applyNumericInputHandler(ctcInput, 12);
});
