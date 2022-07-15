<?php
/*
 * Copyright (c) 2022 Stefan Swerk
 * All rights reserved.
 *
 * Unless required by applicable law or agreed to in writing, software is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 */
namespace GTN\model\adapter;

use DateTimeInterface;
use GTN\model\RawRowEntity;

class RawRowEntityAdapter extends RawRowEntity {
    private RawRowEntity $adapt;

    /**
     * @param RawRowEntity $adapt the entity to adapt
     */
    protected function __construct(RawRowEntity $adapt) {
        $this->adapt = $adapt;
    }

    public function setParentEntity(?RawRowEntity $parentEntity): void {
            $this->adapt->setParentEntity($parentEntity);
    }

    public function __toString(): string {
        return $this->adapt->__toString();
    }

    public function getId(): int {
        return $this->adapt->getId();
    }

    public function setId(int $id): void {
        $this->adapt->setId($id);
    }

    public function getCategory(): int {
        return $this->adapt->getCategory();
    }

    public function setCategory(int $category): void {
        $this->adapt->setCategory($category);
    }

    public function getParentEntity(): ?RawRowEntity {
        return $this->adapt->getParentEntity();
    }

    public function getName(): string {
        return $this->adapt->getName();
    }

    public function setName(string $name): void {
        $this->adapt->setName($name);
    }

    public function getText(): string {
        return $this->adapt->getText();
    }

    public function setText(string $text): void {
        $this->adapt->setText($text);
    }

    public function getFormat(): int {
        return $this->adapt->getFormat();
    }

    public function setFormat(int $format): void {
        $this->adapt->setFormat($format);
    }

    public function getDefaultMark(): float {
        return $this->adapt->getDefaultMark();
    }

    public function setDefaultMark(float $defaultMark): void {
        $this->adapt->setDefaultMark($defaultMark);
    }

    public function getPenalty(): float {
        return $this->adapt->getPenalty();
    }

    public function setPenalty(float $penalty): void {
        $this->adapt->setPenalty($penalty);
    }

    public function getQtype(): string {
        return $this->adapt->getQtype();
    }

    public function setQtype(string $qtype): void {
        $this->adapt->setQtype($qtype);
    }

    public function getStamp(): string {
        return $this->adapt->getStamp();
    }

    public function setStamp(string $stamp): void {
        $this->adapt->setStamp($stamp);
    }

    public function getVersion(): string {
        return $this->adapt->getVersion();
    }

    public function setVersion(string $version): void {
        $this->adapt->setVersion($version);
    }

    public function isHidden(): bool {
        return $this->adapt->isHidden();
    }

    public function setHidden(bool $hidden): void {
        $this->adapt->setHidden($hidden);
    }

    public function getTimeCreatedDate(): DateTimeInterface {
        return $this->adapt->getTimeCreatedDate();
    }

    public function setTimeCreatedDate(DateTimeInterface $timeCreateDate): void {
        $this->adapt->setTimeCreatedDate($timeCreateDate);
    }

    public function getTimeModifiedDate(): DateTimeInterface {
        return $this->adapt->getTimeModifiedDate();
    }

    public function setTimeModifiedDate(DateTimeInterface $timeModifiedDate): void {
        $this->adapt->setTimeModifiedDate($timeModifiedDate);
    }

    public function getCreatedBy(): int {
        return $this->adapt->getCreatedBy();
    }

    public function setCreatedBy(int $createdBy): void {
        $this->adapt->setCreatedBy($createdBy);
    }

    public function getModifiedBy(): int {
        return $this->adapt->getModifiedBy();
    }

    public function setModifiedBy(int $modifiedBy): void {
        $this->adapt->setModifiedBy($modifiedBy);
    }

    public function getLength(): int {
        return $this->adapt->getLength();
    }

    public function setLength(int $length): void {
        $this->adapt->setLength($length);
    }

}