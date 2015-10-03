<?php
		$dbhost = "localhost";
		$dbuser = "root";
		$dbpass = "";
		$dbname     = "naughty9";
		
		$con   = mysql_connect($dbhost,$dbuser, $dbpass);
		$condb = mysql_select_db($dbname);	
		
		$table_name = "category";
		
		$result = mysql_query("select * from $table_name");
		//$a = array();
		/* get column metadata */
		$i = 0;
		while ($i < mysql_num_fields($result)) {
			$meta = mysql_fetch_field($result, $i);
			echo "<pre>
				blob:         $meta->blob
				max_length:   $meta->max_length
				multiple_key: $meta->multiple_key
				name:         $meta->name
				not_null:     $meta->not_null
				numeric:      $meta->numeric
				primary_key:  $meta->primary_key
				table:        $meta->table
				type:         $meta->type
				unique_key:   $meta->unique_key
				unsigned:     $meta->unsigned
				zerofill:     $meta->zerofill
			</pre>";
			$i++;
		}/*WHILE*/
		mysql_free_result($result);
?>