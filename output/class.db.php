<?php

include ("class.config.php");

//output

class Db{
    private static $instance;	
    /*
     * constructor
     */
    public function __construct($dbname, $sqlfile){
        $con = mysql_connect(Config::$dbhost, Config::$dbuser, Config::$dbpass);
        if (!$con) {
            die('Could not connect: ' . mysql_error());
        }

        if(!$this->mysql_install_db($dbname, $sqlfile, $errmsg)) { 
            die( "failure: ".$errmsg."<br/>".mysql_error() );
        }
    }

    /*
     * get tables list from the db
     */
    public function get_tables(){
        $qry = "SHOW TABLES"; 
        $rs  = mysql_query($qry);
        $a =array();
        while ($row = mysql_fetch_array($rs)) { 
            $a[] = $row;	
        }	

        return $a;
    }

    /* 
     * get fields list from the table
     */
    public function get_fields($tbl_name){
        $rs = mysql_query("SELECT * from $tbl_name");
        $a =array();
        $cols=0;
        while ($row = mysql_fetch_assoc($rs)) { 
            if($cols == 0) {
                $cols = 1;
                foreach($row as $col => $value) {
                    $a[] = $col;
                }
            }

        }
        return $a;
		
    }
	
	public function get_column_name($tble_name){
		$result = mysql_query("select * from $tble_name");
		$a = array();
		
		/* get column metadata */
		$i = 0;
		
		while ($i < mysql_num_fields($result)) {
			$meta = mysql_fetch_field($result, $i);
				$a[]  = $meta->name;
				$i++;
		}/*while*/
		
		mysql_free_result($result);
		return $a;
	}/*get_column_name*/

    /* 
     * import sql file into db
     */
    function mysql_import_file($filename, &$errmsg) {
        /* Read the file */
        $lines = file($filename);

        if(!$lines) {
            $errmsg = "cannot open file $filename";
            return false;
        }

        $scriptfile = false;

        /* Get rid of the comments and form one jumbo line */
        foreach($lines as $line) {
            $line = trim($line);

            if(!ereg('^--', $line)) {
                $scriptfile.=" ".$line;
            }
        }

        if(!$scriptfile) {
            $errmsg = "no text found in $filename";
            return false;
        }

        /* Split the jumbo line into smaller lines */
        $queries = explode(';', $scriptfile);

        /* Run each line as a query */
        foreach($queries as $query) {
            $query = trim($query);
            if($query == "") { continue; }
            if(!mysql_query($query.';')) {
                $errmsg = "query ".$query." failed";
                return false;
            }
        }

        return true;
    }

    function mysql_install_db($dbname, $dbsqlfile, &$errmsg) {
        $result = true;

        if(!mysql_select_db($dbname)) {
            $result = mysql_query("CREATE DATABASE $dbname");
            if(!$result) {
                $errmsg = "could not create [$dbname] db in mysql";
                return false;
            }
            $result = mysql_select_db($dbname);
        }

        if(!$result) {
            $errmsg = "could not select [$dbname] database in mysql";
            return false;
        }

        $result = $this->mysql_import_file($dbsqlfile, $errmsg);
        return $result;
    }

}/* Db */
?>
