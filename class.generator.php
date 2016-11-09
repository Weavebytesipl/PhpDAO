<?php
include ("class.db.php");

class CodeGenerator {

    var $db, $zip;

    public function __construct(){ }

    function show_form() {
        echo "
            <center>
            <form action=\"index.php\" method=\"post\"
            enctype=\"multipart/form-data\">
            Db Name: <input type='text' name='dbname'>
            <label for=\"file\">Filename:</label>
            <input type=\"file\" name=\"file\" id=\"file\" />
            <br />
            <input type=\"submit\" name=\"submit\" value=\"Submit\" />
            </form>
			</center> 
			<br />";
			
    }


    /*************************************************************** 
     * main controller funciton for the page                        *
     ****************************************************************/
    function main() {

        /* if nothing is posted, show the form*/
        if(!isset($_POST["submit"])) { 
            $this->show_form();
            
        }  

        else $this->generate(); 
    }

    function uploadSql($sqlname) {
        if ($_FILES["file"]["error"] > 0) {
            die( "Return Code: " . $_FILES["file"]["error"] . "<br />");
        }
        else {
            move_uploaded_file($_FILES["file"]["tmp_name"], $sqlname);
        }
    }

    function generate() {

        /* make dynamic name for directory */
        $date = new DateTime();
        //$dir = "wb_generate_" . $date->getTimestamp();

        //$sqlfile = "$dir.sql";
        /* upload sql */
        //$this->uploadSql($sqlfile);
        //
        $dir = $_POST["dbname"];
        //mkdir($dir);

        $this->db = new Db($dir, $sqlfile);
        $tables = $this->db->get_tables();

        /* initialize zip */
        $this->zip = new ZipArchive();
        if ($this->zip->open("$dir.zip", ZIPARCHIVE::CREATE )!==TRUE) {
            exit("cannot open <$dir.zip>\n");
        }
		
		$this->generateHeader($dir, $tables);
		$this->generateFooter($dir);
		$this->generateIndex($dir,$tables);
        
		foreach($tables as $row){
            $this->generateTableVO($dir, $row[0]);
            $this->generateTableDAO($dir, $row[0]);
            $this->generateTableForm($dir, $row[0]);
			$this->generateView($dir, $row[0]);
			//$this->generateAdd($dir, $row[0]);
			//$this->generateEdit($dir, $row[0]);
			$this->generateDelete($dir, $row[0]);
			$this->generateSave($dir, $row[0]);
			$this->generateImageDIR($dir,"img");
        }
		
		
        /* close zip */
        $this->zip->close();

        echo "Code Generate Successfully.<br><a href=\"$dir.zip\">Download<a>";

    }

