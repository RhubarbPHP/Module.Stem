<?php
/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 09/10/2015
 * Time: 12:08
 */

namespace Rhubarb\Stem\Schema\Columns;


use Rhubarb\Stem\Models\Model;

class UUID extends String implements ModelValueInitialiserInterface
{
    public function __construct($columnName = 'UUID')
    {
        parent::__construct($columnName, 100, null);
    }

    private function generateUUID()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function onNewModelInitialising(Model $model)
    {
        $model[ $this->columnName ] = $this->generateUUID();
    }
}