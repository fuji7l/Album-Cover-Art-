<?php
require_once 'saAlbumCoverArt.class.php';

//$album = new saAlbumCoverArt('Enter The Wu-Tang Clan', 'Wu-Tang Clan', '', array('save' => 0));
$album = new saAlbumCoverArt('Enter The Wu-Tang Clan', 'Wu-Tang Clan');

$album->execute();

echo $album->getCoverFile();
echo "\n\n";

