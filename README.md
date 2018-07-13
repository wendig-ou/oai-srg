## Production (how to use)

* checkout the repo to within apache's DocumentRoot
* copy .env.sample to .env and make changes within the file to reflect your
  deployment
* run `composer install --no-dev`
* run `composer dump-autoload`
* run the setup routine: `php bin/setup.php` to create the database structure

## Development

To get started developing, first install VirtualBox and Vagrant and git. Then
make sure that the guest additions plugin is installed by running

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

You find the current directory at `/vagrant` so you will probably want to

~~~ bash
cd /vagrant
~~~

From there, to start the development server, run 

~~~ bash
bin/dev.sh
~~~

And navigate to http://localhost:3000 with your browser.

## Tests

The test suits can be run with `bin/test.sh`. Make sure the test server and the
data server are both running (`bin/test-server.sh` and `bin/serve-test-data.sh`).
