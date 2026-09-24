function selectRole(role) {

    document.getElementById('role').value = role;

    document.getElementById('role-selection').hidden = true;
    document.getElementById('signup-details').hidden = false;

    const nameLabel =
        document.getElementById('name-label');

    const roleName =
        document.getElementById('selected-role-name');

    const roleIcon =
        document.getElementById('selected-role-icon');

    const businessField =
        document.getElementById('business-name-field');

    const businessInput =
        document.getElementById('business_name');


    if (role === 'customer') {

        nameLabel.textContent = 'Full Name';
        roleName.textContent = 'Customer';
        roleIcon.textContent = '🛒';

        businessField.hidden = true;
        businessInput.required = false;

    } else if (role === 'farmer') {

        nameLabel.textContent = 'Full Name';
        roleName.textContent = 'Farmer';
        roleIcon.textContent = '🌱';

        businessField.hidden = false;
        businessInput.required = true;
    }
}


function changeRole() {

    document.getElementById('role').value = '';

    document.getElementById('signup-details').hidden = true;
    document.getElementById('role-selection').hidden = false;

    const businessField =
        document.getElementById('business-name-field');

    const businessInput =
        document.getElementById('business_name');

    businessField.hidden = true;
    businessInput.required = false;
}


document.querySelector('.auth-form').addEventListener(
    'submit',
    function (event) {

        const role =
            document.getElementById('role').value;

        if (!role) {
            event.preventDefault();
            alert('Please select an account type.');
        }
    }
);