<?php

namespace Rhubarb\Stem\Custard\CommandHelpers;

use phpDocumentor\Reflection\DocBlock;

class DocBlockHelper extends DocBlock
{
    public static function removePropertyTags(DocBlock $docBlock, array $tagNames)
    {
        $keepTags = [];
        foreach ($docBlock->tags as $tag) {
            if ($tag instanceof DocBlock\Tag\PropertyTag && in_array($tag->getVariableName(), $tagNames)) {
                continue;
            }
            $keepTags[] = $tag;
        }

        $docBlock->tags = $keepTags;
    }
}
