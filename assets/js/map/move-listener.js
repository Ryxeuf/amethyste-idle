// Configuration du client Mercure - URL lue depuis data-mercure-url sur le body (injectée par Twig)
const mercureUrl = document.body.dataset.mercureUrl;
if (!mercureUrl) {
    console.warn('Mercure: data-mercure-url non défini sur body, client Mercure désactivé.');
} else {
    const url = new URL(mercureUrl);
    url.searchParams.append('topic', 'map/move');

    // Création de l'EventSource pour se connecter au hub Mercure
    const eventSource = new EventSource(url);

    // Gestion des messages reçus
    eventSource.onmessage = (event) => {
        const data = JSON.parse(event.data);
        console.log('Message reçu:', data);
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
        console.log(`Objet de type ${type} (ID: ${object}) déplacé vers ${coordinates}`);
        if (type === 'player') {
            const moveButton = document.getElementById('move-player');
            moveButton.dataset.liveXParam = x;
            moveButton.dataset.liveYParam = y;
            moveButton.click();
        }
    }
} 