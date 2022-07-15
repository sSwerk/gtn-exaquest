#!/bin/bash
#
# Exaquest similarity comparison extension
#
# @package    block_exaquest
# @copyright  2022 Stefan Swerk
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
#

EXAQUEST_PLUGIN_DIR="$(pwd)"
VERSION002="https://gitea.swerk.priv.at/stefan/-/packages/composer/stefan%2Fgtn_jku_similarity_comparison/0.0.2/files/5"

cd "$EXAQUEST_PLUGIN_DIR"

# download the zip
mkdir similarity_comparison
curl "$VERSION002" --output gtn-jku-similarity-comparison.zip
unzip -d similarity_comparison/ gtn-jku-similarity-comparison.zip
rm gtn-jku-similarity-comparison.zip

#(alternative to above) use latest dev version
# git clone https://gitea.swerk.priv.at/stefan/gtn-jku-similarity-comparison similarity_comparison


# run composer update
cd similarity_comparison
composer update
