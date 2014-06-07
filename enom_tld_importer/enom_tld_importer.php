<?php
if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

//$enomUrl="resellertest.enom.com";
$enomUrl="reseller.enom.com";

function enom_tld_importer_config() {
    return array(
        'name' => 'Enom TLD Import Module',
        'version' => '1.0',
        'author' => 'Bytesun',
        'description' => 'An official addon module from Bytesun for WHMCS to Importing all provisioning TLD for Enom',
        'language'  =>  'english',
    );
}

function enom_tld_importer_activate() {
  
   return array('status'=>'success','description'=>'Successfully Activated');
}

function enom_tld_importer_deactivate() {
   
   return array('status'=>'success','description'=>'Successfully De-Activated');
}
function enom_tld_importer_sidebar( $vars ) {
    $sidebar = '';
    return $sidebar;
}

function enom_tld_importer_output($vars) {

    $out= <<<ENOM
        <table border = "0" width = "650">
<tr valign = "top">
<td width = "320">
<form method="post">
    <strong>Price importer</strong>
    <br />
    <br />
    <input name="action" value="doimport" type="hidden" />
    eNom Username: <input name="enU" type="text" /><br />
    eNom Password: &nbsp;<input name="enP" type="text" /><br />
    <br />
    <input name="emptytables" type="checkbox" checked />Delete existing prices first?<br />
    <br />
    <input name="oneyear" type="checkbox" checked />Add 1 year prices?<br />
    <input name="twoyear" type="checkbox" />Add 2-4 years prices?<br />
    <input name="fiveyear" type="checkbox" />Add 5-9 years prices?<br />
    <input name="tenyear" type="checkbox" />Add 10 years prices?<br />
    <br />
    <input name="roundupdown" type="checkbox" />Round up or down to the nearest whole value?<br />
    <br />
    <br />
    <input name="Submit1" type="submit" value="Submit" />
</form>
</td>
<td>
<form method="post">
    <strong>Price changer</strong>
    <br />
    <br />
    <input name="action" value="changeprices" type="hidden" />
    Change prices from: <input name="pricefrom" type="text"  size="10" /> to: <input name="priceto" type="text"  size="10" />
    <br />
    <br />
    <input name="pricereg" type="checkbox" />Change all register prices?<br />
    <input name="pricetran" type="checkbox" />Change all transfer prices?<br />
    <input name="priceren" type="checkbox" />Change all renewal prices?<br />
    <br />
    <br />
    <input name="Submit2" type="submit" value="Submit" />
</form>
</td>
<tr>
</table>
ENOM;

echo $out;

}
    


