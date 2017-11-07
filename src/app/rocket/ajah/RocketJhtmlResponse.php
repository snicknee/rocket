<?php
namespace rocket\ajah;

use n2n\web\http\BufferedResponseObject;
use n2n\impl\web\ui\view\json\JsonResponse;
use n2n\impl\web\ui\view\jhtml\JhtmlJsonResponse;
use n2n\impl\web\ui\view\html\HtmlView;
use n2n\impl\web\ui\view\jhtml\JhtmlRedirect;

class RocketJhtmlResponse extends BufferedResponseObject {
	private $jsonResponse;

	private function __construct(array $attrs) {
		$this->jsonResponse = new JsonResponse(array(JhtmlJsonResponse::ADDITIONAL_KEY => $attrs));
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\BufferedResponseObject::getBufferedContents()
	 */
	public function getBufferedContents(): string {
		return $this->jsonResponse->getBufferedContents();
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\ResponseObject::prepareForResponse()
	 */
	public function prepareForResponse(\n2n\web\http\Response $response) {
		$this->jsonResponse->prepareForResponse($response);
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\web\http\ResponseObject::toKownResponseString()
	 */
	public function toKownResponseString(): string {
		return $this->jsonResponse->toKownResponseString();
	}

	const ATTR_EVENTS = 'events';
	const ATTR_MODIFICATIONS = 'modifications';

	const MOD_TYPE_CHANGED = 'changed';
	const MOD_TYPE_REMOVED = 'removed';

	const ATTR_EXEC_CONFIG = 'execConfig';

	/**
	 * @param string $fallbackUrl
	 * @param JhtmlEventInfo $ajahEventInfo
	 * @param JhtmlExec $jhtmlExec
	 * @return BufferedResponseObject
	 */
	public static function redirectBack(string $fallbackUrl, JhtmlEventInfo $ajahEventInfo = null, JhtmlExec $jhtmlExec = null) {
		$attrs = array();

		if ($ajahEventInfo !== null) {
			$attrs[self::ATTR_EVENTS] = $ajahEventInfo->toAttrs();
		}

		if ($jhtmlExec !== null) {
			$attrs[self::ATTR_EXEC_CONFIG] = $jhtmlExec->toAttrs();
		}

		return JhtmlRedirect::back($fallbackUrl, $jhtmlExec, $attrs);
	}

	/**
	 * @param JhtmlEventInfo $ajahEventInfo
	 * @return BufferedResponseObject
	 */
	public static function events(JhtmlEventInfo $ajahEventInfo) {
		return new RocketJhtmlResponse(array(
				self::ATTR_EVENTS => $ajahEventInfo === null ? array() : $ajahEventInfo->toAttrs()));
	}

	public static function view(HtmlView $htmlView, JhtmlEventInfo $ajahEventInfo = null) {
		return new JhtmlJsonResponse($htmlView,
				($ajahEventInfo !== null ? array(self::ATTR_EVENTS => $ajahEventInfo->toAttrs()) : null));
	}
}