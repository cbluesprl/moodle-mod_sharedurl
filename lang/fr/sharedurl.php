<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'sharedurl', language 'fr'
 *
 * @package    mod_sharedurl
 * @copyright  2021 CBlue SPRL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'URL partagée';
$string['modulename'] = 'URL partagée';
$string['modulename_link'] = 'mod/sharedurl/view';
$string['modulename_help'] = 'Le module "URL partagée" permet à un enseignant de créer un lien vers l\'activité d\'un autre cours.
                                    Il fonctionne avec le plugin d\'inscription "enrol_shared" qui permet d\'inscrire automatiquement, et pour une période donnée, 
                                    un utilisateur dans le cours ciblé.';
$string['pluginadministration'] = 'Shared URL administration';

$string['externalurl'] = 'URL de l\'activité';
$string['invalidstoredurl'] = 'Impossible d\'afficher la page. L\'URL doit rediriger vers une activité existante de cette plateforme moodle';
$string['modulename'] = 'URL partagée';
$string['modulenameplural'] = 'URLs partagées';

// Capabilities
$string['sharedurl:addinstance'] = 'Ajouter une nouvelle activité "SharedURL"';
$string['sharedurl:view'] = 'Afficher une activité "SharedURL"';

$string['clicktoopen'] = 'Cliquez sur {$a} pour ouvrir la ressource.';
$string['configdisplayoptions'] = 'Sélectionner toutes les options qui devraient être disponibles, les options existantes ne seront pas modifiées. Pressez la touche CTRL pour sélectionner plusieurs valeurs';
$string['configframesize'] = 'Quand une page web est affichée à l\'intérieur d\'une fenêtre, cette valeur est la hauteur (en pixels)';
$string['contentheader'] = 'Contenu';
$string['createurl'] = 'Créer une URL partagée';
$string['displayoptions'] = 'Options d\'affichage disponibles';
$string['displayselect'] = 'Affichage';
$string['displayselect_help'] = 'Ce paramètre, ainsi que le type de fichier de l\'URL partagée et le fait que le navigateur autorise ou non l\'intégration, déterminent la manière dont l\'URL est affichée. Les options peuvent inclure :

* Automatique - La meilleure option d\'affichage de l\'URL partagée est sélectionnée automatiquement
* Intégrée - L\'URL partagé est affiché dans la page sous la barre de navigation avec la description de l\'URL et les blocs éventuels
* Ouvrir - Seule l\'URL partagée est affichée dans la fenêtre du navigateur
* En pop-up - L\'URL partagée est affichée dans une nouvelle fenêtre du navigateur sans menu ni barre d\'adresse
* Dans le cadre - L\'URL partagée est affichée dans un cadre sous la barre de navigation et la description de l\'URL partagée
* Nouvelle fenêtre - L\'URL partagée est affichée dans une nouvelle fenêtre du navigateur avec des menus et une barre d\'adresse';

$string['displayselectexplain'] = 'Choisir un type d\'affichage, malheureusement tout les type ne sont pas adapté à toutes les URLs partagées.';
$string['externalurl'] = 'URL partagée externe';
$string['framesize'] = 'Hauteur de la fenêtre';
$string['printintro'] = 'Afficher la description de l\'URL partagée';
$string['printintroexplain'] = 'Affiche la description de l\'URL partagée sous le contenu ? Certains types d\'affichage ne peuvent pas afficher la desription même si elle est activée.';
$string['popupheight'] = 'Hauteur de la pop-up (en pixels)';
$string['popupheightexplain'] = 'Défini la hauteur par défaut de la pop-up.';
$string['popupwidth'] = 'Largeur de la pop-up (en pixels)';
$string['popupwidthexplain'] = 'Défini la largeur par défaut de la pop-up.';
$string['privacy:metadata'] = 'Le plugin shared url ne conserve aucune donnée personnelle.';
