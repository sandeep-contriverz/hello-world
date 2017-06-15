<?
require('../vendor/autoload.php');


$db = new Hmg\Controllers\DbController('localhost',  'root', 'root','hmg_v3');

$db->connect();


$sql= "SELECT fp.id , p.employer , p.first_name , p.last_name FROM `family_provider` fp
INNER  JOIN providers p ON p.id = fp.provider_id
WHERE `organization_site_id` IS NULL AND `contact_id` IS NULL";

echo '<pre>';
$rs =mysql_query($sql);
        $rows = array();
        if ($rs) 
            while ($row = mysql_fetch_array($rs, MYSQL_ASSOC)){

                list ($org_name,$site) = explode(':',$row['employer']);
                $first = mysql_escape_string($row['first_name']);  $last = mysql_escape_string($row['last_name']);
                $site=mysql_escape_string(trim($site));
                $org_name = mysql_escape_string(trim($org_name));
                 $sql2 = "Select os.id as organization_site_id , c.id as contact_id from organization_sites os
                     JOIN organizations o ON o.id=os.organization_id
                     JOIN contacts c ON os.id = c.organization_sites_id
                     LEFT JOIN settings site ON site.id=os.organization_site_id
                     LEFT JOIN settings org ON org.id=o.organization_name_id
                     WHERE org.name='$org_name' AND site.name='$site' AND 
                     c.first='$first' AND c.last='$last'   LIMIT 1";
                 $rs2 =mysql_query($sql2) or die($sql2);

                 $row2 = mysql_fetch_array($rs2, MYSQL_ASSOC);

                 if(empty($row2)){
                    continue;

                 }
                 $sql3 = "UPDATE family_provider SET organization_site_id =".$row2['organization_site_id']." ,contact_id=".$row2['contact_id']." WHERE id=".$row['id'];
                  mysql_query($sql3) or die($sql3);
                 
            }
                
?>