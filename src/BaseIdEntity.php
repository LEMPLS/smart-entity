<?php
/**
 * @author Martin Kapal <flamecze@gmail.com>
 * @author Tomáš Korený <tom@koreny.eu>
 */

namespace Lempls\SmartEntity;


use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass()
 */
class BaseIdEntity extends BaseEntity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @access read-only
     * @var int
     */

    protected $id;
}