    function generateTableVO($dir, $tbl) {
        $fields = $this->db->get_column_name($tbl);
        $pkey = $fields[0];

        $fh = fopen("$dir/class.$tbl.vo.php", 'w');
        fwrite($fh, "<?php\n");
        fwrite($fh, " /* add weavebytes header here */ \n\n");
        fwrite($fh, " /* VO for $tbl */\n\n");

        /*......... begin class ............................*/
        fwrite($fh, "/* value object to represent $tbl */ \n");
        fwrite($fh, "class $tbl { \n\n");


        /* generate var list for class */
        $i = 0;
        fwrite($fh, "\tvar ");
        foreach($fields as $f){
            $i++;
            if($i == count($fields)) fwrite($fh, "\$$f");
            else                     fwrite($fh, "\$$f, ");
        }
        fwrite($fh, ";\n\n");

        /* constructor */
        fwrite($fh, "\t/* constructor */ \n");

        /* make params for constructor */
        fwrite($fh, "\tpublic function __construct(");
        $i = 0;
        foreach($fields as $f){
            if($i == 0 ) { $i++; continue; }
                $i++;
            if($i == count($fields)) fwrite($fh, "\$$f");
            else                     fwrite($fh, "\$$f, ");
        }
        fwrite($fh, ") { \n");

        /* assign values to members */
        $i=0;
        foreach($fields as $f){
            if($i == 0 ) { $i++; continue; }
                $i++;
            fwrite($fh, "\t\t\$this->$f = \$$f;\n");
        }
        fwrite($fh, "\t\t\$this->$pkey = 0;\n");
        /* end constructor */
        fwrite($fh, "\t} \n\n");

        /* toJSON() */
        fwrite($fh, "\t/* returns json for the vo */\n");
        fwrite($fh, "\tpublic function toJSON(){\n");
		
		fwrite($fh, "\t\t\$a = array(\n");
		$i = 0;
        foreach($fields as $f){
            if($i == 0 ) { $i++; continue; }
                $i++;
            if($i == count($fields)) fwrite($fh, "\t\t\t\"$f\" => \$this->$f);\n");
            else                     fwrite($fh, "\t\t\t\"$f\" => \$this->$f,\n");
        }
		
		fwrite($fh, "\t\treturn json_encode(\$a);\n");
        fwrite($fh, "\t}\n\n");

        /* toXML() */
        fwrite($fh, "\t/* returns xml for the vo */\n");
        fwrite($fh, "\tpublic function toXML(){\n");
        fwrite($fh, "\t\t//todo\n");
        fwrite($fh, "\t}\n\n");


        /* show() */
        fwrite($fh, "\t/* convenience funciton to view contents of $tbl object */ \n");
        fwrite($fh, "\tpublic function show() { \n");

        fwrite($fh, "\t\techo \"<table>\";\n");
        foreach($fields as $f){
            fwrite($fh, "\t\t\t\techo \"<tr><td>$f</td><td>\$this->$f</td></tr>\";\n");
        }
        fwrite($fh, "\t\techo \"</table>\";\n");
        fwrite($fh, "\t} \n\n");


        /*........... end class ............................*/
        fwrite($fh, " }\n");
        fwrite($fh, "/* $tbl */\n");
        fwrite($fh, "?>\n");

        /* close php file */
        fclose($fh);

        /* add vo to zip */
        $this->zip->addFile("$dir/class.$tbl.vo.php", "class.$tbl.vo.php");
    }
	
