document.addEventListener('DOMContentLoaded', function() {
    var principalContactInput = document.getElementById('id_principal_contact');
    var coordinatorContactInput1 = document.getElementById('id_coordinator_contact1');
    var coordinatorContactInput2 = document.getElementById('id_coordinator_contact2');
    var coordinatorContactInput3 = document.getElementById('id_coordinator_contact3');
    var coordinatorContactInput4 = document.getElementById('id_coordinator_contact4');
    var aerobayFeesInput = document.getElementById('id_aerobay_fees');

    if (principalContactInput) {
        principalContactInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
        });
    }

    if (coordinatorContactInput1) {
        coordinatorContactInput1.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
        });
    }
    if (coordinatorContactInput2) {
        coordinatorContactInput2.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
        });
    }
    if (coordinatorContactInput3) {
        coordinatorContactInput3.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
        });
    }
    if (coordinatorContactInput4) {
        coordinatorContactInput4.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
        });
    }

    if (aerobayFeesInput) {
        aerobayFeesInput.addEventListener('input', function() {
            // Remove non-numeric characters from the input value
            this.value = this.value.replace(/\D/g, '');
            // Trim the input value if it exceeds 12 characters
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
        });
    }
});