if($_POST[action]=='doimport'){
if (isset($_POST['emptytables'])) {

//truncate tbldomainpricing
//mysql_query("truncate table tbldomainpricing");
mysql_query("delete from tbldomainpricing where autoreg='enom'");

//remove all TLD data from tblpricing table
mysql_query("DELETE FROM `tblpricing` WHERE type='domainregister'");
mysql_query("DELETE FROM `tblpricing` WHERE type='domaintransfer'");
mysql_query("DELETE FROM `tblpricing` WHERE type='domainrenew'");
}


if (isset($_POST['oneyear'])) {

//get the url from curl
$url = "http://$enomUrl/interface.asp?command=PE_GetRetailPricing&TLDOnly=1&years=1&uid={$_POST[enU]}&pw={$_POST[enP]}&responsetype=xml";
//echo $url."<BR>";
$ch = curl_init();
$timeout = 5; // set to zero for no timeout
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
$result = curl_exec($ch);
//echo $result."<BR>";
curl_close($ch);
$rss1 = simplexml_load_string($result);


$i=1;
foreach ($rss1->pricestructure->tld as $item1) {


    //first insert into tbldomainpricing
    $query="INSERT IGNORE INTO `tbldomainpricing` (`id` ,`extension` ,`dnsmanagement` ,`emailforwarding` ,`idprotection` ,`eppcode` ,`autoreg` ,`order`)
    VALUES (NULL , '." . $item1->tld . "', '', '', '', '', 'enom', '$i');";
    mysql_query($query);
    $relid = mysql_insert_id();

if (isset($_POST['roundupdown'])) {

if($item1->registerprice =="0.00"){
$reg = "-1";
}else{
$reg = round((float)$item1->registerprice);
}

if($item1->transferprice =="0.00"){
$tran = "-1";
}else{
$tran = round((float)$item1->transferprice);
}

if($item1->renewprice =="0.00"){
$ren = "-1";
}else{
$ren = round((float)$item1->renewprice);
}

}else{

if($item1->registerprice =="0.00"){
$reg = "-1";
}else{
$reg = (float)$item1->registerprice;
}

if($item1->transferprice =="0.00"){
$tran = "-1";
}else{
$tran = (float)$item1->transferprice;
}

if($item1->renewprice =="0.00"){
$ren = "-1";
}else{
$ren = (float)$item1->renewprice;
}

}//roundupdown
    
    //now insert into tblpricing
    $query="INSERT INTO `tblpricing` (`id` ,`type` ,`currency` ,`relid` ,`msetupfee` ,`qsetupfee` ,`ssetupfee` ,`asetupfee` ,`bsetupfee` ,`tsetupfee` ,`monthly` ,`quarterly` ,`semiannually` ,`annually` ,`biennially` ,`triennially` )
    VALUES (NULL , 'domainregister', '1', '$relid', '$reg', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00')";
    mysql_query($query);
    $query="INSERT INTO `tblpricing` (`id` ,`type` ,`currency` ,`relid` ,`msetupfee` ,`qsetupfee` ,`ssetupfee` ,`asetupfee` ,`bsetupfee` ,`tsetupfee` ,`monthly` ,`quarterly` ,`semiannually` ,`annually` ,`biennially` ,`triennially` )
    VALUES (NULL , 'domaintransfer', '1', '$relid', '$tran', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00')";
    mysql_query($query);
    $query="INSERT INTO `tblpricing` (`id` ,`type` ,`currency` ,`relid` ,`msetupfee` ,`qsetupfee` ,`ssetupfee` ,`asetupfee` ,`bsetupfee` ,`tsetupfee` ,`monthly` ,`quarterly` ,`semiannually` ,`annually` ,`biennially` ,`triennially` )
    VALUES (NULL , 'domainrenew', '1', '$relid', '$ren', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00')";
    mysql_query($query);
}
}//oneyear




if (isset($_POST['twoyear'])) {

$url = "http://$enomUrl/interface.asp?command=PE_GetRetailPricing&TLDOnly=1&years=2&uid={$_POST[enU]}&pw={$_POST[enP]}&responsetype=xml";
$ch = curl_init();
$timeout = 5; // set to zero for no timeout
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
$result = curl_exec($ch);
curl_close($ch);
$rss2 = simplexml_load_string($result);

foreach ($rss2->pricestructure->tld as $item2) {
$tld = '.'.$item2->tld;

    $query="SELECT tbldomainpricing.extension, tblpricing.relid FROM `tbldomainpricing` LEFT JOIN  `tblpricing` ON tbldomainpricing.id = tblpricing.relid WHERE tbldomainpricing.extension = '$tld'";
    mysql_query($query);
    $result = mysql_query($query) or die(mysql_error());


while($row = mysql_fetch_array($result)){
$relid = $row['relid'];

if (isset($_POST['roundupdown'])) {

if($item2->registerprice =="0.00"){
$reg2 = "-1";
$reg3 = "-1";
$reg4 = "-1";
}else{
$reg2 = round((float)$item2->registerprice) * 2;
$reg3 = round((float)$item2->registerprice) * 3;
$reg4 = round((float)$item2->registerprice) * 4;
}

if($item2->transferprice =="0.00"){
$tran2 = "-1";
$tran3 = "-1";
$tran4 = "-1";
}else{
$tran2 = round((float)$item2->transferprice) * 2;
$tran3 = round((float)$item2->transferprice) * 3;
$tran4 = round((float)$item2->transferprice) * 4;
}

if($item2->renewprice =="0.00"){
$ren2 = "-1";
$ren3 = "-1";
$ren4 = "-1";
}else{
$ren2 = round((float)$item2->renewprice) * 2;
$ren3 = round((float)$item2->renewprice) * 3;
$ren4 = round((float)$item2->renewprice) * 4;
}

}else{

if($item2->registerprice =="0.00"){
$reg2 = "-1";
$reg3 = "-1";
$reg4 = "-1";
}else{
$reg2 = ((float)$item2->registerprice * 2);
$reg3 = ((float)$item2->registerprice * 3);
$reg4 = ((float)$item2->registerprice * 4);
}

if($item2->transferprice =="0.00"){
$tran2 = "-1";
$tran3 = "-1";
$tran4 = "-1";
}else{
$tran2 = ((float)$item2->transferprice * 2);
$tran3 = ((float)$item2->transferprice * 3);
$tran4 = ((float)$item2->transferprice * 4);
}

if($item2->renewprice =="0.00"){
$ren2 = "-1";
$ren3 = "-1";
$ren4 = "-1";
}else{
$ren2 = ((float)$item2->renewprice * 2);
$ren3 = ((float)$item2->renewprice * 3);
$ren4 = ((float)$item2->renewprice * 4);
}

}//roundupdown

$query  = "UPDATE `tblpricing` SET `qsetupfee` = '$reg2', `ssetupfee` = '$reg3', `asetupfee` = '$reg4' WHERE `type` = 'domainregister' AND `relid` = '$relid'";
    mysql_query($query);

$query  = "UPDATE `tblpricing` SET `qsetupfee` = '$tran2', `ssetupfee` = '$tran3', `asetupfee` = '$tran4' WHERE `type` = 'domaintransfer' AND `relid` = '$relid'";
    mysql_query($query);

$query  = "UPDATE `tblpricing` SET `qsetupfee` = '$ren2', `ssetupfee` = '$ren3', `asetupfee` = '$ren4' WHERE `type` = 'domainrenew' AND `relid` = '$relid'";
    mysql_query($query);

}
}
}//twoyear


if (isset($_POST['fiveyear'])) {

$url = "http://$enomUrl/interface.asp?command=PE_GetRetailPricing&TLDOnly=1&years=2&uid={$_POST[enU]}&pw={$_POST[enP]}&responsetype=xml";
$ch = curl_init();
$timeout = 5; // set to zero for no timeout
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
$result = curl_exec($ch);
curl_close($ch);
$rss5 = simplexml_load_string($result);

foreach ($rss5->pricestructure->tld as $item5) {
$tld = '.'.$item5->tld;

    $query="SELECT tbldomainpricing.extension, tblpricing.relid FROM `tbldomainpricing` LEFT JOIN  `tblpricing` ON tbldomainpricing.id = tblpricing.relid WHERE tbldomainpricing.extension = '$tld'";
    mysql_query($query);
    $result = mysql_query($query) or die(mysql_error());


while($row = mysql_fetch_array($result)){
$relid = $row['relid'];

if (isset($_POST['roundupdown'])) {

if($item5->registerprice =="0.00"){
$reg5 = "-1";
$reg6 = "-1";
$reg7 = "-1";
$reg8 = "-1";
$reg9 = "-1";
}else{
$reg5 = round((float)$item5->registerprice) * 5;
$reg6 = round((float)$item5->registerprice) * 6;
$reg7 = round((float)$item5->registerprice) * 7;
$reg8 = round((float)$item5->registerprice) * 8;
$reg9 = round((float)$item5->registerprice) * 9;
}

if($item5->transferprice =="0.00"){
$tran5 = "-1";
$tran6 = "-1";
$tran7 = "-1";
$tran8 = "-1";
$tran9 = "-1";
}else{
$tran5 = round((float)$item5->transferprice) * 5;
$tran6 = round((float)$item5->transferprice) * 6;
$tran7 = round((float)$item5->transferprice) * 7;
$tran8 = round((float)$item5->transferprice) * 8;
$tran9 = round((float)$item5->transferprice) * 9;
}

if($item5->renewprice =="0.00"){
$ren5 = "-1";
$ren6 = "-1";
$ren7 = "-1";
$ren8 = "-1";
$ren9 = "-1";
}else{
$ren5 = round((float)$item5->renewprice) * 5;
$ren6 = round((float)$item5->renewprice) * 6;
$ren7 = round((float)$item5->renewprice) * 7;
$ren8 = round((float)$item5->renewprice) * 8;
$ren9 = round((float)$item5->renewprice) * 9;
}

}else{

if($item5->registerprice =="0.00"){
$reg5 = "-1";
$reg6 = "-1";
$reg7 = "-1";
$reg8 = "-1";
$reg9 = "-1";
}else{
$reg5 = ((float)$item5->registerprice * 5);
$reg6 = ((float)$item5->registerprice * 6);
$reg7 = ((float)$item5->registerprice * 7);
$reg8 = ((float)$item5->registerprice * 8);
$reg9 = ((float)$item5->registerprice * 9);
}

if($item5->transferprice =="0.00"){
$tran5 = "-1";
$tran6 = "-1";
$tran7 = "-1";
$tran8 = "-1";
$tran9 = "-1";
}else{
$tran5 = ((float)$item5->transferprice * 5);
$tran6 = ((float)$item5->transferprice * 6);
$tran7 = ((float)$item5->transferprice * 7);
$tran8 = ((float)$item5->transferprice * 8);
$tran9 = ((float)$item5->transferprice * 9);
}

if($item5->renewprice =="0.00"){
$ren5 = "-1";
$ren6 = "-1";
$ren7 = "-1";
$ren8 = "-1";
$ren9 = "-1";
}else{
$ren5 = ((float)$item5->renewprice * 5);
$ren6 = ((float)$item5->renewprice * 6);
$ren7 = ((float)$item5->renewprice * 7);
$ren8 = ((float)$item5->renewprice * 8);
$ren9 = ((float)$item5->renewprice * 9);
}

}//roundupdown

$query  = "UPDATE `tblpricing` SET `bsetupfee` = '$reg5', `monthly` = '$reg6', `quarterly` = '$reg7', `semiannually` = '$reg8', `annually` = '$reg9' WHERE `type` = 'domainregister' AND `relid` = '$relid'";
    mysql_query($query);

$query  = "UPDATE `tblpricing` SET `bsetupfee` = '$tran5', `monthly` = '$tran6', `quarterly` = '$tran7', `semiannually` = '$tran8', `annually` = '$tran9' WHERE `type` = 'domaintransfer' AND `relid` = '$relid'";
    mysql_query($query);

$query  = "UPDATE `tblpricing` SET `bsetupfee` = '$ren5', `monthly` = '$ren6', `quarterly` = '$ren7', `semiannually` = '$ren8', `annually` = '$ren9' WHERE `type` = 'domainrenew' AND `relid` = '$relid'";
    mysql_query($query);

}
}
}//fiveyear



if (isset($_POST['tenyear'])) {

$url = "http://$enomUrl/interface.asp?command=PE_GetRetailPricing&TLDOnly=1&years=10&uid={$_POST[enU]}&pw={$_POST[enP]}&responsetype=xml";
$ch = curl_init();
$timeout = 5; // set to zero for no timeout
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
$result = curl_exec($ch);
curl_close($ch);
$rss10 = simplexml_load_string($result);

foreach ($rss10->pricestructure->tld as $item10) {
$tld = '.'.$item10->tld;

    $query="SELECT tbldomainpricing.extension, tblpricing.relid FROM `tbldomainpricing` LEFT JOIN  `tblpricing` ON tbldomainpricing.id = tblpricing.relid WHERE tbldomainpricing.extension = '$tld'";
    mysql_query($query);
    $result = mysql_query($query) or die(mysql_error());


while($row = mysql_fetch_array($result)){
$relid = $row['relid'];

if (isset($_POST['roundupdown'])) {

if($item10->registerprice =="0.00"){
$reg10 = "-1";
}else{
$reg10 = round((float)$item10->registerprice) * 10;
}

if($item10->transferprice =="0.00"){
$tran10 = "-1";
}else{
$tran10 = round((float)$item10->transferprice) * 10;
}

if($item10->renewprice =="0.00"){
$ren10 = "-1";
}else{
$ren10 = round((float)$item10->renewprice) * 10;
}

}else{

if($item10->registerprice =="0.00"){
$reg10 = "-1";
}else{
$reg10 = ((float)$item10->registerprice * 10);
}

if($item10->transferprice =="0.00"){
$tran10 = "-1";
}else{
$tran10 = ((float)$item10->transferprice * 10);
}

if($item10->renewprice =="0.00"){
$ren10 = "-1";
}else{
$ren10 = ((float)$item10->renewprice * 10);
}

}//roundupdown

$query  = "UPDATE `tblpricing` SET `biennially` = '$reg10' WHERE `type` = 'domainregister' AND `relid` = '$relid'";
    mysql_query($query);

$query  = "UPDATE `tblpricing` SET `biennially` = '$tran10' WHERE `type` = 'domaintransfer' AND `relid` = '$relid'";
    mysql_query($query);

$query  = "UPDATE `tblpricing` SET `biennially` = '$ren10' WHERE `type` = 'domainrenew' AND `relid` = '$relid'";
    mysql_query($query);

}
}
}//tenyear

echo 'Import done.';    
}
//}//doimport

if($_POST[action]=='changeprices'){

if (isset($_POST['pricereg'])) {
$pricefrom = (float)$_POST['pricefrom'];
$priceto = (float)$_POST['priceto'];

$query  = "UPDATE `tblpricing` SET `msetupfee` = '$priceto' WHERE `type` = 'domainregister' AND `msetupfee` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `qsetupfee` = '$priceto' WHERE `type` = 'domainregister' AND `qsetupfee` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `ssetupfee` = '$priceto' WHERE `type` = 'domainregister' AND `ssetupfee` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `asetupfee` = '$priceto' WHERE `type` = 'domainregister' AND `asetupfee` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `bsetupfee` = '$priceto' WHERE `type` = 'domainregister' AND `bsetupfee` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `monthly` = '$priceto' WHERE `type` = 'domainregister' AND `monthly` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `quarterly` = '$priceto' WHERE `type` = 'domainregister' AND `quarterly` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `semiannually` = '$priceto' WHERE `type` = 'domainregister' AND `semiannually` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `annually` = '$priceto' WHERE `type` = 'domainregister' AND `annually` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `biennially` = '$priceto' WHERE `type` = 'domainregister' AND `biennially` = '$pricefrom'";
mysql_query($query);
}

if (isset($_POST['pricetran'])) {
$pricefrom = (float)$_POST['pricefrom'];
$priceto = (float)$_POST['priceto'];

$query  = "UPDATE `tblpricing` SET `msetupfee` = '$priceto' WHERE `type` = 'domaintransfer' AND `msetupfee` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `qsetupfee` = '$priceto' WHERE `type` = 'domaintransfer' AND `qsetupfee` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `ssetupfee` = '$priceto' WHERE `type` = 'domaintransfer' AND `ssetupfee` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `asetupfee` = '$priceto' WHERE `type` = 'domaintransfer' AND `asetupfee` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `bsetupfee` = '$priceto' WHERE `type` = 'domaintransfer' AND `bsetupfee` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `monthly` = '$priceto' WHERE `type` = 'domaintransfer' AND `monthly` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `quarterly` = '$priceto' WHERE `type` = 'domaintransfer' AND `quarterly` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `semiannually` = '$priceto' WHERE `type` = 'domaintransfer' AND `semiannually` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `annually` = '$priceto' WHERE `type` = 'domaintransfer' AND `annually` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `biennially` = '$priceto' WHERE `type` = 'domaintransfer' AND `biennially` = '$pricefrom'";
mysql_query($query);
}

if (isset($_POST['priceren'])) {
$pricefrom = (float)$_POST['pricefrom'];
$priceto = (float)$_POST['priceto'];

$query  = "UPDATE `tblpricing` SET `msetupfee` = '$priceto' WHERE `type` = 'domainrenew' AND `msetupfee` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `qsetupfee` = '$priceto' WHERE `type` = 'domainrenew' AND `qsetupfee` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `ssetupfee` = '$priceto' WHERE `type` = 'domainrenew' AND `ssetupfee` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `asetupfee` = '$priceto' WHERE `type` = 'domainrenew' AND `asetupfee` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `bsetupfee` = '$priceto' WHERE `type` = 'domainrenew' AND `bsetupfee` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `monthly` = '$priceto' WHERE `type` = 'domainrenew' AND `monthly` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `quarterly` = '$priceto' WHERE `type` = 'domainrenew' AND `quarterly` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `semiannually` = '$priceto' WHERE `type` = 'domainrenew' AND `semiannually` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `annually` = '$priceto' WHERE `type` = 'domainrenew' AND `annually` = '$pricefrom'";
mysql_query($query);
$query  = "UPDATE `tblpricing` SET `biennially` = '$priceto' WHERE `type` = 'domainrenew' AND `biennially` = '$pricefrom'";
mysql_query($query);
}


echo 'Changes done.';
}//changeprices
?>