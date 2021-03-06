<?php if(empty($_SERVER["PATH_INFO"])) exit;
	$get = explode('/', trim($_SERVER["PATH_INFO"],'/'));
	if(count($get) == 2){
		$cookie = tempnam(sys_get_temp_dir(), 'cookies');
		header('Content-type: application/x-bittorrent');
		// Link is in this format:
		// http://www.demonoid.ph/files/details/xxx/yyy
		$details = "/files/details/{$get[0]}/{$get[1]}/";
		$page = new DOMDocument;
		@$page->loadHTML(file_get_contents("http://www.demonoid.ph$details"));
		$XPath = new DOMXPath($page);
		$tables = $XPath->query('//table/tr/td[@class="ctable_content"]/a');
		$torrent = NULL;
		for($i = 0; $i < $tables->length; $i++){
			// Torrent links are:
			// http://www.demonoid.ph/files/download/xxx/yyy
			$href = $tables->item($i)->getAttribute('href');
			if(strpos($href, '/files/download/') !== FALSE){
				$torrent = $href;
				break;
			}
		}
		if(!is_null($torrent)){
			// POST to https://www.demonoid.ph/account_handler.php
			require_once('demonoidPW.php');
			$postInfo = array_merge($postInfo, array(
				'Submit' => 'Submit',
				'returnpath' => $torrent,
				'withq' => 0
			));

			$ch = curl_init();
			curl_setopt_array($ch, array(
				CURLOPT_URL => 'http://www.demonoid.ph/account_handler.php',
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $postInfo,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_COOKIEJAR => $cookie,
				CURLOPT_COOKIEFILE => $cookie
			));

			curl_exec($ch);
			curl_close($ch);
			unlink($cookie);
			exit;
		}
	}
	else{
		header('Content-type: application/rss+xml');
		$rss = new DOMDocument();
		$rss->load("http://www.demonoid.ph/rss/users/{$get[0]}.xml");
		$items = $rss->getElementsByTagName('item');
		for($i = 0; $i < $items->length; $i++){
			$item = $items->item($i);
			$node = $item->getElementsByTagName('link')->item(0);
			// Link is in this format:
			// http://www.demonoid.ph/files/details/xxx/yyy
			$link = $node->textContent;
			$link = explode('/', rtrim($link, '/'));
			$linkLength = count($link);
			$id2 = $link[$linkLength-1];
			$id1 = $link[$linkLength-2];
			$newLink = $rss->createElement('link',
				"http://{$_SERVER["HTTP_HOST"]}{$_SERVER["SCRIPT_NAME"]}/$id1/$id2");
			$item->replaceChild($newLink, $node);
		}
		die($rss->saveXML());
	}
?>
