<?php
// This file is part of Exabis Student Review
//
// (c) 2016 GTN - Global Training Network GmbH <office@gtn-solutions.com>
//
// Exabis Student Review is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This script is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You can find the GNU General Public License at <http://www.gnu.org/licenses/>.
//
// This copyright notice MUST APPEAR in all copies of the script!

require_once __DIR__ . '/../inc.php';

use \core_customfield\field_controller;

// called when installing a plugin
function xmldb_block_exaquest_install() {
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
    $field = field_controller::create(0, (object)['type' => $record->type], $c1);
    $handler->save_field_configuration($field, $record);

}
