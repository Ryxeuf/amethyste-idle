// Configuration du client Mercure
const url = new URL('https://amethyste-idle.local:8243/.well-known/mercure');
url.searchParams.append('topic', 'map/move');

// Création de l'EventSource pour se connecter au hub Mercure
const eventSource = new EventSource(url);

// Gestion des messages reçus
eventSource.onmessage = (event) => {
    const data = JSON.parse(event.data);
    console.log('Message reçu:', data);
    
    // Vous pouvez ajouter ici votre logique pour traiter les données
    // Par exemple, mettre à jour la position d'un objet sur la carte
    handleMove(data);
};

// Gestion des erreurs
eventSource.onerror = (error) => {
    console.error('Erreur de connexion Mercure:', error);
    eventSource.close();
};

eventSource.onopen = () => {
    console.log('Connexion Mercure établie');
};

// Fonction pour traiter les mouvements
function handleMove(data) {
    const { type, object, x, y, coordinates, data: additionalData } = data;
    
    // Exemple de traitement
    console.log(`Objet de type ${type} (ID: ${object}) déplacé vers ${coordinates}`);
    console.log('Données supplémentaires:', additionalData);
    
    // TODO: Implémenter votre logique de mise à jour de la carte ici
    if (type === 'player') {
        const moveButton = document.getElementById('move-player');
        moveButton.dataset.liveXParam = x;
        moveButton.dataset.liveYParam = y;
        moveButton.click();
    }
} 