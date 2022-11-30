YUI.add('moodle-availability_criteria_score-form', function (Y, NAME) {

/**
 * JavaScript for form editing criterion conditions.
 *
 * @module moodle-availability_criteria_score-form
 */
M.availability_criteria_score = M.availability_criteria_score || {};

/**
 * @class M.availability_criteria_score.form
 * @extends M.core_availability.plugin
 */
M.availability_criteria_score.form = Y.Object(M.core_availability.plugin);

/**
 * scales available for selection (alphabetical order).
 *
 * @property criterions
 * @type Array
 */
M.availability_criteria_score.form.gradeitems = null;

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} gradeitems Array of objects
 */
M.availability_criteria_score.form.initInner = function(gradeitems) {
    this.gradeitems = gradeitems;
};

M.availability_criteria_score.form.getNode = function(json) {
    // Create HTML structure for grade item selection.
    var html = '<label class="form-group"><span class="p-r-1">Grade Item</span> ' +
        '<span class="availability-criterion">' +
        '<select name="gradeitemid" class="custom-select"><option value="0">' +
        M.util.get_string('choosedots', 'moodle') + '</option>';
    for (var i = 0; i < this.gradeitems.length; i++) {
        var grade = this.gradeitems[i];
        // String has already been escaped using format_string.
        html += '<option value="' + grade.id + '">' + grade.name + '</option>';
    }
    html += '</select></span></label>';
    // Structure for criterion.
    html += '<label><span class="p-r-1">' + M.util.get_string('choosecriteria', 'availability_criteria_score') + '</span> ' +
        '<span class="availability-criteria_score">' +
        '<select name="criterion" class="custom-select">' +
        '<option value="choose">' + M.util.get_string('choosedots', 'moodle') + '</option></select></span></label>';
    // Structure for score.
    html += '<br><span class="availability-group form-group">' +
        // Minimum
        '<label><input type="checkbox" class="form-check-input mx-1" name="min" />' +
        M.util.get_string('option_min', 'availability_criteria_score') + '</label>' +
        '<label><span class="accesshide">' + M.util.get_string('label_min', 'availability_criteria_score') +
        '</span><input type="text" class="form-control mx-1" name="minval" title="' +
        M.util.get_string('label_min', 'availability_criteria_score') + '"></label>' +
        // Max
        '<label><input type="checkbox" class="form-check-input mx-1" name="max" />' +
        M.util.get_string('option_max', 'availability_criteria_score') + '</label>' +
        '<label><span class="accesshide">' + M.util.get_string('label_max', 'availability_criteria_score') +
        '</span><input type="text" class="form-control mx-1" name="maxval" title="' +
        M.util.get_string('label_max', 'availability_criteria_score') + '"></label>' +
        '</span>';
    var node = Y.Node.create('<span class="form-inline">' + html + '</span>');

    // Set initial values (leave default 'choose' if creating afresh).
    if (json.gradeitemid !== undefined &&
        node.one('select[name=gradeitemid] > option[value=' + json.gradeitemid + ']')) {
        node.one('select[name=gradeitemid]').set('value', '' + json.gradeitemid);
        M.availability_criteria_score.fillCriterion(node);
    } else if (json.gradeitemid === undefined) {
        node.one('select[name=gradeitemid]').set('value', 'choose');
    }
    if (json.criterion !== undefined &&
        node.one('select[name=criterion] > option[value=' + json.criterion + ']')) {
        node.one('select[name=criterion]').set('value', '' + json.criterion);
        M.availability_criteria_score.unlockScores(node);
    } else if (json.criterion === undefined) {
        node.one('select[name=criterion]').set('value', 'choose');
    }
    if (json.min !== undefined) {
        node.one('input[name=min]').set('checked', true);
        node.one('input[name=minval]').set('value', json.min);
    }
    if (json.max !== undefined) {
        node.one('input[name=max]').set('checked', true);
        node.one('input[name=maxval]').set('value', json.max);
    }

    // Disables/enables text input fields depending on checkbox.
    var updateCheckbox = function(check, focus) {
        var input = check.ancestor('label').next('label').one('input');
        var checked = check.get('checked');
        input.set('disabled', !checked);
        if (focus && checked) {
            input.focus();
        }
        return checked;
    };
    node.all('input[type=checkbox]').each(updateCheckbox);

    // Add event handlers (first time only).
    if (!M.availability_criteria_score.form.addedEvents) {
        M.availability_criteria_score.form.addedEvents = true;

        var root = Y.one('.availability-field');

        node.one('select[name=gradeitemid]').delegate('change', function() {
            M.availability_criteria_score.fillCriterion(node);
            M.core_availability.form.update();
        }, '.availability_criteria_score select');

        node.one('select[name=criterion]').delegate('change', function() {
            M.availability_criteria_score.unlockScores(node);
            M.core_availability.form.update();
        }, '.availability_criteria_score select');

        root.delegate('click', function() {
            updateCheckbox(this, true);
            M.core_availability.form.update();
        }, '.availability_criteria_score input[type=checkbox]');

        root.delegate('valuechange', function() {
            // For grade values, just update the form fields.
            M.core_availability.form.update();
        }, '.availability_criteria_score input[type=text]');
    }

    return node;
};

M.availability_criteria_score.form.fillValue = function(value, node) {
    var selected = node.one('select[name=gradeitemid]').get('value');
    if (selected === 'choose') {
        value.gradeitemid = null;
    } else {
        value.gradeitemid = parseInt(selected, 10);
    }
    var selectedcriteria = node.one('select[name=criterion]').get('value');
    if (selectedcriteria === 'choose') {
        value.criterion = null;
    } else {
        value.criterion = parseInt(selectedcriteria, 10);
    }
    if (node.one('input[name=min]').get('checked')) {
        value.min = this.getValue('minval', node);
    }
    if (node.one('input[name=max]').get('checked')) {
        value.max = this.getValue('maxval', node);
    }
};

/**
 * Gets the numeric value of an input field. Supports decimal points (using
 * dot or comma).
 *
 * @method getValue
 * @return {Number|String} Value of field as number or string if not valid
 */
M.availability_criteria_score.form.getValue = function(field, node) {
    // Get field value.
    var value = node.one('input[name=' + field + ']').get('value');

    // If it is not a valid positive number, return false.
    if (!(/^[0-9]+([.,][0-9]+)?$/.test(value))) {
        return value;
    }

    // Replace comma with dot and parse as floating-point.
    var result = parseFloat(value.replace(',', '.'));
    if (result < 0 || result > 100) {
        return value;
    } else {
        return result;
    }
};

M.availability_criteria_score.form.fillErrors = function(errors, node) {
    var value = {};
    this.fillValue(value, node);

    if ((value.gradeitemid && value.gradeitemid === 'choose') ||
        (value.criterion && value.criterion === 'choose')) {
        errors.push('availability_criteria_score:error_selectcriterion');
    }
};

M.availability_criteria_score.fillCriterion = function(node) {
    var selected = node.one('select[name=gradeitemid]').get('value');
    if (selected !== 'choose') {
        var gradeitemid = parseInt(selected, 10);
        var finalgradeitem = null;
        for (var i = 0; i < M.availability_criteria_score.form.gradeitems.length; i++) {
            var gradeitem = M.availability_criteria_score.form.gradeitems[i];
            if (gradeitem.id == gradeitemid) {
                finalgradeitem = gradeitem;
                break;
            }
        }
        if (finalgradeitem !== null) {
            var criterionselect = node.one('select[name=criterion]');
            var domnode = criterionselect.getDOMNode();

            for (var j = domnode.options.length - 1; j >= 0; j--) {
                domnode.remove(j);
            }

            var chooseoption = document.createElement('option');
            chooseoption.value = 'choose';
            chooseoption.text = M.util.get_string('choosedots', 'moodle');
            domnode.add(chooseoption);

            for (var k = 0; k < finalgradeitem.criteria.length; k++) {
                var criterion = finalgradeitem.criteria[k];
                var option = document.createElement('option');
                option.value = criterion.id;
                option.text = criterion.shortname;
                domnode.add(option);
            }
        }
    }
};

M.availability_criteria_score.unlockScores = function(node) {

};


}, '@VERSION@', {"requires": ["base", "node", "event", "moodle-core_availability-form"]});
