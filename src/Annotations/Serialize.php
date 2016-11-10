<?php
/**
 * @author Martin Kapal <flamecze@gmail.com>
 * @author Tomáš Korený <tom@koreny.eu>
 */

namespace Lempls\Annotations;



final class Serialize
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

}
