// Game.js - Main game functionality

// Constants
const ENERGY_REFRESH_INTERVAL = 60000; // 1 minute
const MARKET_REFRESH_INTERVAL = 300000; // 5 minutes
const NOTIFICATION_TIMEOUT = 5000; // 5 seconds

// Game state
let gameState = {
    lastUpdate: new Date(),
    notifications: [],
    activeTimers: {},
    marketPrices: {}
};

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', () => {
    initializeGame();
    startTimers();
    setupEventListeners();
});

// Main initialization
function initializeGame() {
    updateStats();
    checkNotifications();
    updateTimers();
}

// Update character stats
function updateStats() {
    fetch('/api/character/stats')
        .then(response => response.json())
        .then(stats => {
            updateStatDisplays(stats);
            checkStatRequirements(stats);
        })
        .catch(error => console.error('Error updating stats:', error));
}

// Update UI elements with current stats
function updateStatDisplays(stats) {
    // Update energy bar
    const energyBar = document.querySelector('.energy-progress');
    if (energyBar) {
        const percentage = (stats.currentEnergy / stats.maxEnergy) * 100;
        energyBar.style.width = `${percentage}%`;
        energyBar.textContent = `${stats.currentEnergy}/${stats.maxEnergy}`;
    }

    // Update cash display
    const cashDisplay = document.querySelector('.cash-display');
    if (cashDisplay) {
        cashDisplay.textContent = `$${numberWithCommas(stats.cash)}`;
    }

    // Update other stats
    Object.entries(stats).forEach(([stat, value]) => {
        const element = document.querySelector(`.stat-${stat}`);
        if (element) {
            element.textContent = typeof value === 'number' ? 
                numberWithCommas(value) : value;
        }
    });
}

// Check requirements for actions
function checkStatRequirements(stats) {
    document.querySelectorAll('[data-requires]').forEach(element => {
        const requirements = JSON.parse(element.dataset.requires);
        let canPerform = true;

        Object.entries(requirements).forEach(([stat, required]) => {
            if (stats[stat] < required) canPerform = false;
        });

        element.disabled = !canPerform;
        element.classList.toggle('disabled', !canPerform);
    });
}

// Timer management
function startTimers() {
    // Energy regeneration timer
    gameState.activeTimers.energy = setInterval(() => {
        updateStats();
    }, ENERGY_REFRESH_INTERVAL);

    // Market price updates
    gameState.activeTimers.market = setInterval(() => {
        updateMarketPrices();
    }, MARKET_REFRESH_INTERVAL);
}

// Market price updates
function updateMarketPrices() {
    fetch('/api/market/prices')
        .then(response => response.json())
        .then(prices => {
            gameState.marketPrices = prices;
            updateMarketDisplay();
        })
        .catch(error => console.error('Error updating market prices:', error));
}

// Notification system
function showNotification(message, type = 'info') {
    const notification = {
        id: Date.now(),
        message,
        type
    };

    gameState.notifications.push(notification);
    displayNotification(notification);

    setTimeout(() => {
        removeNotification(notification.id);
    }, NOTIFICATION_TIMEOUT);
}

// Display notification in UI
function displayNotification(notification) {
    const container = document.querySelector('.notification-container');
    if (!container) return;

    const element = document.createElement('div');
    element.className = `alert alert-${notification.type} notification`;
    element.setAttribute('data-notification-id', notification.id);
    element.textContent = notification.message;

    container.appendChild(element);
}

// Remove notification
function removeNotification(id) {
    const element = document.querySelector(`[data-notification-id="${id}"]`);
    if (element) {
        element.classList.add('fade-out');
        setTimeout(() => element.remove(), 300);
    }

    gameState.notifications = gameState.notifications.filter(n => n.id !== id);
}

// Action confirmation
function confirmAction(message) {
    return new Promise((resolve) => {
        showConfirmationDialog(message).then(result => {
            resolve(result);
        });
    });
}

// Utility functions
function numberWithCommas(x) {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function updateTimers() {
    document.querySelectorAll('[data-countdown]').forEach(element => {
        const target = new Date(element.dataset.countdown);
        
        const interval = setInterval(() => {
            const now = new Date();
            const distance = target - now;

            if (distance < 0) {
                clearInterval(interval);
                element.textContent = 'Zakończono!';
                updateStats();
                return;
            }

            const minutes = Math.floor(distance / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            element.textContent = `${minutes}m ${seconds}s`;
        }, 1000);
    });
}

// Event listeners
function setupEventListeners() {
    // Action buttons
    document.querySelectorAll('[data-action]').forEach(button => {
        button.addEventListener('click', async (e) => {
            e.preventDefault();
            const action = e.target.dataset.action;
            
            if (e.target.dataset.confirm) {
                const confirmed = await confirmAction(e.target.dataset.confirm);
                if (!confirmed) return;
            }

            performAction(action, e.target.dataset);
        });
    });

    // Form submissions
    document.querySelectorAll('form[data-game-form]').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            const action = form.dataset.gameForm;

            try {
                const response = await fetch(`/api/${action}`, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    updateStats();
                } else {
                    showNotification(result.message, 'danger');
                }
            } catch (error) {
                showNotification('Wystąpił błąd podczas wykonywania akcji', 'danger');
            }
        });
    });
}

// Action handler
async function performAction(action, data) {
    try {
        const response = await fetch(`/api/${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            updateStats();
            
            if (result.redirect) {
                window.location.href = result.redirect;
            }
        } else {
            showNotification(result.message, 'danger');
        }
    } catch (error) {
        showNotification('Wystąpił błąd podczas wykonywania akcji', 'danger');
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
    if (e.ctrlKey) {
        switch(e.key) {
            case 'h':
                window.location.href = '/index.php';
                break;
            case 'r':
                updateStats();
                break;
        }
    }
});

// Error handling
window.onerror = function(msg, url, lineNo, columnNo, error) {
    console.error('Error: ' + msg + '\nURL: ' + url + '\nLine: ' + lineNo + '\nColumn: ' + columnNo + '\nError object: ' + JSON.stringify(error));
    showNotification('Wystąpił błąd w aplikacji', 'danger');
    return false;
};

// Export functions for use in other scripts
window.gameUtils = {
    showNotification,
    confirmAction,
    updateStats,
    numberWithCommas
};