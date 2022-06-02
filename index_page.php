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
global $CFG, $PAGE, $COURSE;
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$courseid=2;
require_login($courseid);
$context = context_course::instance($courseid);

// Set up the page.
$title = get_string('dashboard', 'block_exaquest');
$pagetitle = $title;
$url = new moodle_url('/blocks/exaquest/index_page.php');
$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($title);

$output = $PAGE->get_renderer('block_exaquest');

echo $output->header();
echo $output->heading($pagetitle);

$renderable = new \block_exaquest\output\index_page('asdfasdf');
echo $output->render($renderable);

echo $output->footer();