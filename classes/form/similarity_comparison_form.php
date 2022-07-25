<?php
/*
 * Exaquest similarity comparison extension
 *
 * @package    block_exaquest
 * @copyright  2022 Stefan Swerk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $DB, $CFG, $COURSE, $PAGE, $OUTPUT, $USER;
require_once($CFG->libdir . '/formslib.php');

class similaritycomparison_form extends moodleform {


    /**
     * @inheritDoc
     */
    protected function definition() {
        $mform = $this->_form;

        /*
        $mform->addElement('text','email','email');
        $mform->setType('email', PARAM_NOTAGS);
        $mform->setDefault('email','Please enter email');

        $mform->addElement('button', 'show', 'Show');
        */

        $similarityButtonGroup = array();
        $similarityButtonGroup[] = $mform->createElement('submit', 'showSimilarityOverviewButton', 'Refresh Similarity Overview');
        $similarityButtonGroup[] = $mform->createElement('submit', 'computeSimilarityButton', 'Compute Similarity');
        $similarityButtonGroup[] = $mform->createElement('submit', 'computeSimilarityStoreButton', 'Compute and persist Similarity');

        $mform->addGroup($similarityButtonGroup, 'similarityButtonGroup', '', ' ', false);
        //$mform->addElement('checkbox', 'ratingtime', 'rating');

        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);
        $mform->setType('courseid', PARAM_INT);
        //$this->add_action_buttons();
    }

    public function validation($data, $files) {
        return parent::validation($data, $files);
    }
}