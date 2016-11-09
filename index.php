<html>
<!-- <body bgcolor="#3B3131"> -->
<body>

<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("class.generator.php");

 $gen = new CodeGenerator();
 $gen->main();	
?>
	<h3>Known issues</h3>
	<ul >
		<li>This generator will fail if table name is "order".</li>
	</ul>
			 
	<h3>TO do</h3>
		<ul>
					<li>Recursively copy output directory generated zip.</li>
					<li>Generate css.</li>
					<li>use textarea in html for field length > 64.</li>
					<li>Check for intval in dao</li>
					<li>Add  enctype="multipart/form-data" to form </li>
					<li>Table title should be bold</li>
					<li>Use hash for serial number/auto increment when listing table</li>
					<li>Include all dao in header file. Create objects of all dao "dao_tablename" in file</li>
					<li>Remove space in value tag in update case in form.$tblname</li>
					<li>value objects naming convention "vo_tablename"</li>
					<li>for listing in foreach loop create "vo_list" variable</li>			
		</ul>
</body>
</html>
