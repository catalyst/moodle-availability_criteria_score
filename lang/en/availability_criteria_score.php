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
 * Language strings.
 *
 * @package     availability_criteria_score
 * @author      Alex Morris <alex.morris@catalyst.net.nz>
 * @copyright   Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Restriction by criteria score';
$string['privacy:metadata'] = 'The Restriction by criteria score plugin does not store any personal data.';
$string['description'] = 'Require students to achieve a specified criteria score.';
$string['title'] = 'Criteria Score';
$string['requires_criteria_both'] = 'Requires score greater than or equal to <b>{$a->min}</b> and less than <b>{$a->max}</b> in <b>{$a->criteria}</b> from <b>{$a->activity}</b>';
$string['requires_criteria_greater'] = 'Requires score greater than or equal to <b>{$a->min}</b> in <b>{$a->criteria}</b> from <b>{$a->activity}</b>';
$string['requires_criteria_less'] = 'Requires score less than <b>{$a->max}</b> in <b>{$a->criteria}</b> from <b>{$a->activity}</b>';
$string['choosescore'] = 'Choose score';
$string['choosecriteria'] = 'Choose criteria';
$string['error_loading_requirements'] = 'Error with criteria score restriction';
$string['label_min'] = 'Minimum grade percentage (inclusive)';
$string['label_max'] = 'Maximum grade percentage (exclusive)';
$string['option_min'] = 'must be &#x2265;';
$string['option_max'] = 'must be <';
