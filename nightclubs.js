// Data utworzenia: 2025-03-23 15:56:00
// Autor: PanKrowa

// Globalne zmienne
let currentClubId = null;
let currentClubData = null;
let availableDrugs = {};
let visitClubData = {
    clubId: null,
    drugs: [],
    entryFee: 0
};

// Inicjalizacja po załadowaniu dokumentu
document.addEventListener('DOMContentLoaded', () => {
    // Event listenery
    document.getElementById('drugSelect')?.addEventListener('change', updateMaxQuantity);
    document.getElementById('visitDrugSelect')?.addEventListener('change', updateVisitCosts);

    // Inicjalizacja tooltipów
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Walidacja formularzy
function validateForm(form) {
    const inputs = form.querySelectorAll('input[required], select[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value) {
            isValid = false;
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
        }
    });

    return isValid;
}

// Funkcja pokazująca modal dodawania narkotyków
function showAddDrugsModal(clubId) {
    currentClubId = clubId;
    document.getElementById('modalClubId').value = clubId;

    // Reset formularza
    document.getElementById('addDrugsForm').reset();

    // Aktualizacja maksymalnej ilości
    updateMaxQuantity();

    // Pokaż modal
    const modal = new bootstrap.Modal(document.getElementById('addDrugsModal'));
    modal.show();
}

// Aktualizacja maksymalnej ilości narkotyków
function updateMaxQuantity() {
    const select = document.getElementById('drugSelect');
    const quantityInput = document.getElementById('drugQuantity');
    const maxQuantity = select.options[select.selectedIndex].dataset.quantity;
    quantityInput.max = maxQuantity;
    quantityInput.value = Math.min(quantityInput.value || 1, maxQuantity);
}

// Funkcja dodająca narkotyki do klubu
function addDrugsToClub() {
    const form = document.getElementById('addDrugsForm');
    if (!validateForm(form)) {
        alert('Wypełnij wszystkie wymagane pola.');
        return;
    }

    const formData = new FormData(form);

    fetch('actions/add_club_drugs.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Wystąpił błąd podczas dodawania narkotyków.');
    });
}

// Funkcja pokazująca modal odwiedzania klubu
function showVisitClubModal(clubId) {
    const club = findClubById(clubId);
    if (!club) return;

    visitClubData.clubId = clubId;
    visitClubData.drugs = club.drugs || [];
    visitClubData.entryFee = club.entry_fee;

    // Aktualizuj select z narkotykami
    const select = document.getElementById('visitDrugSelect');
    select.innerHTML = '<option value="">Bez narkotyku (tylko wejście)</option>';
    
    visitClubData.drugs.forEach(drug => {
        if (drug && drug.quantity > 0) {
            select.innerHTML += `
                <option value="${drug.id}" 
                        data-price="${drug.price}"
                        data-energy="${drug.energy_boost}"
                        data-addiction="${drug.addiction_rate}">
                    ${drug.name} - $${drug.price}
                </option>
            `;
        }
    });

    // Aktualizuj koszty
    updateVisitCosts();

    // Pokaż modal
    const modal = new bootstrap.Modal(document.getElementById('visitClubModal'));
    modal.show();
}

// Aktualizacja kosztów wizyty
function updateVisitCosts() {
    const select = document.getElementById('visitDrugSelect');
    const drugInfo = document.getElementById('drugInfo');
    const option = select.selectedOptions[0];

    document.getElementById('entryFee').textContent = visitClubData.entryFee;

    if (option.value) {
        const drugPrice = option.dataset.price;
        const energyBoost = option.dataset.energy;
        const addictionRate = option.dataset.addiction;

        document.getElementById('drugPrice').textContent = drugPrice;
        document.getElementById('energyBoost').textContent = energyBoost;
        document.getElementById('addictionRate').textContent = addictionRate;
        document.getElementById('totalCost').textContent = 
            (parseInt(visitClubData.entryFee) + parseInt(drugPrice));
        
        drugInfo.classList.remove('d-none');
    } else {
        document.getElementById('totalCost').textContent = visitClubData.entryFee;
        drugInfo.classList.add('d-none');
    }
}

// Helper do znalezienia klubu po ID
function findClubById(clubId) {
    const allClubs = [
        ...Array.from(document.querySelectorAll('#other-clubs .card'))
    ];
    
    return allClubs.find(club => {
        return club.querySelector(`button[onclick*="${clubId}"]`);
    });
}

// Funkcje podstawowe (kupno/sprzedaż klubu)
function buyClub(clubId) {
    if (!confirm('Czy na pewno chcesz kupić ten klub?')) {
        return;
    }

    fetch('actions/buy_club.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `club_id=${clubId}`,
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Wystąpił błąd podczas komunikacji z serwerem.');
    });
}

function sellClub(clubId) {
    if (!confirm('Czy na pewno chcesz sprzedać ten klub?')) {
        return;
    }

    fetch('actions/sell_club.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `club_id=${clubId}`,
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Wystąpił błąd podczas komunikacji z serwerem.');
    });
}