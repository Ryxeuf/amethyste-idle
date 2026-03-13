Reinitialiser la base de donnees et recharger toutes les fixtures.

⚠️ ATTENTION : cette operation est DESTRUCTIVE. Elle supprime TOUTES les donnees de la base de donnees et recharge les donnees de test.

Demander TOUJOURS confirmation a l'utilisateur avant de proceder.

Si confirme, executer :
```bash
cd /var/www/amethyste-idle && ./scripts/reload-fixtures.sh --dev
```

Ce script :
1. Supprime le schema de la base
2. Recree le schema
3. Corrige les sequences PostgreSQL
4. Charge les fixtures (dans un conteneur temporaire avec les deps dev)
5. Restaure les dependances prod dans le conteneur principal

Rapporter le resultat de chaque etape.
