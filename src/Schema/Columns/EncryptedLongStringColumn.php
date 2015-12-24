<?php

namespace Rhubarb\Stem\Schema\Columns;

class EncryptedLongStringColumnColumn extends LongStringColumn
{
    use WithEncryptedText;
}
