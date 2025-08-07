<?php
/*
 * Paste 3 <old repo: https://github.com/jordansamuel/PASTE>  new: https://github.com/boxlabss/PASTE
 * demo: https://paste.boxlabs.uk/
 * https://phpaste.sourceforge.io/  -  https://sourceforge.net/projects/phpaste/
 *
 * Licensed under GNU General Public License, version 3 or later.
 * See LICENCE for details.
 */
class SSP {
    static function simple($request, $sql_details, $table, $primaryKey, $columns, $columns2) {
        $bindings = array();
        $db = self::sql_connect($sql_details);

        // Build the SQL query string
        $limit = self::limit($request, $columns);
        $order = self::order($request, $columns);
        $where = self::filter($request, $columns, $bindings);

        $select_columns = array_map(function($col) { return $col['db']; }, $columns);
        $query = "SELECT " . implode(", ", $select_columns) . " FROM `$table` $where $order $limit";
        
        $stmt = $db->prepare($query);
        foreach ($bindings as $binding) {
            $stmt->bindValue($binding['key'], $binding['val'], $binding['type']);
        }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total records
        $resTotal = $db->query("SELECT COUNT({$primaryKey}) AS cnt FROM `$table`")->fetch(PDO::FETCH_ASSOC);
        $recordsTotal = $resTotal['cnt'];

        $resFiltered = $db->query("SELECT COUNT({$primaryKey}) AS cnt FROM `$table` $where")->fetch(PDO::FETCH_ASSOC);
        $recordsFiltered = $resFiltered['cnt'];

        return array(
            "draw" => isset($request['draw']) ? intval($request['draw']) : 0,
            "recordsTotal" => intval($recordsTotal),
            "recordsFiltered" => intval($recordsFiltered),
            "data" => self::data_output($columns2, $data)
        );
    }

    static function sql_connect($sql_details) {
        return $sql_details['pdo'];
    }

    static function limit($request, $columns) {
        $limit = '';
        if (isset($request['start']) && $request['length'] != -1) {
            $limit = "LIMIT " . intval($request['start']) . ", " . intval($request['length']);
        }
        return $limit;
    }

    static function order($request, $columns) {
        $order = '';
        if (isset($request['order']) && count($request['order'])) {
            $orderBy = array();
            for ($i = 0, $ien = count($request['order']); $i < $ien; $i++) {
                $columnIdx = intval($request['order'][$i]['column']);
                $requestColumn = $request['columns'][$columnIdx];
                $column = $columns[$columnIdx];
                if ($requestColumn['orderable'] == 'true') {
                    $dir = $request['order'][$i]['dir'] === 'asc' ? 'ASC' : 'DESC';
                    $orderBy[] = '`' . $column['db'] . '` ' . $dir;
                }
            }
            if (count($orderBy)) {
                $order = 'ORDER BY ' . implode(', ', $orderBy);
            }
        }
        return $order;
    }

    static function filter($request, $columns, &$bindings) {
        $where = '';
        if (isset($request['search']) && $request['search']['value'] != '') {
            $str = $request['search']['value'];
            $where = 'WHERE (';
            for ($i = 0, $ien = count($columns); $i < $ien; $i++) {
                $column = $columns[$i];
                if ($column['searchable'] == 'true') {
                    $bindings[] = array(
                        'key' => $column['db'],
                        'val' => '%' . $str . '%',
                        'type' => PDO::PARAM_STR
                    );
                    $where .= '`' . $column['db'] . '` LIKE :' . $column['db'] . ' OR ';
                }
            }
            $where = rtrim($where, ' OR ') . ')';
        }
        return $where;
    }

    static function data_output($columns, $data) {
        $out = array();
        for ($i = 0, $ien = count($data); $i < $ien; $i++) {
            $row = array();
            for ($j = 0, $jen = count($columns); $j < $jen; $j++) {
                $column = $columns[$j];
                if (isset($column['formatter'])) {
                    $row[$column['dt']] = $column['formatter']($data[$i][$column['db']], $data[$i]);
                } else {
                    $row[$column['dt']] = htmlspecialchars($data[$i][$column['db']]);
                }
            }
            $out[] = $row;
        }
        return $out;
    }
}
?>