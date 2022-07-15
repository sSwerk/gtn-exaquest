<?php
/*
 * Copyright (c) 2022 Stefan Swerk
 * All rights reserved.
 *
 * Unless required by applicable law or agreed to in writing, software is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 */
namespace GTN\model;

use DateTimeInterface;

class RawRowEntity {
    private int $id;
    private int $category;
    private ?RawRowEntity $parentEntity;
    private string $name;
    private string $text;
    private int $format; // may be empty?
    private float $defaultmark;
    private float $penalty;
    // values known so far:
    // multianswer, shortanswer, multichoice, gapselect, truefalse, numerical, match, essay, random,
    // ddwtos, calculated, ddimageortext, ddmatch, description, gapfill, ddmarker, ordering,
    private string $qtype; // TODO: replace with enum with PHP >8.1, may be empty?
    private int $length;
    private string $stamp;
    private string $version;
    private bool $hidden;

    private DateTimeInterface $timeCreatedDate;
    private DateTimeInterface $timeModifiedDate;

    private int $createdBy;
    private int $modifiedBy;


    public function __toString(): string {
        // TODO: Implement __toString() method.
        return var_export($this, true);
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getCategory(): int {
        return $this->category;
    }

    /**
     * @param int $category
     */
    public function setCategory(int $category): void {
        $this->category = $category;
    }

    /**
     * @return ?RawRowEntity
     */
    public function getParentEntity(): ?RawRowEntity {
        return $this->parentEntity;
    }

    /**
     * @param ?RawRowEntity $parentEntity
     */
    public function setParentEntity(?RawRowEntity $parentEntity): void {
        $this->parentEntity = $parentEntity;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getText(): string {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText(string $text): void {
        $this->text = $text;
    }

    /**
     * @return int
     */
    public function getFormat(): int {
        return $this->format;
    }

    /**
     * @param int $format
     */
    public function setFormat(int $format): void {
        $this->format = $format;
    }

    /**
     * @return float
     */
    public function getDefaultMark(): float {
        return $this->defaultmark;
    }

    /**
     * @param float $defaultMark
     */
    public function setDefaultMark(float $defaultMark): void {
        $this->defaultmark = $defaultMark;
    }

    /**
     * @return float
     */
    public function getPenalty(): float {
        return $this->penalty;
    }

    /**
     * @param float $penalty
     */
    public function setPenalty(float $penalty): void {
        $this->penalty = $penalty;
    }

    /**
     * @return string
     */
    public function getQtype(): string {
        return $this->qtype;
    }

    /**
     * @param string $qtype
     */
    public function setQtype(string $qtype): void {
        $this->qtype = $qtype;
    }

    /**
     * @return string
     */
    public function getStamp(): string {
        return $this->stamp;
    }

    /**
     * @param string $stamp
     */
    public function setStamp(string $stamp): void {
        $this->stamp = $stamp;
    }

    /**
     * @return string
     */
    public function getVersion(): string {
        return $this->version;
    }

    /**
     * @param string $version
     */
    public function setVersion(string $version): void {
        $this->version = $version;
    }

    /**
     * @return bool
     */
    public function isHidden(): bool {
        return $this->hidden;
    }

    /**
     * @param bool $hidden
     */
    public function setHidden(bool $hidden): void {
        $this->hidden = $hidden;
    }

    /**
     * @return DateTimeInterface
     */
    public function getTimeCreatedDate(): DateTimeInterface {
        return $this->timeCreatedDate;
    }

    /**
     * @param DateTimeInterface $timeCreateDate
     */
    public function setTimeCreatedDate(DateTimeInterface $timeCreateDate): void {
        $this->timeCreatedDate = $timeCreateDate;
    }

    /**
     * @return DateTimeInterface
     */
    public function getTimeModifiedDate(): DateTimeInterface {
        return $this->timeModifiedDate;
    }

    /**
     * @param DateTimeInterface $timeModifiedDate
     */
    public function setTimeModifiedDate(DateTimeInterface $timeModifiedDate): void {
        $this->timeModifiedDate = $timeModifiedDate;
    }

    /**
     * @return int
     */
    public function getCreatedBy(): int {
        return $this->createdBy;
    }

    /**
     * @param int $createdBy
     */
    public function setCreatedBy(int $createdBy): void {
        $this->createdBy = $createdBy;
    }

    /**
     * @return int
     */
    public function getModifiedBy(): int {
        return $this->modifiedBy;
    }

    /**
     * @param int $modifiedBy
     */
    public function setModifiedBy(int $modifiedBy): void {
        $this->modifiedBy = $modifiedBy;
    }

    /**
     * @return int
     */
    public function getLength(): int {
        return $this->length;
    }

    /**
     * @param int $length
     */
    public function setLength(int $length): void {
        $this->length = $length;
    }

}