<?php
/**
 * Created by PhpStorm.
 * User: mhaase
 * Date: 28.06.2017
 * Time: 10:26
 */

namespace HaaseIT\HCSF\Controller\CLI;

use Zend\ServiceManager\ServiceManager;

class Itemdata
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $dbal;

    /**
     * @var array
     */
    protected $items;

    /**
     * @var array
     */
    protected $itemschanged = [];

    public function __construct(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
        $this->dbal = $serviceManager->get('dbal');
    }

    public function getItems()
    {
        return $this->items;
    }

    public function fetchItems()
    {
        $querybuilder = $this->dbal->createQueryBuilder();

        $querybuilder
            ->select('itm_id', 'itm_data')
            ->from('item_base')
        ;

        $stmt = $querybuilder->execute();

        $this->items = $stmt->fetchAll();

        $this->decodeItemData();
    }

    protected function decodeItemData()
    {
        foreach ($this->items as $key => $item) {
            if (empty($item['itm_data']) || ($itemdata = json_decode($item['itm_data'])) === null) {
                $itemdata = new \stdClass();
            }
            $this->items[$key]['itm_data'] = $itemdata;
        }
    }

    protected function encodeItemData()
    {
        foreach ($this->items as $key => $item) {
            $this->items[$key]['itm_data'] = json_encode($item['itm_data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    }

    public function addDataWhere($field, $needle, $newfield, $content)
    {
        foreach ($this->items as $key => $item) {
            if ($needle !== false) {
                if (!empty($item['itm_data']->$field) && $item['itm_data']->$field == $needle) {
                    $this->items[$key]['itm_data']->$newfield = $content;
                    $this->itemschanged[$key] = true;
                }
            } else {
                if (!empty($item['itm_data']->$field)) {
                    $this->items[$key]['itm_data']->$newfield = $content;
                    $this->itemschanged[$key] = true;
                }
            }
        }
    }

    // if $needls is set to false, existence of the field is enough to trigger removal
    public function removeDataWhere($field, $needle, $fieldtoremove)
    {
        foreach ($this->items as $key => $item) {
            if (!empty($item['itm_data']->$field)) {
                if ($needle) {
                    if ($item['itm_data']->$field == $needle && isset($item['itm_data']->$fieldtoremove)) {
                        unset($this->items[$key]['itm_data']->$fieldtoremove);
                        $this->itemschanged[$key] = true;
                    }
                } else {
                    if (isset($item['itm_data']->$fieldtoremove)) {
                        unset($this->items[$key]['itm_data']->$fieldtoremove);
                        $this->itemschanged[$key] = true;
                    }
                }
            }
        }
    }

    public function writeItems()
    {
        $this->encodeItemData();

        foreach ($this->items as $key => $item) {
            if (!isset($this->itemschanged[$key])) {
                continue;
            }
            $querybuilder = $this->dbal->createQueryBuilder();

            $querybuilder
                ->update('item_base')
                ->set('itm_data', '?')
                ->setParameter(0, $item['itm_data'])
                ->where('itm_id = '.$item['itm_id'])
            ;
            echo 'updating item with itm_id: '.$item['itm_id'].PHP_EOL;
            $querybuilder->execute();
        }
    }
}