	function generateTableForm($dir, $tbl) {
		$fields = $this->db->get_column_name($tbl);
		$tbl_c =  ucfirst($tbl);
		
		$fh = fopen("$dir/form.$tbl.php", 'w');

		
		//Java Script validation
        fwrite($fh, "<script type = \"text/javascript\" >\n");
		
        fwrite($fh, "\tfunction validate$tbl_c(){ \n\n");
        $i=0;
		
		foreach($fields as $f){
			if($i!=0){
				fwrite($fh, "\t\tvar $f =  document.forms[\"frm$tbl_c\"][\"$f\"].value; \n");
			}
			$i++;
		}
		fwrite($fh, "\n");
		$i=0;
		foreach($fields as $f){
			if($i!=0){
				fwrite($fh, "\t\tif($f == null || $f == \"\"){\n");
				fwrite($fh, "\t\t\talert(\"$f can't be empty\");\n");
				fwrite($fh, "\t\t\treturn false;\n");
				fwrite($fh, "\t\t}\n");
			}
			$i++;
		}
		
		fwrite($fh, "\n");
		fwrite($fh, "\t\treturn true;\n");
		fwrite($fh, "\t}\n");
        fwrite($fh, "</script>\n");
        
		fwrite($fh, "<?php\n");
		fwrite($fh, "include(\"header.php\");\n");
		fwrite($fh, "include(\"class.$tbl.dao.php\");\n?>\n");
		fwrite($fh, "<form name = \"frm$tbl_c\" method=\"POST\" action=\"save.$tbl.php\"  onsubmit = \"return validate$tbl_c();\">\n");
        fwrite($fh, "\t<table cellspacing=\"5\" cellpadding=\"5\">\n");
		
		fwrite($fh, "\t\t<?php\n");
		fwrite($fh, "\t\tif(isset(\$_GET[\"id\"])){\n"); 
		fwrite($fh, "\t\t\t\$dao = new DAO$tbl();\n");
		fwrite($fh, "\t\t\t\$vo = \$dao->get(\$_GET[\"id\"]);\n\t\t?>\n");
		
		
		/* generate input fields for form */
		$i=0;
        foreach($fields as $f){
			if($i!=0){
				fwrite($fh, "\t\t\t<tr>\n");
				fwrite($fh, "\t\t\t\t<td>");
				fwrite($fh, " $f ");
				fwrite($fh, "</td>\n");
				fwrite($fh, "\t\t\t\t<td>");
				fwrite($fh, "<input type = \"text\" name = \"$f\" value= \"<?=\$vo->$f?> \"/>");
				fwrite($fh, "</td>\n");
				fwrite($fh, "\t\t\t</tr>\n");
			}
			$i++;
        }
		fwrite($fh, "\t\t\t<tr colspan = \"2\">\n");
		fwrite($fh, "\t\t\t\t<th>");
        fwrite($fh, "<input type = \"submit\" value= \"EDIT\"  />");
		fwrite($fh, "</th>\n");
		fwrite($fh, "\t\t\t</tr>\n");
		$i=0;
        foreach($fields as $f){
			if($i==0){
				fwrite($fh, "\t\t\t<input type = \"hidden\" name = \"$f\" value= \"<?=\$vo->$f?> \"/>\n");
			}
			$i++;
        }
		
		fwrite($fh, "\t\t<?}else{?>\n");	
			/* generate input fields for form */
		$i=0;
        foreach($fields as $f){
			if($i!=0){
				fwrite($fh, "\t\t\t<tr>\n");
				fwrite($fh, "\t\t\t\t<td>");
				fwrite($fh, " $f ");
				fwrite($fh, "</td>\n");
				fwrite($fh, "\t\t\t\t<td>");
				fwrite($fh, "<input type = \"text\" name = \"$f\" />");
				fwrite($fh, "</td>\n");
				fwrite($fh, "\t\t\t</tr>\n");
			}
			$i++;
        }
		fwrite($fh, "\t\t\t<tr colspan = \"2\">\n");
		fwrite($fh, "\t\t\t\t<th>");
        fwrite($fh, "<input type = \"submit\" value= \"ADD\"  />");
		fwrite($fh, "</th>\n");
		fwrite($fh, "\t\t\t</tr>\n");
		fwrite($fh, "\t\t<?}?>\n");	
		
		
			
	    fwrite($fh, "\t</table>\n");
	    fwrite($fh, "</form>\n");
        fwrite($fh, "<?\ninclude(\"footer.php\");\n?>\n");

        /* close php file */
        fclose($fh);
        $this->zip->addFile("$dir/form.$tbl.php", "form.$tbl.php");
	}

