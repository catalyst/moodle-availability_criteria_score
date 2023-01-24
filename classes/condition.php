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

namespace availability_criteria_score;

use core_availability\info;
use gradingform_instance;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/grading/lib.php');
require_once($CFG->dirroot . '/grade/grading/form/lib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * Condition on criterion grades of current user.
 *
 * @package     availability_criteria_score
 * @author      Alex Morris <alex.morris@catalyst.net.nz>
 * @copyright   Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {

    /**
     * @var int Grade item ID
     */
    protected $gradeitemid;
    /**
     * @var int Criterion ID
     */
    protected $criterion;
    /**
     * @var int|null Score minimum
     */
    protected $min;
    /**
     * @var int|null Score maximum
     */
    protected $max;

    /**
     * Condition constructor.
     *
     * @param \stdClass $structure
     */
    public function __construct($structure) {
        if (isset($structure->gradeitemid) && is_int($structure->gradeitemid)) {
            $this->gradeitemid = $structure->gradeitemid;
        } else {
            throw new \coding_exception('Invalid ->gradeitemid for criteria condition');
        }
        if (isset($structure->criterion) && is_int($structure->criterion)) {
            $this->criterion = $structure->criterion;
        } else {
            throw new \coding_exception('Invalid ->criterion for criteria condition');
        }
        if (isset($structure->min) && is_int($structure->min)) {
            $this->min = $structure->min;
        } else {
            $this->min = null;
        }
        if (isset($structure->max) && is_int($structure->max)) {
            $this->max = $structure->max;
        } else {
            $this->max = null;
        }
    }

    /**
     * Checks if the item is available, determined by whether the given user has the appropriate score
     * awarded in the grade item criterion.
     *
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we are checking
     * @param bool $grabthelot
     * @param int $userid User ID to check availability for
     * @return bool True if available
     */
    public function is_available($not, info $info, $grabthelot, $userid) {
        global $DB;

        $gradeitem = \grade_item::fetch(['id' => $this->gradeitemid]);
        $cm = get_coursemodule_from_instance($gradeitem->itemmodule, $gradeitem->iteminstance, $gradeitem->courseid);
        if ($cm == null) {
            return false;
        }
        $context = \context_module::instance($cm->id);

        $criteria = $DB->get_record('gradingform_guide_criteria', ['id' => $this->criterion]);
        if ($criteria == null) {
            return false;
        }

        $assign = new \assign($context, $cm, false);
        $usergrade = $assign->get_user_grade($userid, false);
        if (!$usergrade) {
            return false;
        }

        $gradinginstance = $DB->get_record('grading_instances', array('definitionid'  => $criteria->definitionid,
            'itemid' => $usergrade->id,
            'status'  => \gradingform_instance::INSTANCE_STATUS_ACTIVE));
        if ($gradinginstance == null) {
            return false;
        }

        $filling = $DB->get_record('gradingform_guide_fillings',
            array('instanceid' => $gradinginstance->id, 'criterionid' => $criteria->id));
        if ($filling == null) {
            return false;
        }

        $allow = $filling->score !== false &&
            (is_null($this->min) || $filling->score >= $this->min) &&
            (is_null($this->max) || $filling->score < $this->max);
        if ($not) {
            $allow = !$allow;
        }

        return $allow;
    }

    /**
     * Returns a string describing the restriction.
     *
     * @param bool $full Set true if this is the 'full information' view
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we are checking
     * @return \lang_string|string
     */
    public function get_description($full, $not, info $info) {
        global $DB;

        $gradeitem = \grade_item::fetch(['id' => $this->gradeitemid]);
        $cm = get_coursemodule_from_instance($gradeitem->itemmodule, $gradeitem->iteminstance, $gradeitem->courseid);
        if ($cm == null) {
            return get_string('error_loading_requirements', 'availability_criteria_score');
        }

        $criteria = $DB->get_record('gradingform_guide_criteria', ['id' => $this->criterion]);
        if ($criteria == null) {
            return get_string('error_loading_requirements', 'availability_criteria_score');
        }

        $inf = new \stdClass();
        $inf->activity = $cm->name;
        $inf->min = $this->min;
        $inf->max = $this->max;
        $inf->criteria = $criteria->shortname;

        if (!is_null($this->min) && !is_null($this->max)) {
            return get_string('requires_criteria_both', 'availability_criteria_score', $inf);
        } else if (!is_null($this->min)) {
            return get_string('requires_criteria_greater', 'availability_criteria_score', $inf);
        } else if (!is_null($this->max)) {
            return get_string('requires_criteria_less', 'availability_criteria_score', $inf);
        }

        return get_string('error_loading_requirements', 'availability_criteria_score');
    }

    /**
     * Returns the settings of this condition as a string for debugging.
     *
     * @return string
     */
    protected function get_debug_string() {
        return $this->gradeitemid . '#' . $this->criterion . '-' . $this->min . '-' . $this->max;
    }

    /**
     * Saves condition settings to a structure object.
     *
     * @return \stdClass Structure object
     */
    public function save() {
        $result = (object)array('type' => 'criterion');
        if ($this->gradeitemid) {
            $result->gradeitemid = $this->gradeitemid;
        }
        if ($this->criterion) {
            $result->criterion = $this->criterion;
        }
        if ($this->min) {
            $result->min = $this->min;
        }
        if ($this->max) {
            $result->max = $this->max;
        }
        return $result;
    }
}
