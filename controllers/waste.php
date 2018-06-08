<?php

class WasteController extends PluginController {

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        Navigation::activateItem("/admin/config/cleanup");
        if (!$GLOBALS['perm']->have_perm("root")) {
            throw new AccessDeniedException();
        }
        foreach (scandir($GLOBALS['STUDIP_BASE_PATH']."/lib/models") as $file) {
            if ($file[0] !== "." && !is_dir($GLOBALS['STUDIP_BASE_PATH']."/lib/models/".$file)) {
                require_once "lib/models/".$file;
            }
        }
    }

    public function index_action()
    {
        $this->tables = array();
        $this->count_sorms = 0;
        foreach (get_declared_classes() as $class) {
            $reflection = new ReflectionClass($class);
            if ($class !== "SimpleORMap" && is_subclass_of($class, "SimpleORMap") && !$reflection->isAbstract()) {
                $this->count_sorms++;

                $object = new $class();
                $metadata = $object->getTableMetadata();
                $objects = $this->getWastedObjects($metadata);

                if (!$objects || !count($objects)) {
                    continue;
                }

                $this->tables[] = array(
                    'name' => $metadata['table'],
                    'count' => count($objects),
                    'class' => $class
                );
            }
        }
    }

    public function takeout_action()
    {
        if (Request::isPost()) {
            foreach (Request::getArray("c") as $class) {
                $reflection = new ReflectionClass($class);
                if ($class !== "SimpleORMap" && is_subclass_of($class, "SimpleORMap") && !$reflection->isAbstract()) {
                    $object = new $class();
                    $metadata = $object->getTableMetadata();
                    $objects = $this->getWastedObjects($metadata);

                    if (!$objects || !count($objects)) {
                        continue;
                    }
                    foreach ($objects as $pk) {
                        if (is_array($pk) && count($pk) < 2) {
                            $pk = $pk[0];
                        }
                        $object = $class::find($pk);
                        if ($object) {
                            $object->delete();
                        }
                    }
                }
            }
            PageLayout::postSuccess(_("Verwaiste Daten wurden gelöscht."));
        }
        $this->redirect("waste/index");
    }

    protected function getWastedObjects($metadata)
    {
        $select = "";
        foreach ($metadata['pk'] as $i => $field) {
            if ($i > 0) {
                $select .= ", ";
            }
            $select .= "`".$metadata['table']."`.`".$field."`";
        }
        $select = "
            SELECT ".$select."
            FROM `".$metadata['table']."`
        ";

        if (!$metadata['config']['belongs_to']) {
            return false;
        }

        foreach ((array) $metadata['config']['belongs_to'] as $key => $belongs_to) {
            if (!$belongs_to
                || !$belongs_to['class_name']
                || !$belongs_to['foreign_key']
                || $belongs_to['assoc_func']) {
                continue;
            }
            $belongs_to['class_name'];
            $belongs_to['foreign_key'];
            $relation = new $belongs_to['class_name']();
            $relation_meta = $relation->getTableMetadata();
            $alias = $relation_meta['table']."_".md5($key);
            $select .= "LEFT JOIN `".$relation_meta['table']."` AS `" . $alias . "`
                            ON (`".$alias."`.`".$relation_meta['pk'][0]."` = `".$metadata['table']."`.`".($belongs_to['foreign_key'] ?: $relation_meta['pk'][0])."`) ";
        }
        $where = "";
        foreach ((array) $metadata['config']['belongs_to'] as $key => $belongs_to) {
            if (!$belongs_to
                || !$belongs_to['class_name']
                || !$belongs_to['foreign_key']
                || $belongs_to['assoc_func']) {
                continue;
            }
            $relation = new $belongs_to['class_name']();
            $relation_meta = $relation->getTableMetadata();
            $alias = $relation_meta['table']."_".md5($key);
            if ($where) {
                $where .= " OR ";
            }
            $where .= " `".$alias."`.`".$relation_meta['pk'][0]."` IS NULL ";
        }
        if (!$where) {
            return false;
        }
        $where = " WHERE ".$where;

        try {
            $statement = DBManager::get()->prepare($select . $where);
            $statement->execute();
        } catch (Exception $e) {
            var_dump($e->getMessage());
            var_dump($select . $where);
            var_dump($metadata);
            die();
        }
        $pks = $statement->fetchAll(PDO::FETCH_COLUMN);
        return $pks;
    }

}