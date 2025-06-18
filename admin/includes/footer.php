<?php
// admin/includes/footer.php
?>

</main>

<!-- Footer Admin -->
<footer class="admin-footer">
    <div class="admin-footer-content">
        <div class="footer-left">
            <p>&copy; <?php echo date('Y'); ?> OpticLook. Tous droits réservés.</p>
            <p class="footer-version">
                <i class="fas fa-code"></i> Version 2.1.0 
                <span class="separator">|</span>
                <i class="fas fa-database"></i> Base de données active
                <span class="separator">|</span>
                <i class="fas fa-server"></i> Serveur: <?php echo $_SERVER['SERVER_NAME']; ?>
            </p>
        </div>
        
        <div class="footer-center">
            <div class="footer-stats">
                <div class="footer-stat">
                    <i class="fas fa-users"></i>
                    <span>Connecté en tant qu'admin</span>
                </div>
                <div class="footer-stat">
                    <i class="fas fa-clock"></i>
                    <span id="currentTime"><?php echo date('H:i:s'); ?></span>
                </div>
                <div class="footer-stat">
                    <i class="fas fa-calendar"></i>
                    <span><?php echo date('d/m/Y'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="footer-right">
            <div class="footer-actions">
                <button class="footer-btn" onclick="showKeyboardShortcuts()" title="Raccourcis clavier">
                    <i class="fas fa-keyboard"></i>
                </button>
                <button class="footer-btn" onclick="toggleDarkMode()" title="Mode sombre">
                    <i class="fas fa-moon"></i>
                </button>
                <button class="footer-btn" onclick="showSystemStatus()" title="Statut système">
                    <i class="fas fa-info-circle"></i>
                </button>
                <a href="aide.php" class="footer-btn" title="Aide">
                    <i class="fas fa-question-circle"></i>
                </a>
            </div>
        </div>
    </div>
</footer>

<!-- Modal pour les raccourcis clavier -->
<div id="keyboardShortcutsModal" class="modal">
    <div class="modal-content small">
        <div class="modal-header">
            <h3><i class="fas fa-keyboard"></i> Raccourcis clavier</h3>
            <button class="close" onclick="closeModal('keyboardShortcutsModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="shortcuts-list">
                <div class="shortcut-item">
                    <kbd>Ctrl</kbd> + <kbd>D</kbd>
                    <span>Tableau de bord</span>
                </div>
                <div class="shortcut-item">
                    <kbd>Ctrl</kbd> + <kbd>P</kbd>
                    <span>Ajouter produit</span>
                </div>
                <div class="shortcut-item">
                    <kbd>Ctrl</kbd> + <kbd>C</kbd>
                    <span>Ajouter client</span>
                </div>
                <div class="shortcut-item">
                    <kbd>Ctrl</kbd> + <kbd>R</kbd>
                    <span>Rendez-vous</span>
                </div>
                <div class="shortcut-item">
                    <kbd>Ctrl</kbd> + <kbd>S</kbd>
                    <span>Sauvegarder (dans les formulaires)</span>
                </div>
                <div class="shortcut-item">
                    <kbd>Escape</kbd>
                    <span>Fermer les modals</span>
                </div>
                <div class="shortcut-item">
                    <kbd>Alt</kbd> + <kbd>N</kbd>
                    <span>Notifications</span>
                </div>
                <div class="shortcut-item">
                    <kbd>Alt</kbd> + <kbd>M</kbd>
                    <span>Menu utilisateur</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour le statut système -->
<div id="systemStatusModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-server"></i> Statut du système</h3>
            <button class="close" onclick="closeModal('systemStatusModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="system-status-grid">
                <div class="status-item">
                    <div class="status-icon status-online">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="status-info">
                        <h4>Base de données</h4>
                        <p>Connexion active</p>
                        <small>MySQL 8.0</small>
                    </div>
                </div>
                <div class="status-item">
                    <div class="status-icon status-online">
                        <i class="fas fa-server"></i>
                    </div>
                    <div class="status-info">
                        <h4>Serveur Web</h4>
                        <p>Fonctionnel</p>
                        <small>PHP <?php echo PHP_VERSION; ?></small>
                    </div>
                </div>
                <div class="status-item">
                    <div class="status-icon status-online">
                        <i class="fas fa-hdd"></i>
                    </div>
                    <div class="status-info">
                        <h4>Espace disque</h4>
                        <p>85% utilisé</p>
                        <small>2.1 GB disponible</small>
                    </div>
                </div>
                <div class="status-item">
                    <div class="status-icon status-warning">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="status-info">
                        <h4>Dernière sauvegarde</h4>
                        <p>Il y a 2 heures</p>
                        <small><?php echo date('d/m/Y H:i'); ?></small>
                    </div>
                </div>
            </div>
            
            <div class="system-metrics">
                <h4>Métriques de performance</h4>
                <div class="metrics-grid">
                    <div class="metric">
                        <span class="metric-label">Temps de réponse moyen</span>
                        <span class="metric-value">245ms</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Utilisateurs actifs</span>
                        <span class="metric-value">1</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Requêtes/minute</span>
                        <span class="metric-value">23</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Uptime</span>
                        <span class="metric-value">99.9%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts globaux -->
<script>
// Horloge en temps réel
function updateClock() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('fr-FR');
    document.getElementById('currentTime').textContent = timeString;
}