    function generateTableDAO($dir, $tbl) {

        $fields = $this->db->get_column_name($tbl);

        $pkey = $fields[0];

        $fh = fopen("$dir/class.$tbl.dao.php", 'w');


        fwrite($fh, "<?php\n");
        fwrite($fh, " /* DAO for $tbl */\n\n");

        /* include vo file */
        fwrite($fh, "include (\"class.$tbl.vo.php\");\n\n");

        /*......... begin class ............................*/
        fwrite($fh, "class DAO$tbl { \n\n");

        /* get() */
        fwrite($fh, "\t/* gets a vo by $pkey */\n");
        fwrite($fh, "\tpublic function get(\$$pkey){\n");
        fwrite($fh, "\t\t/* ensure $pkey is an integer */\n");
        fwrite($fh, "\t\tif(!is_numeric(\$$pkey)) throw  new Exception(\"$pkey of $tbl must be an integer\");\n\n");

        fwrite($fh, "\t\t\$result=mysql_query(\"SELECT * FROM $tbl WHERE $pkey=\$$pkey\");\n");
        fwrite($fh, "\t\tif(\$result){/*ensure query success*/\n");
        fwrite($fh, "\t\t\tif(\$row = mysql_fetch_array(\$result)){/*ensure record*/\n");
        fwrite($fh, "\t\t\t\t\$vo = new $tbl(");

        $i = 0;
        foreach($fields as $f){		
            if($i == 0 ) { $i++; continue; }
                $i++;
            if($i == count($fields)) fwrite($fh, "\$row['$f']);\n");
            else                     fwrite($fh, "\$row['$f'],");
        }
        fwrite($fh, "\t\t\t\t\$vo->$pkey = \$$pkey;\n");
        fwrite($fh, "\t\t\t\treturn \$vo;\n");
        fwrite($fh, "\t\t\t}\n");
        fwrite($fh, "\t\t}\n\n");
        fwrite($fh, "\t\treturn NULL;\n");
        fwrite($fh, "\t}\n\n");	

        /* getAll() */
        fwrite($fh, "\t/* returns all vo */\n");
        fwrite($fh, "\tpublic function getAll(\$limit1,\$limit2){\n");
		fwrite($fh, "\t\t\$result=mysql_query(\"SELECT * FROM $tbl LIMIT \" . \$limit1 . \",\" . \$limit2 );\n");
		fwrite($fh, "\t\tif(\$result){/*ensure query success*/\n");
		fwrite($fh, "\t\t\t\$vlist = array();\n");
		fwrite($fh, "\t\t\twhile(\$row = mysql_fetch_array(\$result)){/*ensure record*/\n");
		fwrite($fh, "\t\t\t\t\$vo = new $tbl(");

        $i = 0;
        foreach($fields as $f){		
           if($i == 0 ) { $i++; continue; }
                $i++;
            if($i == count($fields)) fwrite($fh, "\$row['$f']);\n");
            else                     fwrite($fh, "\$row['$f'],");
        }
		$i = 0;
		foreach($fields as $f){		
           if($i == 0 ) { $i++ ; fwrite($fh, "\t\t\t\t\$vo->$pkey = \$row['$f'];\n") ;}
        }
		fwrite($fh, "\t\t\t\t\$vlist[] = \$vo;\n") ;							                
        fwrite($fh, "\t\t\t}\n");
		fwrite($fh, "\t\t\treturn \$vlist;\n");
        fwrite($fh, "\t\t}\n\n");
		fwrite($fh, "\t\treturn NULL;\n");
        fwrite($fh, "\t}\n\n");
	
		 /* getCount() */
        fwrite($fh, "\t/* returns number of vo */\n");
        fwrite($fh, "\tpublic function getCount(){\n");
		fwrite($fh, "\t\t\$result = mysql_num_rows(mysql_query(\"select * from $tbl\"));\n");
		fwrite($fh, "\t\treturn \$result;\n");
        fwrite($fh, "\t}\n\n");

        /* insert() */
        fwrite($fh, "\t/* insert new record in db */\n");
        fwrite($fh, "\tpublic function insert(&\$vo){\n");
        fwrite($fh, "\t\t if(mysql_query(\"INSERT INTO $tbl(");

        $i = 0;
        foreach($fields as $f){		
            $i++;
            if($i == count($fields)) fwrite($fh, "$f) VALUES(' ', ");		
            else                     fwrite($fh, "$f,");				
        }

        $i = 0;
        foreach($fields as $f){		
            if($i == 0 ) { $i++; continue; }
                $i++;
            if($i == count($fields)) fwrite($fh, "'\$vo->$f')\")){\n");
            else                     fwrite($fh, "'\$vo->$f',");
        }

        fwrite($fh, "\t\t\t\$result = mysql_query(\"Select MAX(" . $fields[0] . ") from $tbl\");\n");
        fwrite($fh, "\t\t\tif(\$row = mysql_fetch_array(\$result)){\n");
        fwrite($fh, "\t\t\t\t\$vo->" . $fields[0] . "=\$row[0];\n");
        fwrite($fh, "\t\t\t\treturn true;\n");
        fwrite($fh, "\t\t\t}\n");
        fwrite($fh, "\t\t}\n");
        fwrite($fh, "\t\treturn false;\n");
        fwrite($fh, "\t}\n\n");

        /* update() */
        fwrite($fh, "\t/* update an existing record in db */\n");
        fwrite($fh, "\tpublic function update(&$" . "vo){\n");
        fwrite($fh, "\t\treturn mysql_query(\"UPDATE $tbl SET ");

        $i = 0;
        foreach($fields as $f){		
            if($i == 0 ) { $i++; continue; }

                $i++;
            if($i == count($fields)) fwrite($fh, "$f = '\$vo->$f'");		
            else                     fwrite($fh, "$f = '\$vo->$f',");				
        }
        fwrite($fh, " WHERE ". $fields[0] ." = \$vo->" . $fields[0] . " \");\n");
        fwrite($fh, "\t}\n\n");


        /* save() */
        fwrite($fh, "\t/* save the value object in db */\n");
        fwrite($fh, "\tpublic function save(&$" . "vo){\n");
        fwrite($fh, "\t\tif(\$vo->$pkey == 0){\n");
        fwrite($fh, "\t\t\treturn \$this->insert(\$vo);\n");
        fwrite($fh, "\t\t}\n");
        fwrite($fh, "\t\treturn \$this->update(\$vo);\n");
        fwrite($fh, "\t}\n\n");

        /* del() */
        fwrite($fh, "\t/* delete an existing record from db */\n");
        fwrite($fh, "\tpublic function del(&$" . "vo){\n");
        fwrite($fh, "\t\tif(mysql_query(\"DELETE FROM $tbl WHERE " . $fields[0] . "=\$vo->" . $fields[0] . "\")) {\n");
        fwrite($fh, "\t\t\t\$vo->" . $fields[0] . "=0;\n");
        fwrite($fh, "\t\t}\n");
        fwrite($fh, "\t}\n\n");

        /* getMaxId() */
        fwrite($fh, "\t/* gets max id from db */\n");
        fwrite($fh, "\tpublic function getMaxId(){\n");
        fwrite($fh, "\t\t\$result = mysql_query(\"Select MAX(" . $fields[0] . ") from $tbl\");\n");
        fwrite($fh, "\t\tif(\$row = mysql_fetch_array(\$result)){\n");
        fwrite($fh, "\t\t\treturn \$row[0];\n");
        fwrite($fh, "\t\t}\n");
        fwrite($fh, "\t\treturn NULL;\n");
        fwrite($fh, "\t}\n\n");

        /*........... end class ............................*/
        fwrite($fh, " }\n");
        fwrite($fh, "/* DAO$tbl */\n");
        fwrite($fh, "?>\n");

        /* close php file */
        fclose($fh);

        // add dao to zip 
        $this->zip->addFile("$dir/class.$tbl.dao.php", "class.$tbl.dao.php");
    }

