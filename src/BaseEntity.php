<?php
/**
 * @author Martin Kapal <flamecze@gmail.com>
 * @author Tomáš Korený <tom@koreny.eu>
 */

namespace Lempls\SmartEntity;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping as ORM;
use Lempls\SmartObjects\BaseObject;

/**
 * @ORM\MappedSuperclass()
 */
class BaseEntity extends BaseObject
{

    /**
     * Find all entities
     *
     * @return array
     */
    public static function all() {
        return \EntityManager::getRepository(get_called_class())->findAll();
    }

    /**
     * Finds one or creates new entity.
     *
     * @param array $options
     * @return object
     */
    public static function firstOrNew($options = []) {
        $entity = self::findOne($options);
        if (!$entity) {
            $entity = self::create($options);
        }
        return $entity;
    }

    /**
     * Finds array of entities matching filter, or id, or array of id's
     *
     * @param array $options
     * @return array|object
     */
    public static function find($options = [])
    {
        if (gettype($options) === 'integer') {
            return \EntityManager::find(get_called_class(), $options);
        } elseif (self::has_string_keys($options) || $options === []) {
            return \EntityManager::getRepository(get_called_class())->findBy($options);
        } else {
            $array = [];
            foreach ($options as $option) {
                $array[] = \EntityManager::find(get_called_class(), $option);
            }
            return $array;
        }

    }

    /**
     * Helper function to determine if array has string keys.
     *
     * @param array $array
     * @return bool
     */
    private static function has_string_keys(array $array) {
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }

    /**
     * Finds exactly one entity matching filter.
     *
     * @param array|int $options
     * @return object
     */
    public static function findOne($options = [])
    {
        if (gettype($options) === 'integer') {
            return \EntityManager::find(get_called_class(), $options);
        }
        else {
            return \EntityManager::getRepository(get_called_class())->findOneBy($options);
        }
    }

    /**
     * Finds object by filter, if doesn't exist, creates it, and updates it with second parameter.
     *
     * @param $options
     * @param array $update
     * @return object
     */
    public static function updateOrCreate($options, array $update)
    {
        $entity = self::firstOrNew($options);
        foreach ($update as $key => $value) {
            $entity->__set($key, $value);
        }
        $entity->save();
        return $entity;
    }

    /**
     * Deletes itself.
     */
    public function delete()
    {
        \EntityManager::remove($this);
        \EntityManager::flush($this);
    }

    /**
     * Mass delete, same params as find.
     *
     * @param array|int $options
     */
    public static function destroy($options)
    {
        $entities = self::find($options);
        if (gettype($entities) === 'array') {
            foreach ($entities as $entity) {
                $entity->delete();
            }
        } else {
            $entities->delete();
        }
    }

    /**
     * Creates new entity, persists to database.
     *
     * @param array $options
     * @return object
     */
    public static function firstOrCreate($options = [])
    {
        $entity = self::firstOrNew($options);
        $entity->save();
        return $entity;
    }

    /**
     * Find array of entities or throw error.
     *
     * @param array $options
     * @return array
     * @throws EntityNotFoundException
     */
    public static function findOrFail($options = [])
    {
        $return = self::find($options);
        if ($return === [] || $return == null) throw new EntityNotFoundException('No entity found matching this filter.');
        return $return;
    }

    /**
     * Find one entity or throw error.
     *
     * @param array $options
     * @return object
     * @throws EntityNotFoundException
     */
    public static function firstOrFail($options = [])
    {
        $return = self::findOne($options);
        if ($return === []) throw new EntityNotFoundException('No entity found matching this filter.');
        return $return;
    }

    /**
     * Eager load properties marked in annotation
     *
     * @param array $exclude
     * @return array
     */
    public function toArray($exclude = []) : array
    {
        $array = parent::toArray($exclude);

        return $this->eagerLoad($array);
    }

    /**
     * @param $array
     * @return mixed
     */
    private function eagerLoad($array)
    {
        foreach ($this as $key => $value) {
            if (parent::readAnnotation($key, 'Serialize')) {
                if(method_exists($value, '__load')) {
                    $value->__load();
                    $excluded = $this->cycleProtection($value);
                    $array[$key] = $value->toArray($excluded);
                }
                if(method_exists($value, 'initialize')) {
                    $value->initialize();
                    $array[$key] = $this->overwriteCollection($value);
                }
            }
        }
        return $array;
    }

    /**
     * @param $value
     * @return array
     */
    private function overwriteCollection($value)
    {
        $array = [];
        foreach ($value as $item) {
            $excluded = $this->cycleProtection($item);
            $array[$item->id] = $item->toArray($excluded);
        }
        return $array;
    }

    /**
     * Protects code from getting endlessly cycled.
     *
     * @param $item
     * @return array
     */
    private function cycleProtection($item)
    {
        $doctrine_annotation_reader = new AnnotationReader();
        $reflection = new \ReflectionClass($this);
        $excluded = [];
        foreach ($item as $property => $v) {
            foreach ($doctrine_annotation_reader->getPropertyAnnotations(new \ReflectionProperty($item->getClass(), $property)) as $annotation) {
                if ($annotation instanceof ORM\OneToMany || $annotation instanceof ORM\ManyToMany || $annotation instanceof ORM\OneToOne || $annotation instanceof ORM\ManyToOne) {
                    if ($annotation->targetEntity === $reflection->getShortName()){
                        $excluded[] = $property;
                    }
                }
            }
        }
        return $excluded;
    }

    /**
     * Saves entity.
     */
    public function save()
    {
        \EntityManager::persist($this);
        \EntityManager::flush($this);
    }

    /**
     * Creates new entity base on options.
     *
     * @param $options
     * @return static
     */
    public static function create($options)
    {
        $entity = new static;
        if (gettype($options) === 'array') {
            foreach ($options as $key => $value) {
                $entity->__set($key, $value);
            }
        }
        return $entity;
    }

    /**
     * Fetches all records like $key => $value pairs
     *
     * @param array $criteria parameter can be skipped
     * @param string $value mandatory
     * @param array $orderBy parameter can be skipped
     * @param string $key optional
     * @return array
     * @throws \Exception
     */
    public static function findPairs($criteria, $value = NULL, $orderBy = [], $key = NULL)
    {
        if (!is_array($criteria)) {
            $key = $orderBy;
            $orderBy = $value;
            $value = $criteria;
            $criteria = [];
        }

        if (!is_array($orderBy)) {
            $key = $orderBy;
            $orderBy = [];
        }

        if (empty($key)) {
            $key = 'id';
        }

        $query = \EntityManager::getRepository(self::getClass())->createQueryBuilder('e')
            ->where($criteria)
            ->select("e.$value", "e.$key")
            ->resetDQLPart('from')->from(self::getClass(), 'e', 'e.' . $key)
            ->orderBy((array) $orderBy)
            ->getQuery();

        try {
            return array_map(function ($row) {
                return reset($row);
            }, $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY));

        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }


}
