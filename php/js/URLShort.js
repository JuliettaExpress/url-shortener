// JS-to-JS scripts should use this overload
function URLShort(urlInput, keepReferral)
{
	var returnObj =
	{
		shouldPostback: false,
		shortenedURL: "",
		error: false,
		errorType: null
	};
	var url;

	try
	{
		url =  new URL(urlInput);
		url.host = url.host.toLowerCase().replace(/^www./i, ""); // lowercase and remove preceding "www." if any
	}
	catch
	{
		// URL could not be parsed. Postback shouldn't have to try parsing either, and label for this validator should exist client-side anyway, so prevent postback and bail early
		returnObj.shouldPostback = false;
		returnObj.error = true;
		returnObj.errorType = "InvalidURL";

		return returnObj;
	}

	switch (url.host)
	{
		case "amazon.com":
			//RegEx implementaions don't always like using $ in "one of" match blocks, so I can't necessarily use "slash or end of string". For these, append slash to end always so it hits every time.
			var slashEnd = url.pathname + "/";
			var handled = false;
			var match;

			// Matches format /dp/1234567890
			if (!handled)
			{
				match = slashEnd.match(/^(?:\/[^\/]+)?(\/(?:dp|gp)\/\w{10})\//i);

				if (match != null)
				{
					url.pathname = match[1];
					handled = true;
				}
			}

			// Matches format /gp/product/1234567890
			if (!handled)
			{
				match = slashEnd.match(/^(?:\/[^\/]+)?(\/(?:dp|gp)\/product\/\w{10})\//i);

				if (match != null)
				{
					url.pathname = match[1];
					handled = true;
				}
			}

			// Matches format /gp/aw/d/1234567890
			if (!handled)
			{
				match = slashEnd.match(/^(?:\/[^\/]+)?(\/(?:dp|gp)\/[a-z]{2}\/[a-z]\/\w{10})\//i);

				if (match != null)
				{
					url.pathname = match[1];
					handled = true;
				}
			}

			if (handled)
			{
				returnObj.shouldPostback = false;
				returnObj.shortenedURL = url.origin + url.pathname; // No other parts of URL are useful for Amazon
				return returnObj;
			}
			break;
		/*
		case "eBay.com":
			break;
		// */
		case "google.com":
			// Matches format /search?q=QUERY&tbm=vid&start=10

			switch(url.pathname.toLowerCase())
			{
				case "/search":
					var newParams = new URLSearchParams();

					for (let [key, value] of url.searchParams)
					{
						switch (key.toLowerCase())
						{
							case "q":
							case "tbm":
							case "start":
								newParams.append(key, value);
								break;
						}
					}

					url.searchParams = newParams;
					url.search = newParams.toString();

					returnObj.shouldPostback = false;
					returnObj.shortenedURL = url.href;
					return returnObj;
			}
			break;
		case "stackoverflow.com":
				if (url.pathname.toLowerCase().startsWith("/questions/"))
				{
					var pathSplit = url.pathname.substr(1).split('/');

					url.pathname = "/" + pathSplit[0] + "/" + pathSplit[1];
					url.searchParams = new URLSearchParams();
					url.search = "";

					returnObj.shouldPostback = false;
					returnObj.shortenedURL = url.href;
					return returnObj;
				}
			break;
		case "youtube.com":
			// Matches format /watch?v=1234567891&list=WL&index=170

			switch (url.pathname.toLowerCase())
			{
				case "/watch":
					var newParams = new URLSearchParams();
					var argVid;

					for (let [key, value] of url.searchParams)
					{
						switch (key.toLowerCase())
						{
							case "v":
								argVid = value;
								break;
							case "t":
								newParams.append(key, value);
								break;
						}
					}

					if (argVid)
					{
						url.host = "youtu.be";
						url.pathname = argVid;
					}

					url.searchParams = newParams;
					url.search = newParams.toString();

					returnObj.shouldPostback = false;
					returnObj.shortenedURL = url.href;
					return returnObj;
				case "/playlist":
					var newParams = new URLSearchParams();

					for (let [key, value] of url.searchParams)
					{
						switch (key.toLowerCase())
						{
							case "list":
								newParams.append(key, value);
								break;
						}
					}

					url.searchParams = newParams;
					url.search = newParams.toString();

					returnObj.shouldPostback = false;
					returnObj.shortenedURL = url.href;
					return returnObj;
			}
			break;
	}

	// If code gets here, it hasn't been handled by javascript already, and hasn't had an error either. Ergo, postback so server can handle it further.
	returnObj.shouldPostback = true;

	return returnObj;
}

// Direct-from-page HTML forms should use this overload
function URLShortForm()
{
	//NOTE: Graceful "missing control" handling via injection of error label omitted. Expectation is that pages that implement this code are complete enough to have all necessary elements within

	// Grab controls up top before grabbing values...
	var txtURLInput = document.getElementById("txtURLInput");
	var txtURLShortened = document.getElementById("txtURLShortened");
	var chkKeepReferral = document.getElementById("chkKeepReferral"); // if null, assume referrals aren't kept
	var lblInvalidURL = document.getElementById("lblInvalidURL");

	// Hide validation error labels before other sanity checks on other controls to prevent confusion
	if (lblInvalidURL == null) { return true; }
	lblInvalidURL.style.display = "none";

	// Sanity checks -- page can be manipulated with a postback
	if (txtURLInput == null || txtURLShortened == null) { return true; }

	// Grab result
	var urlShortObj = URLShort(txtURLInput.value, (chkKeepReferral == null ? false : chkKeepReferral.checked));

	// Shouldn't hit, but just in case
	if (urlShortObj == null) { return true; }

	if (urlShortObj.error)
	{
		// Clear out shortened URL to avoid confusion
		txtURLShortened.value = "";

		switch (urlShortObj.errorType)
		{
			case "InvalidURL":
				lblInvalidURL.style.display = ""; // Shows "invalid" message
				break;
		}
	}
	else
	{
		txtURLShortened.value = urlShortObj.shortenedURL;
	}

	return urlShortObj.shouldPostback;
}