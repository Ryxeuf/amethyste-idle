<?php

namespace App\GameEngine\GameMaster;

/**
 * Un maitre du jeu a tente un geste que son statut lui interdit.
 *
 * Distincte des refus ordinaires : ce n'est ni une sanction (ECO-16b), ni un
 * manque de ressources, ni une regle de jeu. C'est la contrepartie du statut,
 * et le message doit le dire — un MJ qui lit « fonds insuffisants » chercherait
 * un bug la ou il n'y en a pas.
 *
 * Elle etend `InvalidArgumentException` **a dessein** : c'est le refus que les
 * gestionnaires de commerce levent deja, et que tous les ecrans savent
 * rattraper. Un type entierement neuf aurait traverse les `catch` existants
 * pour finir en erreur 500 — la restriction serait devenue une panne.
 */
class GameMasterRestrictionException extends \InvalidArgumentException
{
}