	public function generateView($dir, $tbl){
		$fields = $this->db->get_column_name($tbl);
        $pkey = $fields[0];
		
		$fh = fopen("$dir/$tbl.php", 'w');
		fwrite($fh, "<?php\n");
		fwrite($fh, "include(\"class.$tbl.dao.php\");\n");
		fwrite($fh, "include_once(\"header.php\");\n");
		fwrite($fh, "\$dao = new DAO$tbl();\n");
		
		fwrite($fh, "?>\n");
		fwrite($fh, "<a href=\"form.$tbl.php\">Add $tbl</a>\n");
		fwrite($fh, "<table border=\"1\" width=\"100%\" cellspacing = \"5\" cellpadding = \"5\">\n" );
		fwrite($fh, "\t<tr>\n" );
		foreach($fields as $f){
           fwrite($fh, "\t\t<td>$f");
		   fwrite($fh, "</td>\n" );        
        }
		fwrite($fh,"\t\t<td><b>Edit</b></td>\n");				
		fwrite($fh,"\t\t<td><b>Delete</b></td>\n");				
		fwrite($fh, "\t</tr>\n\n");
		fwrite($fh, "<?php\n");
		fwrite($fh, "\$rec_per_page = 10;\n");
		fwrite($fh, "if(isset(\$_GET['page']))\n");
		fwrite($fh, "\t\$page = \$_GET['page'];\n");
		fwrite($fh, "else\n");
        fwrite($fh, "\t\$page = 1;\n");
		fwrite($fh, "\$limit1 = (\$page-1)*\$rec_per_page;\n");
		fwrite($fh, "\$limit2 = (\$page)*\$rec_per_page;\n");
		fwrite($fh, "\$total_recs = \$dao->getCount();\n");
		fwrite($fh, "\$rec = \$dao->getAll(\$limit1, \$limit2);\n");
		fwrite($fh, "\$pages = ceil(\$total_recs/\$rec_per_page);\n");
		fwrite($fh, "if(\$page==1)");
		fwrite($fh, "\t\$prev = \$page;\n");
		fwrite($fh, "else");
		fwrite($fh, "\t\$prev=\$page-1;\n\n");
		fwrite($fh, "if(\$page==\$pages)");
		fwrite($fh, "\t\$next = \$page;\n");
		fwrite($fh, "else");
		fwrite($fh, "\t\$next=\$page+1;\n\n");
	
		fwrite($fh, "foreach(\$rec as \$row) {\n?>\n")	;		
			fwrite($fh, "\t<tr>\n");
			
			foreach($fields as $f){
               fwrite($fh, "\t\t<td><? echo \$row->$f ?>");
		       fwrite($fh, "\t</td>\n" );        
            }
					
       fwrite($fh, "\t\t<th><a href='form.$tbl.php?id=<? echo \$row->$fields[0] ?>'><img src=\"img/edit.png\" /></a></th>\n");
       fwrite($fh, "\t\t<th><a href='delete.$tbl.php?id=<? echo \$row->$fields[0] ?>'><img src=\"img/del.png\" /></a></th>\n");
	   fwrite($fh, "\t</tr>\n");
	   fwrite($fh, "<?}\n");	
	   fwrite($fh, "?>\n");
	   fwrite($fh, "</table>\n" );
	   
	   fwrite($fh, "<?\nif(\$page != 1){\n?>");
	   fwrite($fh, "&nbsp;&nbsp;<a href=\"$tbl.php?page=<?=\$prev?>\" style=\"margin-right:20px\">Prev</a>\n");
	   fwrite($fh, "<?}\n");
	   fwrite($fh, "for (\$p =1; \$p<=\$pages ; \$p++){\n ?>");
       fwrite($fh, "&nbsp;&nbsp;<a href=\"$tbl.php?page=<?=\$p?>\"><?=\$p.\"/\".\$pages?></a>\n");
	   fwrite($fh, "<?\n}\n");


	   fwrite($fh, "if(\$page != \$pages){\n");
	   fwrite($fh, "?><a href=\"$tbl.php?page=<?=\$next?>\" style=\"margin-left:20px\">Next</a>\n");
       fwrite($fh, "<a href=\"$tbl.php?page=<?=\$pages?>\"style=\"margin-left:12px\">Last</a><br />\n");
      
	   fwrite($fh,"<?}\n?>\n");		
	   fwrite($fh, "<?php\n");
	   fwrite($fh, "include(\"footer.php\");\n");
	   fwrite($fh, "?>\n");
	   fclose($fh);
		 /* add to zip */
       $this->zip->addFile("$dir/$tbl.php", "$tbl.php");
	}
	
