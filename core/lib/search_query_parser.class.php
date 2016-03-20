<?php
/**
* class search_query_parser
*/
class search_query_parser {
    var $default_operator = 'and';
    var $space_marker = '__';
    var $lbracket_marker = '_[_';
    var $rbracket_marker = '_]_';
    var $errors = array();
    var $markers = array (
                         'space'    => '__',
                         'lbracket' => '_[_',
                         'rbracket' => '_]_'
                         );
    var $limits = array(
                        'max_query' => 64,
                        'min_query' => 2,
                        'max_subquery' => 64,
                        'min_subquery' => 2,
                        );

    function parse_query($query) {
//        $query = trim(strtolower($query));
        if (substr_count($query, ')') <> substr_count($query, '(')) {
//            $this->errors[] = "Invalid brackets";
            $this->errors[] = 1;
            return false;
        } else if ( (substr_count($query, '"')>0)  && ((substr_count($query, '"') % 2) != 0 )   ) {
//            $this->errors[] = "Invalid quotes";
            $this->errors[] = 2;
            return false;
        } else if (strlen($query)>=$this->limits['max_query']) {
//            $this->errors[] = "������ �� ������ ���� ����� 64 ��������";
            $this->errors[] = 3;
            return false;

        } else if (strlen($query)<=$this->limits['min_query']) {
//            $this->errors[] = "������ �� ������ ���� ����� 3 ��������";
            $this->errors[] = 4;
            return false;
        }
        $query = $this->mark_subqueries($query);
        $result = $this->decode_query($query);
        return $result;
    }

    function get_subqueries($query_array) {
        $subqueries = array();
        $queries   = $query_array['queries'];
        $operators = $query_array['operators'];
        $queries_num = count($queries)-1;
        for ($i=0;$i<count($queries);$i++) {
            if (is_array($queries[$i])) {
                $sub = $this->get_subqueries($queries[$i]);
                $subqueries = array_merge($subqueries, $sub);
            } else {
                $sub = $queries[$i];
                $subqueries[$sub] = 1;
            }
        }
        return $subqueries;
    }

    function construct_query($query_array, $patern = "'#query'") {
        $queries   = $query_array['queries'];
        $operators = $query_array['operators'];
        $query = '';
        $queries_num = count($queries)-1;
        for ($i=0;$i<count($queries);$i++) {
            if (is_array($queries[$i])) {
                $sub = $this->construct_query($queries[$i], $patern);
                $sub = '('.$sub.')';
            } else {
                $sub = addslashes($queries[$i]);
                $sub = str_replace('#query', $sub, $patern);
            }
            $query .= $sub;
            if ($queries_num > $i) {
                if ($operators[$i] == 'not') {
                    $operators[$i] = 'and not';
                }
                $query .= ' '.$operators[$i].' ';
            }
        }
        return $query;
    }


    function construct_sql_query($query_array, $sql_fields, $sql_patern = "#field LIKE '%#query%'") {
        $sql_query = array();
        $sep = '';
        $patern = '';
        while (list(,$field) = each($sql_fields) ) {
            $patern .= $sep.str_replace('#field', $field, $sql_patern);
            $sep = ' OR ';
        }
        $sql_query[] = $this->construct_query($query_array, '('.$patern.')');
        return $sql_query;
    }


    function construct_sql_query2($query_array, $sql_fields, $sql_patern = "#field LIKE '%#query%'") {
        $sql_query = array();
        while (list(,$field) = each($sql_fields) ) {
            $patern = str_replace('#field', $field, $sql_patern);
            $sql_query[] = $this->construct_query($query_array, $patern);
        }
        return $sql_query;
    }

    function mark_subqueries($query) {
        if (!preg_match_all('/"(.*)"/U', $query, $matches) === false) {
            while (list(, $match) = each($matches[0])) {
                $match_mod = str_replace(' ', $this->markers['space'], $match);
                $query = str_replace($match, $match_mod, $query);

            }
        }
        ## brackets v1
        /*
        if (!preg_match_all('|\((.*)(\s+)(.*)\)|siU', $query, $matches) === false) {
            //print_r($matches[0]);
            while (list(, $match) = each($matches[0])) {
                $match_mod = str_replace(' ', $this->markers['space'], $match);
                $query = str_replace($match, $match_mod, $query);
            }
        }
        */
        ## brackets v2
        while (substr_count($query, ')') > 0 ) {
            $match =  strrchr(substr($query, 0, strpos($query, ')')+1), '(');
            $match_mod = str_replace(' ', $this->markers['space'], $match);
            $match_mod = str_replace('(', $this->markers['lbracket'], $match_mod);
            $match_mod = str_replace(')', $this->markers['rbracket'], $match_mod);
            $query = str_replace($match, $match_mod, $query);
        }
        $query = str_replace( $this->markers['lbracket'], '(', $query);
        $query = str_replace( $this->markers['rbracket'], ')', $query);

        $query = str_replace(',', ' ', $query);
        return $query;
    }

    function decode_query($query) {
        $query_array = preg_split ("/[\s,]+/", $query);
        //print_r($query_array);
        $counter = 0;
        $operators = array();
        $queries   = array();
        $operators[0] = '';
        $operator = '';
        while (list($key, $subquery) = each($query_array)) {
            if ($this->get_operator($subquery, &$operator)) {
                // operator
                if (!empty($queries[$counter-1])) {
                    $operators[$counter-1] = $operator;
                }
            } else {
                //query
                if (strlen($subquery) <= $this->limits['min_subquery'] && substr($subquery,0,1)<>'+') {
                    continue;
                } else if (substr($subquery,0,1) == '+') {
                    $subquery = substr($subquery,1);
                }
                $queries[$counter]   = $this->get_subquery($subquery);
                if ($counter>0 && empty($operators[$counter-1])) {
                    $operators[$counter-1] = $this->default_operator;
                }
                $counter++;
            }
        }
        $result = array();
        $result['queries']   = $queries;
        $result['operators'] = $operators;
        return $result;
    }

    function get_subquery($query) {
        if (substr_count($query, ')') > 0) {
            $query = str_replace($this->markers['space'], ' ', $query);
            $query = substr($query, 1,-1);
            $query = $this->mark_subqueries($query);
            $query = $this->decode_query($query);
            return $query;
        } else if ( substr_count($query, '"')>0)  {
            $query = str_replace($this->markers['space'], ' ', $query);
            $query = substr($query, 1,-1);
            return $query;
        } else {
            return $query;
        }
    }

    function get_operator($string, &$operator) {
        $symbols = array (
                            "'&'",
                            "'\!'",
                            "'\|'"
                        );

        $operators = array (
                            "and",
                            "not",
                            "or"
                         );
        $string = preg_replace ($symbols, $operators, $string);
        if (in_array($string, $operators)) {
            $operator = $string;
            return true;
        } else {
            $operator = '';
            return false;
        }

    }

}

?>
