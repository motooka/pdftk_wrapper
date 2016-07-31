# pdftk wrapper

This is a simple web application which executes `pdftk ${source-file} output ${destination-file}` for the uploaded PDF file. URL of the result file will be available of the resulting JSON. 

## How to set up the server

### Dependencies

- pdftk server https://www.pdflabs.com/tools/pdftk-server/
- machine on which pdftk server can run. Ubuntu is recommended.
- Apache or your favourite HTTP server which can work with PHP.
- PHP
	- 5.3 : does not work
	- 5.4 : works, maybe. not tested.
	- 5.5 : works, maybe. more possibly than 5.4.
	- 5.6 : the author uses this version.
	- 7.0 : works, maybe. not tested but will be tested soon.

### Server set up : example sequence

- Prepare an Ubuntu 14.04 machine
- Install dependencies
	- `sudo aptitude update`
		- If you prefer `apt-get`, it also works.
	- `sudo aptitude install pdftk apache2 libapache2-mod-php5 php5 git`
- Clone this project on your server
- Use `chmod` or `chown` command so that your http server program can write files under the `tmp` directory in `webserver`.
- Copy `webserver` directory to your DocumentRoot. Using `rsync -a ${source} ${destination}` is recommended.

## Required Operations

All the uploaded & resulting PDF files remain on your server. So these files should be wiped out regularly.

## Security Considerations

This wrapper is NOT intended to be placed on publicly accessible servers.

## License

MIT License

