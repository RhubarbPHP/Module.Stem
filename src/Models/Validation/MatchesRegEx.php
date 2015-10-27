<?php

namespace Rhubarb\Stem\Models\Validation;

class MatchesRegEx extends Validation
{
    protected $regEx;

    public function __construct($name, $regEx = "")
    {
        parent::__construct($name);

        if ($regEx) {
            $this->regEx = $regEx;
        }
    }

    public function test($value, $model = null)
    {
        return (bool)(preg_match($this->regEx, $value));
    }

    public function getDefaultFailedMessage()
    {
        return $this->label . ' must match the stated format';
    }
}