	public function generateHeader($dir, $tables){
		$fh = fopen("$dir/header.php", 'w');
		fwrite($fh, "<?php include(\"db.php\"); ?>\n");
		fwrite($fh, "<html>\n");
        fwrite($fh, "\t<body>\n");
        fwrite($fh, "\t<div style = \"margin:auto;\">\n");
		$i=0;
		foreach($tables as $row){
			$i++;
			fwrite($fh, "\t\t<a href=\"$row[0].php\" style =\"text-decoration:none;\">");
			if(count($tables)== $i) fwrite($fh, "$row[0]&nbsp;&nbsp;");
			else                    fwrite($fh, "$row[0]&nbsp;&nbsp;|&nbsp;&nbsp;");
			fwrite($fh, "</a>\n");
		}
		fwrite($fh, "<br /><br /><br />\n");
		fclose($fh);
		 // add header to zip 
        $this->zip->addFile("$dir/header.php", "header.php");
		
	}
	
	public function generateFooter($dir){
		$fh = fopen("$dir/footer.php", 'w');
		fwrite($fh, "\t\t</div>\n");
		fwrite($fh, "\t</body>\n");
        fwrite($fh, "</html>");
		fclose($fh);
		 // add footer to zip 
        $this->zip->addFile("$dir/footer.php", "footer.php");
		
	}
	
