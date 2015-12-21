<?php

namespace Rhubarb\Stem\Schema\Columns;

class EncryptedLongString extends LongString
{
    use WithEncryptedText;
}
