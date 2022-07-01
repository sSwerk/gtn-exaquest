<?php
/*
 * Copyright (c) 2022 Stefan Swerk
 * All rights reserved.
 *
 * Unless required by applicable law or agreed to in writing, software is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 */
namespace GTN\model;

use GTN\Logger;
use GTN\model\adapter\RawRowEntityAdapter;

/**
 * Extension of a RawRowEntity that indicates that this row represents an exam answer.
 * The only disallowed parent is '0' or null, since an answer must have an existing  parent, contrary to their question TODO: verify this assumption
 */
class AnswerRowEntity extends RawRowEntityAdapter {

    public function __construct(?RawRowEntity $adapt) {
        if(isset($adapt)) {
            parent::__construct($adapt);
        } else {
            parent::__construct($this);
        }
    }

    public function setParentEntity(?RawRowEntity $parentEntity): void {
        if(isset($parentEntity) && ($parentEntity->getId() === null || $parentEntity->getId() === 0)) {
            Logger::error("It is not allowed to set no or an invalid parent entity for an answer without a valid parent",
                    ['thisId' => $this->getId(), 'parentId' => $parentEntity?->getId() ]);
        } else {
            parent::setParentEntity($parentEntity);
        }
    }
}