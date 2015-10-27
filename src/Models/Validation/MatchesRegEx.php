<?php

namespace Rhubarb\Stem\Models\Validation;

class MatchesRegEx extends Validation
{
    protected $regEx;

    /**
     * @param $name
     * @param string $regEx Regex pattern WITHOUT DELIMITERS
     */
    public function __construct($name, $regEx)
    {
        parent::__construct($name);
        $this->regEx = $regEx;
    }

    public function test($value, $model = null)
    {
        return preg_match('/' . str_replace('/', '\\/', $this->regEx) . '/', $value) > 0;
    }

    public function getDefaultFailedMessage()
    {
        return $this->label . ' must match the stated format';
    }
}