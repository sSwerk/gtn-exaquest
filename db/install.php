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

require_once __DIR__ . '/../inc.php';

use core_customfield\field_controller;

// called when installing a plugin
function xmldb_block_exaquest_install() {
    global $DB;

    // TODO: only do it once, if those fields do not exist yet
    // TODO: this is just a test, remove or finish

    $handler = qbank_customfields\customfield\question_handler::create();
    $c1id = $handler->create_category();
    $c1 = $handler->get_categories_with_fields()[$c1id];
    $handler->rename_category($c1, 'JKUfields2');
    $record = new stdClass();
    $record->name = 'JKUField';
    $record->shortname = "JKU";
    $record->type = 'checkbox';
    $record->sortorder = 0;
    $configdata = [];
    $configdata += [
        'required' => 0,
        'uniquevalues' => 0,
        'locked' => 0,
        'visibility' => 2,
        'defaultvalue' => '',
        'displaysize' => 0,
        'maxlength' => 0,
        'ispassword' => 0,
        'link' => '',
        'linktarget' => '',
        'checkbydefault' => 0,
        'startyear' => 2000,
        'endyear' => 3000,
        'includetime' => 1,
    ];
    $record->configdata = json_encode($configdata);
    $field = field_controller::create(0, (object) ['type' => $record->type], $c1);
    $handler->save_field_configuration($field, $record);

    // Creating roles and assigning capabilities
    // Done as a task AFTER the installation, because the capabilities only exist at the end/after the installation.
    // create the instance
    $setuptask = new \block_exaquest\task\set_up_roles();
    // queue it
    \core\task\manager::queue_adhoc_task($setuptask);
}
