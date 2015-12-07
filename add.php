<?php
error_reporting(0);
ini_set("display_errors", 1);
set_time_limit(0);
define('INDEXLOCATION',dirname(__FILE__).'/index/');
define('DOCUMENTLOCATION',dirname(__FILE__).'/documents/');

include_once('./classes/ranker.class.php');
include_once('./classes/indexer.class.php');
include_once('./classes/multifolderindex.class.php');
include_once('./classes/multifolderdocumentstore.class.php');

$ranker = new ranker();
$index = new multifolderindex();
$docstore = new multifolderdocumentstore();
$indexer = new indexer($index,$docstore,$ranker);


function html2txt($document){ 
	$search = array('@<script[^>]*?>.*?</script>@si',
					'@<[\/\!]*?[^<>]*?>@si',
					'@<style[^>]*?>.*?</style>@siU',
					'@<![\s\S]*?--[ \t\n\r]*>@',
					'@<style[^>]*?>.*?</style>@si',
					'@\W+@si',
	); 
	$text = preg_replace($search, ' ', $document); 
	return $text; 
} 


$toindex = array();

$count = 0;

foreach(new RecursiveIteratorIterator (new RecursiveDirectoryIterator ('./crawler/documents/')) as $x) {
	$filename = $x->getPathname();
	if(is_file($filename)) {
		$handle = fopen($filename, 'r');
		$contents = fread($handle, filesize($filename));
		fclose($handle);
		$unserialized = unserialize($contents);
		
		$url = $unserialized[0];
		$content = $unserialized[1];
		$rank = $unserialized[2];
		
		preg_match_all('/<title.*?>.*?<\/title>/i',$content, $matches);
		if(count($matches[0]) != 0) {
			$title = preg_replace('/[^(\x20-\x7F)]*/','',trim(strip_tags($matches[0][0])));
		}
		else {
			$title = '';
		}
		
		$tmp = get_meta_tags("data://$mime;base64,".base64_encode($content));
		if(isset($tmp['description'])) {
			$desc = preg_replace('/[^(\x20-\x7F)]*/','',trim($tmp['description']));
		}
		else {
			$desc = '';
		}
		
		$content = preg_replace('/[^(\x20-\x7F)]*/','',trim(strip_tags(html2txt($content))));
		
		if($desc == '' && $content != '') {
			$desc = substr($content,0,200).'...';
		}
		if($title == '' && $desc != '') {
			$title = substr($desc,0,50).'...';
		}
		
		$count++;
		if($title != '') {
			$toindex[] = array($url, $title, $desc, $rank);
			echo 'INDEXING '.$count."\r\n";
		}
		else {
			echo 'SKIP '.$count."\r\n";
		}
		
	}
}
echo "Starting Index\r\n";
$indexer->index($toindex);

?>