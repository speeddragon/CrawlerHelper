<?php
	class HttpResponse {
		protected $_httpCode;
		protected $_html;

		public function getHtml() {
		    return $this->_html;
		}
		
		public function setHtml($html) {
		    $this->_html = $html;
		}

		public function getHttpCode() {
		    return $this->_httpCode;
		}
		
		public function setHttpCode($httpCode) {
		    $this->_httpCode = $httpCode;
		}
	}