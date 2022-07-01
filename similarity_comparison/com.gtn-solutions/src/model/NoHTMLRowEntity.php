<?php
/*
 * Copyright (c) 2022 Stefan Swerk
 * All rights reserved.
 *
 * Unless required by applicable law or agreed to in writing, software is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 */
namespace GTN\model;

class NoHTMLRowEntity extends RawRowEntity {

    /**
     * Removes HTML tags/formatting from question-text before setting the variable
     *
     * @param string $text
     * @return void
     */
    public function setText(string $text): void {
        $textWithoutTags = strip_tags($text);
        $textWithoutSpecialChars = htmlentities($textWithoutTags, ENT_QUOTES | ENT_IGNORE, "UTF-8");

        parent::setText($textWithoutSpecialChars);
    }

}