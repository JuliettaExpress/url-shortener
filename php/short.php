<!DOCTYPE html>
<html lang="en-US">
	<head>
		<script src="js/URLShort.js"></script>
		<title>URL Link Shortener</title>
	</head>
	<body>
<?php

$shortenedURL = "";

if (!empty($_POST["txtURLInput"]))
{
	$shortenedURL = ShortenLink($_POST["txtURLInput"]);
}

function StartsWith($searchIn, $searchFor, $caseSensitive = false)
{
	if (!$caseSensitive)
	{
		$searchIn = strtolower($searchIn);
		$searchFor = strtolower($searchFor);
	}

	return substr($searchIn, 0, strlen($searchFor)) === $searchFor;
}

function AddArg($args, $newArgKey, $newArgValue)
{
	if (!empty($args))
	{
		$args = $newArgKey . "=" . $newArgValue;
		return $args;
	}
	else
	{
		$args = $args . "&" . $newArgKey . "=" . $newArgValue;
		return $args;
	}
}

function GetURLParts($inputURL, $removeWWW = true)
{
	$url = parse_url($inputURL);

	// Remove starting "www." if any exists
	if ($removeWWW && StartsWith($url["host"], "www."))
	{
		$url["host"] = substr($url["host"], 4);
	}

	return $url;
}

function ShortenLink($inputURL)
{
	$url = GetURLParts($inputURL);

	switch (strtolower($url["host"]))
	{
		case "amzn.to":
			return Amazon(GetURLRedirectSource($inputURL));
		case "amazon.com":
			return Amazon($inputURL);
		case "ebay.com":
			return eBay($inputURL);
		case "google.com":
			return Google($inputURL);
		case "stackoverflow.com":
			return StackOverflow($inputURL);
		case "youtube.com":
			return YouTube($inputURL);
	}

	return $inputURL;
}

function GetURLRedirectSource($inputURL)
{
	$headers = @get_headers($inputURL, 1);
	return $headers['Location'];
}

function Amazon($inputURL)
{
	$url = GetURLParts($inputURL);

	// Matches format /stores/page/*
	if (StartsWith($url["path"], "/stores/page/"))
	{
		return $url["scheme"] . "://" . $url["host"] . $url["path"];
	}

	// Matches format /dp/1234567890
	if (preg_match('/^(?:\/[^\/]+)?(\/(?:dp|gp)\/\w{10})(?:\/|$)/', $url["path"], $matches))
	{	
		return $url["scheme"] . "://" . $url["host"] . $matches[1];
	}

	// Matches format /gp/product/1234567890
	if (preg_match('/^(?:\/[^\/]+)?(\/(?:dp|gp)\/product\/\w{10})(?:\/|$)/', $url["path"], $matches))
	{
		return $url["scheme"] . "://" . $url["host"] . $matches[1];
	}

	// Matches format /gp/aw/d/1234567890
	if (preg_match('/^(?:\/[^\/]+)?(\/(?:dp|gp)\/[a-z]{2}\/[a-z]\/\w{10})(?:\/|$)/', $url["path"], $matches))
	{
		return $url["scheme"] . "://" . $url["host"] . $matches[1];
	}

	return $inputURL;
}

function eBay($inputURL)
{
	//TODO: FINISH!!!
	return $inputURL;
}

function Google($inputURL)
{
	$url = GetURLParts($inputURL);

	switch (strtolower($url["path"]))
	{
		case "/search":
			$newArgs = "";

			parse_str($url["query"], $args);

			foreach($args as $key => $value)
			{
				switch (strtolower($key))
				{
					case "q":
					case "tbm":
					case "start":
						if ($args[$key] != null)
						{
							$newArgs = AddArg($newArgs, $key, $value);
						}
						break;
				}
			}

			if ($newArgs != "")
			{
				$newArgs = "?" . $newArgs;
			}

			return $url["scheme"] . "://" . $url["host"] . $url["path"] . $newArgs;
	}

	return $inputURL;
}

function StackOverflow($inputURL)
{
	$url = GetURLParts($inputURL);

	if (StartsWith($url["path"], "/questions/"))
	{
		$pathParts = explode('/', substr($url["path"], 1));
		$newPath = "/" . implode("/", array($pathParts[0], $pathParts[1]));

		return $url["scheme"] . "://" . $url["host"] . $newPath;
	}

	return $inputURL;
}

function YouTube($inputURL)
{
	$url = GetURLParts($inputURL);

	switch (strtolower($url["path"]))
	{
		case "/watch":
			$newArgs = "";
			$argVid = null;

			parse_str($url["query"], $args);

			foreach($args as $key => $value)
			{
				switch (strtolower($key))
				{
					case "v":
						$argVid = $value;
						break;
					case "t":
						if ($args[$key] != null)
						{
							$newArgs = AddArg($newArgs, $key, $value);
						}
						break;
				}
			}

			if ($newArgs != "")
			{
				$newArgs = "?" . $newArgs;
			}

			if ($argVid != null)
			{
				return $url["scheme"] . "://youtu.be/" . $argVid . $newArgs;
			}

			return $url["scheme"] . "://" . $url["host"] . $url["path"] . $newArgs;
		case "/playlist":
			$newArgs = "";

			parse_str($url["query"], $args);

			foreach($args as $key => $value)
			{
				switch (strtolower($key))
				{
					case "list":
						if ($args[$key] != null)
						{
							$newArgs = AddArg($newArgs, $key, $value);
						}
						break;
				}
			}

			if ($newArgs != "")
			{
				$newArgs = "?" . $newArgs;
			}

			return $url["scheme"] . "://" . $url["host"] . $url["path"] . $newArgs;
	}

	return $inputURL;
}
?>
		<form action="short.php" method="post" onsubmit="return URLShortForm();">
			<label for="txtURLInput">URL Input:</label><br />
			<input type="text" id="txtURLInput" name="txtURLInput" style="width: 100%;" required><br />
			<!-- Feel free to implement this feature, I won't however -->
			<!-- <input type="checkbox" id="chkKeepReferral" name="chkKeepReferral" value="KeepReferral">
			<label for="chkKeepReferral">Keep referral?</label><br /> -->
			<br />
			<label for="txtURLShortened">Shortened:</label><br />
			<input type="text" id="txtURLShortened" name="txtURLShortened" style="width: 100%;" value="<?php echo $shortenedURL; ?>" readonly><br />
			<br />
			<input type="submit" value="Submit">
			<input type="reset">
			<span id="lblInvalidURL" class="red bold" style="display: none;">Invalid Input URL</span>
		</form>
	</body>
</html>