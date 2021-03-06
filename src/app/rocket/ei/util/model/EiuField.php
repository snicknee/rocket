<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the n2n module ROCKET.
 *
 * ROCKET is free software: you can redistribute it and/or modify it under the terms of the
 * GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * ROCKET is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg...........:	Architect, Lead Developer, Concept
 * Bert Hofmänner.............: Idea, Frontend UI, Design, Marketing, Concept
 * Thomas Günther.............: Developer, Frontend UI, Rocket Capability for Hangar
 */
namespace rocket\ei\util\model;

use n2n\l10n\Message;
use rocket\ei\EiPropPath;

class EiuField {
	private $eiPropPath;
	private $eiuEntry;
	private $eiuFactory;
	private $eiuProp;
	
	public function __construct(EiPropPath $eiPropPath, EiuEntry $eiuEntry, EiuFactory $eiuFactory = null) {
		$this->eiPropPath = $eiPropPath;
		$this->eiuEntry = $eiuEntry;
		$this->eiuFactory = $eiuFactory;
	}
	
	/**
	 * @return \rocket\ei\util\model\EiuProp
	 */
	public function getEiuProp() {
		if ($this->eiuProp === null) {
			$this->eiuProp = $this->getEiuEntry()->getEiuFrame()->getEiuEngine()->prop($this->eiPropPath);
		}
		
		return $this->eiuProp;
	}
	
	/**
	 * @return \rocket\ei\EiPropPath
	 */
	public function getEiPropPath() {
		return $this->eiPropPath;
	}
	
	/**
	 * @return \rocket\ei\manage\mapping\EiField
	 */
	public function getEiField() {
		return $this->eiuEntry->getEiEntry()->getEiField($this->eiPropPath);
	}
	
	public function getEiuEntry() {
		return $this->eiuEntry;
	}
	
	public function getValue() {
		return $this->getEiuEntry()->getValue($this->eiPropPath);
	}
	
	public function setValue($value) {
		return $this->getEiuEntry()->setValue($this->eiPropPath, $value);
	}
	
	public function setScalarValue($scalarValue) {
		return $this->getEiuEntry()->setScalarValue($this->eiPropPath, $scalarValue);
	}
	
	/**
	 * @param Message $message
	 * @return \rocket\ei\util\model\EiuField
	 */
	public function addError(Message $message) {
		$this->getEiuEntry()->getEiEntry()->getMappingErrorInfo()->getFieldErrorInfo($this->eiPropPath)
				->addError($message);
		return $this;
	}
}