	public function generateIndex($dir ,$tables){
		$fh = fopen("$dir/index.php", 'w');
		fwrite($fh, "<?php\n");
		fwrite($fh, "include(\"header.php\");\n");
		$i = 0;
		foreach($tables as $row){
			if($i == 0) fwrite($fh, "include(\"$row[0].php\");\n");
			$i++;
		}
		

		fwrite($fh, "include(\"footer.php\");\n");
		fwrite($fh, "?>\n");
		fclose($fh);
		 // add index to zip 
        $this->zip->addFile("$dir/index.php", "index.php");
		
	}
	
	public function generateSave($dir ,$tbl){
		$fields = $this->db->get_column_name($tbl);
        $pkey = $fields[0];
		
		$fh = fopen("$dir/save.$tbl.php", 'w');
		fwrite($fh, "<?php\n");
		fwrite($fh, "include(\"db.php\");\n");
		fwrite($fh, "include(\"class.$tbl.dao.php\");\n");
		fwrite($fh, "\$dao = new DAO$tbl();\n");
		fwrite($fh, "\$vo = new $tbl(");
		$i=0;
        foreach($fields as $f){
			if($i!=0){
				if((count($fields)-1)== $i) fwrite($fh, "\$_POST[\"$f\"]);\n");
				else                    fwrite($fh, "\$_POST[\"$f\"],");
			}
			
			$i++;
        }
		
		$i=0;
        foreach($fields as $f){
			if($i==0){
				fwrite($fh, "if(isset(\$_POST[\"$f\"])){\n");
				fwrite($fh, "\t\$vo->$f = \$_POST[\"$f\"];\n}\n");
			}
			$i++;
       }
	
		fwrite($fh, "\$dao->save(\$vo);\n");

		fwrite($fh, "header(\"Location: $tbl.php\");\n");
		fwrite($fh, "?>\n");
		fclose($fh);
		 // add save to zip 
        $this->zip->addFile("$dir/save.$tbl.php", "save.$tbl.php");
		
	}
	
	public function generateDelete($dir ,$tbl){
		$fields = $this->db->get_column_name($tbl);
        $pkey = $fields[0];
		
		$fh = fopen("$dir/delete.$tbl.php", 'w');
		fwrite($fh, "<?php\n");
		fwrite($fh, "include(\"db.php\");\n");
		fwrite($fh, "include(\"class.$tbl.dao.php\");\n");
		fwrite($fh, "\$dao = new DAO$tbl();\n");
		
		fwrite($fh, "if(isset(\$_GET[\"id\"])){\n");
		fwrite($fh, "\t\$vo = \$dao->get(\$_GET[\"id\"]);\n");
		fwrite($fh, "\t\$dao->del(\$vo);\n}\n");
	
		fwrite($fh, "header(\"Location: $tbl.php\");\n");
		fwrite($fh, "?>\n");
		fclose($fh);
		 // add save to zip 
        $this->zip->addFile("$dir/delete.$tbl.php", "delete.$tbl.php");
		
	}
	
	public function generateImageDIR($dir ,$foldername){
	
	/****     TO DO -------------            ***********************/
		 $imgdir = mkdir($dir . "/". $foldername);
		 copy("del.png",$dir . "/". $foldername. "/del.png");
		 copy("edit.png",$dir . "/". $foldername. "/edit.png");
		 // add save to zip 
         $this->zip->addEmptyDir($foldername);
		 $this->zip->addFile("$dir/$foldername/del.png", "$foldername/del.png");
		 $this->zip->addFile("$dir/$foldername/edit.png", "$foldername/edit.png");
	}
} /* CodeGenerator */

?>
