<?php
//Database Abstraction Bridge
class DBUtils {

    /*
     *
     * query shoule be like "select cat_id,cat_name from category"
     */
    public static function get_query_assoc($query) {
        $a =  array();

        //run query
        //
        //loop
    {

        $a["" . $row[0]] = $row[1];
    }

        return $a;
    }
}//DBUtils
?>
