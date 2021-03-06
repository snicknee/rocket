<?php
namespace rocket\ei\manage\mapping\impl;

use rocket\ei\manage\mapping\EiField;
use rocket\ei\manage\mapping\EiFieldWrapper;

class EiFieldWrapperImpl implements EiFieldWrapper {
	private $eiField;
	private $ignored = false;
	
	public function __construct(EiField $eiField) {
		$this->eiField = $eiField;
	}
	
	/**
	 * @param bool $ignored
	 */
	public function setIgnored(bool $ignored) {
		$this->ignored = $ignored;
	}
	
	/**
	 * @return bool
	 */
	public function isIgnored(): bool {
		return $this->ignored;
	}
	
	/**
	 * @return \rocket\ei\manage\mapping\EiField
	 */
	public function getEiField() {
		return $this->eiField;
	}
}