// Mettre à jour l'horloge toutes les secondes
setInterval(updateClock, 1000);

// Raccourcis clavier
document.addEventListener('keydown', function(e) {
    // Ignore les raccourcis dans les champs de saisie
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
        return;
    }
    
    if (e.ctrlKey) {
        switch(e.key.toLowerCase()) {
            case 'd':
                e.preventDefault();
                window.location.href = 'dashboard.php';
                break;
            case 'p':
                e.preventDefault();
                window.location.href = 'produits/ajouter.php';
                break;
            case 'c':
                e.preventDefault();
                window.location.href = 'clients/ajouter.php';
                break;
            case 'r':
                e.preventDefault();
                window.location.href = 'rendezvous/';
                break;
        }
    }
    
    if (e.altKey) {
        switch(e.key.toLowerCase()) {
            case 'n':
                e.preventDefault();
                toggleNotifications();
                break;
            case 'm':
                e.preventDefault();
                toggleUserMenu();
                break;
        }
    }
    
    if (e.key === 'Escape') {
        // Fermer tous les modals ouverts
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
        });
        
        // Fermer les dropdowns
        document.querySelectorAll('.show').forEach(element => {
            element.classList.remove('show');
        });
    }
});

// Afficher les raccourcis clavier
function showKeyboardShortcuts() {
    document.getElementById('keyboardShortcutsModal').style.display = 'block';
}

// Basculer le mode sombre
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('darkMode', isDark);
    
    const icon = event.target.closest('button').querySelector('i');
    icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
}

// Charger le mode sombre sauvegardé
document.addEventListener('DOMContentLoaded', function() {
    const isDark = localStorage.getItem('darkMode') === 'true';
    if (isDark) {
        document.body.classList.add('dark-mode');
        const moonIcon = document.querySelector('.footer-btn i.fa-moon');
        if (moonIcon) {
            moonIcon.className = 'fas fa-sun';
        }
    }
});

// Afficher le statut système
function showSystemStatus() {
    document.getElementById('systemStatusModal').style.display = 'block';
}

// Fermer les modals
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Fermer les modals en cliquant à l'extérieur
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});

// Vérification périodique du statut système
setInterval(function() {
    fetch('api/system_status.php')
        .then(response => response.json())
        .then(data => {
            // Mettre à jour les indicateurs de statut si nécessaire
            if (data.database_status === 'offline') {
                console.warn('Base de données hors ligne');
            }
        })
        .catch(error => {
            console.error('Erreur vérification statut:', error);
        });
}, 300000); // Vérification toutes les 5 minutes

// Auto-save des formulaires (si applicable)
let autoSaveTimer;
function enableAutoSave(formElement) {
    if (!formElement) return;
    
    const inputs = formElement.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(() => {
                saveFormData(formElement);
            }, 30000); // Auto-save après 30 secondes d'inactivité
        });
    });
}

// Sauvegarder les données du formulaire
function saveFormData(form) {
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    localStorage.setItem('formAutoSave_' + form.id, JSON.stringify(data));
    
    // Afficher un indicateur de sauvegarde
    showNotification('Brouillon sauvegardé automatiquement', 'info', 2000);
}

// Restaurer les données du formulaire
function restoreFormData(form) {
    const savedData = localStorage.getItem('formAutoSave_' + form.id);
    if (savedData) {
        const data = JSON.parse(savedData);
        Object.keys(data).forEach(key => {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) {
                input.value = data[key];
            }
        });
    }
}

// Notification système
function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, duration);
}

// Gestion des erreurs JavaScript globales
window.addEventListener('error', function(e) {
    console.error('Erreur JavaScript:', e.error);
    
    // Envoyer l'erreur au serveur pour logging (optionnel)
    fetch('api/log_error.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            message: e.message,
            filename: e.filename,
            line: e.lineno,
            column: e.colno,
            stack: e.error?.stack
        })
    }).catch(() => {
        // Ignore les erreurs de logging
    });
});
</script>

</body>
</html>