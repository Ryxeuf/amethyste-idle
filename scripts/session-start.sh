#!/usr/bin/env bash
# =============================================================================
# Amorce d'une session Claude Code — rendre `docker compose` utilisable
# =============================================================================
# La regle #1 de CLAUDE.md impose que toute commande PHP/Composer passe par
# Docker, et un hook `PreToolUse` la fait respecter. Une session ou le demon ne
# tourne pas ne peut donc **ni lancer les tests, ni PHPStan, ni le linter** :
# elle ecrit du code et decouvre ses erreurs en CI, dix minutes plus tard.
#
# ## Ce que ce script repare, et ce qu'il ne contourne pas
#
# Dans les sessions web, `dockerd` et `containerd` sont installes et le demon
# fonctionne parfaitement — c'est **`/etc/init.d/docker` qui echoue**, sur un
# appel a `ulimit` qui exige `CAP_SYS_RESOURCE`, la seule capacite que le bac a
# sable retire. Lancer `dockerd` directement contourne un script d'init, jamais
# une protection : le bac a sable garde exactement les memes bornes.
#
# ## Trois regles de conduite
#
#  1. **Ne jamais bloquer la session.** Un amorcage qui echoue doit degrader
#     vers « pas de Docker », pas tuer le demarrage — d'ou le `exit 0` partout.
#  2. **Etre idempotent.** Sur une machine de developpement ou le demon repond
#     deja, ce script ne fait rien.
#  3. **Rendre la main vite.** Le demon se leve en quelques secondes ; tirer les
#     images et installer les dependances prend des minutes, donc ca part en
#     tache de fond et l'agent sait ou regarder.
# =============================================================================
set -u

BOOTSTRAP_LOG="${TMPDIR:-/tmp}/amethyste-bootstrap.log"

say() { printf '%s\n' "$*"; }

# --- 1. Le demon repond deja : il n'y a rien a faire -------------------------
if docker info >/dev/null 2>&1; then
    say "Docker est deja disponible."
else
    # --- 2. Pas de Docker du tout : on ne bloque pas -------------------------
    if ! command -v dockerd >/dev/null 2>&1; then
        say "Docker n'est pas installe dans cet environnement : les commandes PHP/Composer ne seront pas disponibles."
        exit 0
    fi

    # --- 3. Le demon est la mais ne tourne pas : cas des sessions web --------
    # `sudo -n` : jamais d'invite de mot de passe. Un amorcage qui attendrait
    # une saisie humaine bloquerait la session pour de bon.
    if ! sudo -n true >/dev/null 2>&1; then
        say "Docker est installe mais le demon ne tourne pas, et sudo n'est pas disponible sans mot de passe."
        exit 0
    fi

    say "Demarrage du demon Docker (le script d'init echoue sur un ulimit, voir l'en-tete)."
    sudo -n dockerd >"${TMPDIR:-/tmp}/dockerd.log" 2>&1 &

    # Attendre le socket : sans cette boucle, la premiere commande `docker` de
    # la session peut arriver avant lui, et l'agent conclura que Docker est
    # indisponible alors qu'il se levait.
    for _ in $(seq 1 30); do
        docker info >/dev/null 2>&1 && break
        sleep 1
    done

    if ! docker info >/dev/null 2>&1; then
        say "Le demon Docker n'a pas demarre. Voir ${TMPDIR:-/tmp}/dockerd.log."
        exit 0
    fi

    say "Demon Docker pret."
fi

# --- 4. La stack du projet, en tache de fond ---------------------------------
# **On tire l'image, on ne la construit pas.** Dans une session web, l'egress
# passe par un proxy qui re-termine TLS : le demon tourne sur l'hote et connait
# son CA, mais chaque etape `RUN` d'un build s'execute dans son propre
# conteneur, qui ne le connait pas — `install-php-extensions` echoue alors sur
# `curl: (60) self-signed certificate in certificate chain`. Les *pulls*, eux,
# passent. D'ou l'image `:dev` publiee par `release.yml`.
if [ -n "${AMETHYSTE_SKIP_BOOTSTRAP:-}" ]; then
    say "Amorcage de la stack saute (AMETHYSTE_SKIP_BOOTSTRAP)."
    exit 0
fi

# Le prefixe d'image se **derive du remote** plutot que de s'ecrire : un fork
# ou un renommage du depot ne doit pas laisser le script tirer l'image de
# quelqu'un d'autre. `IMAGES_PREFIX` deja pose dans l'environnement gagne.
if [ -z "${IMAGES_PREFIX:-}" ]; then
    origin=$(git config --get remote.origin.url 2>/dev/null || true)
    slug=$(printf '%s' "$origin" | sed -E 's#.*github\.com[:/]##; s#\.git$##' | tr '[:upper:]' '[:lower:]')
    [ -n "$slug" ] && export IMAGES_PREFIX="ghcr.io/${slug}-"
fi

(
    {
        echo "=== $(date -u +%FT%TZ) amorcage de la stack ==="
        echo "IMAGES_PREFIX=${IMAGES_PREFIX:-<vide>}"
        # Tirer plutot que construire (voir plus haut). Un echec n'est pas
        # fatal en soi — sur une machine de developpement, l'image locale fait
        # foi et `up` la reconstruira tres bien.
        if docker compose pull php; then
            docker compose up -d database php
        else
            echo "L'image de developpement n'a pas pu etre tiree."
            echo "Sur une machine de dev, c'est sans consequence : on construit."
            echo "Dans une session web, la construction ECHOUE — l'egress passe"
            echo "par un proxy qui re-termine TLS et les etapes de build ne"
            echo "connaissent pas son CA (curl: (60) self-signed certificate)."
            echo "Publier l'image via release.yml est ce qui debloque ce cas."
            # La base de donnees, elle, se tire toujours : autant l'avoir.
            docker compose up -d database
            docker compose up -d php || echo "service php indisponible : les commandes PHP resteront hors de portee"
        fi
        # `composer install` seulement si besoin : sur une machine de dev, le
        # dossier est deja la et le refaire couterait des minutes pour rien.
        if [ ! -d vendor ]; then
            docker compose exec -T php composer install --no-interaction --no-progress
        fi
        echo "=== pret ==="
    } >>"$BOOTSTRAP_LOG" 2>&1
) &

say "Stack en cours d'amorcage en tache de fond — journal : $BOOTSTRAP_LOG"
say "Avant de lancer les tests, verifier que le journal se termine par « pret »."

exit 0
