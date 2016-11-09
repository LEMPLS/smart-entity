<?php
/**
 * Created by IntelliJ IDEA.
 * User: Puma
 * Date: 09.11.2016
 * Time: 23:12
 */

namespace Lempls\SmartEntity;

use Doctrine\ORM\Mapping as ORM;
use Lempls\SmartObjects\BaseObject;

/**
 * @ORM\MappedSuperclass()
 */
class BaseEntity extends BaseObject
{

    public static function all() {
        return \EntityManager::getRepository(self::class)->findAll();
    }

    public static function findOrNew() {

    }

}
