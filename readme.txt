/* changelog */
/*
    Version 1.1
	added artist taxonomy
    Version 1.1.1
	removed post thumbnails (redundant since we're using a custom image uploader)
    Version 1.1.2
	added note to tracklist textarea description
    Version 1.1.3
	sanitized textarea to prevent malicious code from being embedded in release pages
	embed code area obviously needs html to be allowed.  sanitized the html before it goes to the database and put a strongly-worded warning threatening anyone who misuses their privelages
	Version 1.1.4
	added release date and plague release number
	Version 1.1.5
	added archive.org release identifier
	Version 1.1.6
	troubleshot why plague release wasn't displaying in input box when value was the same as archive.org release identifier (answer, i have no idea, but it's working)
	added plague release number to echo under the input box in case of above issue
	added option to insert plague release number into the description of the internet archive input box
	Version 1.2
	added custom columns for releases
	Version 1.2.1
	added more options for buy now links
	Version 1.2.2
	changed plague_release meta tag name to plague_release_number -- seems to be working in the columns now
*/