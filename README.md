This is an implementation of the [OAI Static Repository Gateway Specification](
http://www.openarchives.org/OAI/2.0/guidelines-static-repository.htm). Its
OAI-PMH endpoints pass the [data provider validation](
https://www.openarchives.org/Register/ValidateSite).

**Known limitations**

* The gateway validates repository XML against the relevant schema. However,
  this validation is limited to the parts covered by the Static-Repository and
  OAI-PMH schemata. The payload (essentially `<metadata>` elements and their
  contents) is not validated.

## Installation

* checkout the repo to within apache's DocumentRoot
* if you are using Apache Alias directives to map the url to a filesystem path,
  older systems and some deployments on Windows might require additional
  configuration for the matching filesystem path:
  ```
  <Directory /path/to/app>
    ...
    RewriteOptions MergeBase
    RewriteBase /sub/url-path/to/app
  </Directory>
  ```
  See the
  [apache documentation](https://httpd.apache.org/docs/current/mod/mod_rewrite.html#rewritebase)
  for details.
* copy .env.sample to .env and make changes within the file to reflect your
  deployment. These are just environment variables, you may also configure them
  with your web-server's environment, e.g. with Apache's SetEnv
* run `composer install --no-dev`
* run `composer dump-autoload`
* run the setup routine: `php bin/setup.php` to create the database structure

Depending on your environment, server performance and the size of your data
sets, you might have to change some resource limits for php, e.g.

~~~
max_execution_time = 300
memory_limit = 256M
~~~

## Development

To get started developing, first install VirtualBox, Vagrant and git. Then make
sure the guest additions plugin is installed by running

~~~ bash
vagrant plugin install vagrant-vbguest
~~~

Now to generate the vagrant environment, simply run

~~~ bash
vagrant up php5
~~~

or 

~~~ bash
vagrant up php7
~~~

depending on the environment you wish to develop on. This step will likely take
several minutes. Essentially, a VirtualBox VM is created and configured to run
the development environment.

With the VM up and running, the following command brings you to a terminal
session within the VM

~~~ bash
vagrant ssh
~~~

Within the VM, the current directory is mounted at `/vagrant` so you will
probably want to

~~~ bash
cd /vagrant
~~~

From there, install the dependencies including development and test:

~~~
composer install
composer dump-autoload
~~~

Then, create databases for the development and test environment as defined in
`.env.development` and `.env.test` and/or modify those files accordingly.

To start the development server, run

~~~ bash
bin/dev.sh
~~~

And navigate to http://localhost:3000 with your browser.

## Tests

The test suits can be run with `bin/test.sh`. Make sure the test server and the
data server are both running (`bin/test-server.sh` and `bin/serve-test-data.sh`).
