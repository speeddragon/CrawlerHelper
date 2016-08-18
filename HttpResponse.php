<?php

class HttpResponse
{
    protected $_httpCode;
    protected $_html;

    public function getHtml()
    {
        return $this->_html;
    }

    public function setHtml($html)
    {
        $this->_html = $html;
    }

    public function getHttpCode()
    {
        return $this->_httpCode;
    }

    public function setHttpCode($httpCode)
    {
        $this->_httpCode = $httpCode;
    }

    /**
     * Get json object if http response is a valid json
     *
     * @return mixed|bool
     */
    public function getJson()
    {
        json_decode($this->_html);
        if (json_last_error() == JSON_ERROR_NONE) {
            return json_decode($this->_html);
        } else {
            return false;
        }
    }
}