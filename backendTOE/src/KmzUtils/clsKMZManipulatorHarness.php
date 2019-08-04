<?php
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 8/17/2017
 * Time: 2:52 PM
 */

require __DIR__ . "/clsKMZManipulator.php";
require __DIR__ . "/../bootstrap.php";

$kmzPath = "C:\\Users\\LENOVO-T430\\Documents\\yearFiveGuelph\\Fall\\CIS4900-TrickOrEat\\RouteMaps\\Trick-Or-Eat-Zone-O_Copy.kmz";

$kmz = new clsKMZManipulator($kmzPath, clsKMZManipulator::KMZ, clsConstants::ROUTE_HOSTING_DIRECTORY . "/temp");
//var_dump($kmz);
$kmz->ModifyBusStop(17, 17);
$kmz->SaveKMLChanges();
$kmz->SaveKMZFile();

//$zip = new ZipArchive();
//$zip->open($kmzPath, ZipArchive::CREATE);
//$zip->addEmptyDir("test");
//$zip->close();