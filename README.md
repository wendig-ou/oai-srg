## Development

To get started developing, first install VirtualBox and Vagrant and git. Then
make sure that the guest additions plugin is installed by running

 vagrant plugin install vagrant-vbguest

Now to generate the vagrant environment, simply run

 vagrant up php5

or 

 vagrant up php7

depending on the environment you wish to develop on. This step will likely take
several minutes. Essentially, a VirtualBox VM is created and configured to run
the development environment.

With the VM up and running, the following command brings you to a terminal
session within the VM

 vagrant ssh

You find the current directory at `/vagrant` so you will probably want to

 cd /vagrant